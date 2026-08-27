#!/usr/bin/env python3
"""Static input audit scanner: find create/update calls and cross-check
submitted fields against model $fillable (data-loss / mass-assignment /
validation-gap hotspots). Read-only. Report to stdout."""
import os, re, sys, json
from pathlib import Path

ROOT = r"C:\Users\HYPE AMD\Project\material_incoming"
CON = Path(ROOT) / "app" / "Http" / "Controllers"
MOD = Path(ROOT) / "app" / "Models"

# --- 1. collect model fillable maps ---
def model_fillable():
    m = {}
    for f in MOD.rglob("*.php"):
        try: txt = f.read_text(encoding="utf-8")
        except Exception: continue
        base = f.stem
        # fillable array literal
        fm = re.search(r'protected\s+\$fillable\s*=\s*\[(.*?)\]', txt, re.S)
        fill = []
        if fm:
            fill = re.findall(r"'([^']+)'", fm.group(1))
        # guarded
        gm = re.search(r'protected\s+\$guarded\s*=\s*\[(.*?)\]', txt, re.S)
        guard = re.findall(r"'([^']+)'", gm.group(1)) if gm else None
        # restrictive = non-empty $fillable whitelist (silent-drop class)
        restrictive = bool(fill)
        m[base] = {"fillable": fill, "guarded": guard, "restrictive": restrictive}
    return m

# --- 2. parse create/update calls in a controller body ---
RR = "\\\\.|".join([])  # noop
def find_calls(txt, mfile):
    calls = []
    # match expressions like:  SomeModel::create(  or  $var->create(  or  ::firstOrCreate(  etc
    pat = re.compile(r'((?:[A-Z][\w\\]*|\$[\w]+)\s*::|->)(create|update|firstOrCreate|updateOrCreate|insert|insertOrIgnore|upsert)\s*\(', re.S)
    for mt in pat.finditer(txt):
        model_tok = mt.group(1).strip()
        method = mt.group(2)
        start = mt.end()
        # capture arguments between matched paren (handle nested)
        depth = 1; i = start; args_start = start
        while i < len(txt) and depth > 0:
            if txt[i] == '(': depth += 1
            elif txt[i] == ')': depth -= 1
            i += 1
        args = txt[args_start:i-1] if i-1 > args_start else ""
        calls.append((model_tok, method, args.strip()))
    return calls

def payload_info(args):
    """Return (payload_type, keys[]) for an args string."""
    a = args.strip()
    if '$request->all()' in a or '$request->all(' in a:
        return ("request->all()", [])
    if '$request->validated(' in a or '->validated(' in a:
        return ("validated()", [])
    m = re.search(r'(\$[\w]+)\s*,\s*$', a)  # e.g. ->create($data)
    # explicit array literal
    am = re.search(r'\[(.*)\]\s*$', a, re.S)
    if am:
        keys = re.findall(r"'([^']+)'\s*=>", am.group(1))
        if keys:
            # figure payload full? treat as explicit
            # also detect shorthand '=>' with variable (bulk) - count keys
            return ("explicit", keys)
    # variable payload
    vars = set(re.findall(r'(\$[A-Za-z_]\w+)', a))
    for v in vars:
        if v.startswith('$request') or v in ('$data','$validated','$item','$payload'):
            return ("var:"+v, [])
    if '=>' not in a and a.count(',')>=2 and a.startswith('['):
        return ("explicit(partial)", re.findall(r"'([^']+)'\s*=>", a))
    return ("other", [])

def has_validate(txt):
    return bool(re.search(r'->validate\s*\(|\$validator\s*=|validator::make', txt))

def resolve_model(tok, mfile):
    """Map a controller's local variable / model-ish tok to a model base name."""
    tok = tok.strip()
    if tok.endswith('::'):
        cls = tok.rstrip(':').split('\\')[-1].split('::')[-1]
        # partial class -> base
        return cls.replace('_','') if cls else None
    return None

fillable = model_fillable()
modes = {}
# map controller->possible models via 'use App\\Models\\X;'
for f in CON.rglob("*.php"):
    try: txt = f.read_text(encoding="utf-8")
    except Exception: continue
    rel = f.relative_to(CON).as_posix()
    uses = re.findall(r'use\s+App\\Models\\([A-Za-z0-9_]+)\s*;', txt)
    calls = find_calls(txt, f)
    if not calls: continue
    rows = []
    for (tok, method, args) in calls:
        pt, keys = payload_info(args)
        # resolve model base name from tok
        if '::' in tok:
            base = tok.rstrip(':').split('\\')[-1].replace('::','')
        elif tok.startswith('$'):
            base = None
        else:
            base = tok
        # try to match against used models / filenames
        fill = None
        if base:
            cf = fillable.get(base)
            if cf is None:
                # fuzzy: find model whose name ends with base or base endswith model
                for k in fillable:
                    if k.endswith(base) or base.endswith(k):
                        cf = fillable[k]; break
        missing = []
        if cf is not None and cf.get("restrictive") and pt == "explicit":
            missing = [k for k in keys if k not in cf["fillable"]]
        if cf is not None and not cf.get("restrictive") and cf.get("guarded") == []:
            guard_flag = "ALLOW-ALL(guarded=[])"
        elif cf is not None and cf.get("guarded") == ['id'] and not cf.get("restrictive"):
            guard_flag = "ALLOW-ALL(guarded=['id'])"
        elif cf is not None and not cf.get("restrictive") and (cf.get("guarded") in (None,)):
            guard_flag = "MASS-EXCEPT(default '*'??)"
        else:
            guard_flag = ""
        rows.append({
            "file": rel, "model": base, "method": method,
            "payload": pt, "keys": keys,
            "fillable_has": cf["fillable"] if cf else None,
            "missing": missing, "validate": has_validate(txt),
            "guard_flag": guard_flag,
        })
    modes[rel] = rows

# --- output ---
print(f"# Controllers scanned: {len(modes)}")
print(f"# Models with fillable: {len(fillable)}")
print()
data_loss = []
mass = []
noreg = []
for rel, rows in modes.items():
    for r in rows:
        tag = []
        if r["missing"]:
            data_loss.append((rel, r))
        if r["payload"] in ("request->all()",):
            mass.append((rel, r))
        if r["validate"] is False and r["method"] in ("create","update","firstOrCreate","updateOrCreate","insert"):
            noreg.append((rel, r))

def show(title, items):
    print(f"\n=== {title} ({len(items)}) ===")
    for rel, r in items:
        print(f"  [{rel}] -> {r['model']}::{r['method']}")
        print(f"       payload={r['payload']} keys={r['keys'][:20]}")
        if r["missing"]:
            print(f"       MISSING from fillable: {r['missing']}")
        if r["guard_flag"]:
            print(f"       {r['guard_flag']}")
        if not r["validate"]:
            print(f"       NO validate() in file")

show("DATA-LOSS candidates (explicit field not in $fillable)", data_loss)
show("MASS-ASSIGNMENT ($request->all())", mass)

print("\n\n=== ALL create/update calls (full inventory) ===")
for rel, rows in modes.items():
    for r in rows:
        if r["method"] in ("create","update","firstOrCreate","updateOrCreate","insert"):
            fill = r["fillable_has"]
            print(f"{rel}|{r['model']}|{r['method']}|{r['payload']}|{len(r['keys'])}keys|missing={len(r['missing'])}|validate={r['validate']}")

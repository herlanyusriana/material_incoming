#!/usr/bin/env python3
"""input-scan v2: precise data-loss static audit."""
import re
from pathlib import Path

ROOT = r"C:\Users\HYPE AMD\Project\material_incoming"
CON = Path(ROOT) / "app" / "Http" / "Controllers"
MOD = Path(ROOT) / "app" / "Models"

model_map = {}
for f in MOD.rglob("*.php"):
    txt = f.read_text(encoding="utf-8")
    ns = re.search(r'namespace\s+([^;]+);', txt)
    cls = re.search(r'class\s+(\w+)', txt)
    if not (ns and cls):
        continue
    fqcn = (ns.group(1).strip() + "\\" + cls.group(1)).lstrip("\\")
    fm = re.search(r'protected\s+\$fillable\s*=\s*\[(.*?)\]', txt, re.S)
    gm = re.search(r'protected\s+\$guarded\s*=\s*\[(.*?)\]', txt, re.S)
    fill = re.findall(r"'([^']+)'", fm.group(1)) if fm else []
    guard = re.findall(r"'([^']+)'", gm.group(1)) if gm else None
    model_map[fqcn] = {"fillable": fill, "restrictive": bool(fill), "guarded": guard,
                       "file": f.as_posix()}


def top_level_keys(arg):
    """Best-effort: return top-level array keys of an array literal, or None."""
    i = arg.find('[')
    if i < 0:
        return None
    depth = 0
    keys = []
    j = i
    seg_start = i
    while j < len(arg):
        c = arg[j]
        if c in "'\"":
            q = c
            j += 1
            while j < len(arg) and arg[j] != q:
                j += 1
        elif c in '[(':
            depth += 1
        elif c in '])':
            depth -= 1
            if depth == 0:
                break
        elif c == '=' and arg[j + 1:j + 2] == '>' and depth == 1:
            seg = arg[seg_start:j]
            lm = re.search(r"([A-Za-z_]\w*|'[^']+'|\"[^\"]+\")\s*$", seg.rstrip())
            if lm:
                k = lm.group(1).strip("'\"")
                if k not in keys:
                    keys.append(k)
            # advance past '=>' and its value (skip string/parens) - increment j
            j += 1
            while j < len(arg) and not (arg[j] == ',' and depth == 1):
                if arg[j] in "'\"":
                    q = arg[j]
                    j += 1
                    while j < len(arg) and arg[j] != q:
                        j += 1
                elif arg[j] in '[(':
                    depth += 1
                elif arg[j] in '])':
                    depth -= 1
                    if depth == 0:
                        break
                else:
                    j += 1
            seg_start = j + 1 if j < len(arg) else j
        j += 1
    return keys if keys else None


def build_aliases(txt):
    aliases = {}
    for m in re.finditer(r'use\s+([A-Za-z0-9_\\]+)\s*(?:as\s+([A-Za-z0-9_]+))?\s*;', txt):
        path = m.group(1)
        alias = m.group(2) or path.split('\\')[-1]
        aliases[alias] = path.lstrip('\\')
    return aliases


def resolve_token(tok, aliases):
    tok = tok.strip().rstrip(':').strip()
    if tok.startswith('\\'):
        return tok.lstrip('\\')
    if tok in aliases:
        return aliases[tok]
    return None


callexp = re.compile(r'((?:[A-Za-z_\\][\w\\]*)\s*::|->)(create|update|firstOrCreate|updateOrCreate|insert|insertOrIgnore|upsert)\s*\(', re.S)

findings = []
scanned = 0
for f in CON.rglob("*.php"):
    txt = f.read_text(encoding="utf-8")
    aliases = build_aliases(txt)
    rel = f.relative_to(CON).as_posix()
    for mt in callexp.finditer(txt):
        tok = mt.group(1).strip().rstrip('::').strip()
        # skip instance calls ($var->create) - only class::create matters for fillable
        if tok.startswith('$'):
            continue
        method = mt.group(2)
        start = mt.end()
        depth = 1
        i = start
        while i < len(txt) and depth > 0:
            if txt[i] == '(':
                depth += 1
            elif txt[i] == ')':
                depth -= 1
            i += 1
        args = txt[start:i - 1]
        if '$request->all()' in args or '$request->all(' in args:
            pt = "request->all()"
            keys = []
        elif '->validated(' in args or '$request->validated' in args:
            pt = "validated()"
            keys = []
        else:
            k = top_level_keys(args)
            if k is None:
                pt = "other"
                keys = []
            else:
                pt = "explicit"
                keys = k
        fqcn = resolve_token(tok, aliases)
        m = model_map.get(fqcn)
        if m is None:
            continue
        scanned += 1
        if pt == "request->all()":
            findings.append(("MASS", rel, fqcn, method, [], m))
            continue
        if pt != "explicit":
            continue
        if not m["restrictive"]:
            continue
        missing = [k for k in keys if k not in m["fillable"]]
        if missing:
            findings.append(("DROP", rel, fqcn, method, missing, m))

print(f"resolve hit models: {scanned}")
print(f"TRUE data-loss / mass-assignment findings: {len(findings)}\n")
for kind, rel, fqcn, method, info, m in findings:
    print(f"[{kind}] {rel} -> {fqcn}::{method}")
    if kind == "MASS":
        print(f"    uses $request->all() vs restrictive fillable {m['fillable']}")
    else:
        print(f"    submitted keys missing from fillable: {info}")
        print(f"    fillable = {m['fillable']}")
        print(f"    model file = {m['file']}")
    print()

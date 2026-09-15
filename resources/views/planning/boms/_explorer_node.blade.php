{{-- Recursive BOM Explorer node list. Renders a series of <li> items (no <ul>). --}}
@foreach ($nodes as $node)
    @php
        $kind = strtolower((string) ($node['kind'] ?? 'rm'));
        $isBranch = in_array($kind, ['fg', 'wip'], true) || !empty($node['children']);
        $knob = $kind === 'fg' ? '●' : ($kind === 'wip' ? '◐' : '◆');
        $no = $node['no'] ?? '';
        $name = $node['name'] ?? '';
        $dim = $node['dim'] ?? '';
        $qty = $node['qty'] ?? '';
        $mb = strtolower((string) ($node['mb'] ?? ''));
        $mbFree = in_array($mb, ['free', 'free_issue'], true) ? 'free' : $mb;
        $special = $node['special'] ?? '';
        $subs = (int) ($node['subs'] ?? 0);
        $machine = $node['machine'] ?? '';
        // Full node payload for JS (strip nested children to avoid shell-size abuse)
        $leafPayload = $node;
        unset($leafPayload['children']);
    @endphp
    <li class="expl-node" x-data="{ open: @js($kind === 'rm' ? false : true) }">
        @if ($isBranch)
            <div class="expl-node-row" @click="open = !open" role="button" tabindex="0"
                @keydown.enter.self="open = !open" @keydown.space.prevent.self="open = !open"
                :aria-expanded="open.toString()">
                <span class="expl-knob knob-{{ $kind }}">{{ $knob }}</span>
                <span class="expl-kind {{ $kind }}">{{ strtoupper($kind) }}</span>
                @if ($no !== '')
                    <span class="expl-part-no">{{ $no }}</span>
                @endif
                @if ($name !== '')
                    <span class="expl-part-name">{{ $name }}</span>
                @endif
                @if ($kind === 'wip' && $machine !== '')
                    <span class="expl-dim">{{ $machine }}</span>
                @endif
                @if ($qty !== '')
                    <span class="expl-qty">{{ $qty }}</span>
                @endif
                <span class="expl-arrow" x-text="open ? '−' : '+'"></span>
            </div>
            <ul class="expl-tree-list" x-show="open" x-cloak @bom-tree-set.window="open = $event.detail">
                @include('planning.boms._explorer_node', ['nodes' => $node['children'] ?? []])
            </ul>
        @else
            <div class="expl-node-row" data-leaf data-node="{{ e(json_encode($leafPayload)) }}"
                @click="$dispatch('bom-rm-detail', JSON.parse($el.dataset.node))" role="button" tabindex="0"
                @keydown.enter.self="$dispatch('bom-rm-detail', JSON.parse($el.dataset.node))">
                <span class="expl-knob knob-rm">◆</span>
                <span class="expl-kind rm">RM</span>
                @if ($no !== '')
                    <span class="expl-part-no">{{ $no }}</span>
                @endif
                @if ($name !== '')
                    <span class="expl-part-name">{{ $name }}</span>
                @endif
                @if ($dim !== '')
                    <span class="expl-dim">{{ $dim }}</span>
                @endif
                @if ($qty !== '')
                    <span class="expl-qty">{{ $qty }}</span>
                @endif
                @if ($mb !== '')
                    <span class="expl-mb {{ $mbFree }}">{{ strtoupper(str_replace('_', ' ', $mb)) }}</span>
                @endif
                @if ($special !== '')
                    <span class="expl-mb subcon">{{ $special }}</span>
                @endif
                @if ($subs > 0)
                    <span class="expl-subs-chip">⇄ {{ $subs }} subs</span>
                @endif
                <span class="expl-leaf-actions" @click.stop>
                    <button type="button" class="expl-icon-btn" title="Edit baris"
                        @click="$dispatch('bom-line-edit', JSON.parse($el.closest('[data-node]').dataset.node))">✎</button>
                    <button type="button" class="expl-icon-btn" title="Substitutenya"
                        @click="$dispatch('bom-rm-detail', JSON.parse($el.closest('[data-node]').dataset.node))">⇄</button>
                    <form method="POST" action="{{ $node['delete_url'] ?? '' }}" class="inline"
                        onsubmit="return confirm('{{ __('planning.boms.index.confirm_delete_line') }}')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="expl-icon-btn danger" title="{{ __('planning.boms.index.delete_title') }}">✕</button>
                    </form>
                </span>
            </div>
        @endif
    </li>
@endforeach
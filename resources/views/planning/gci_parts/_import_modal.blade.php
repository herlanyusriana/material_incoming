{{-- Excel import modal --}}
<div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4"
    x-show="importOpen" x-cloak @keydown.escape.window="importOpen = false">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl border border-slate-200">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
            <div class="text-sm font-semibold text-slate-900">Import Part GCI</div>
            <button type="button" class="w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50" @click="importOpen = false">&#10005;</button>
        </div>
        <form action="{{ route('planning.gci-parts.import') }}" method="POST" enctype="multipart/form-data" class="px-5 py-4 space-y-3">
            @csrf
            <input type="file" name="file" accept=".xlsx,.xls" required
                class="block w-full text-sm text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
            <div class="rounded-lg bg-slate-50 border border-slate-200 px-3 py-2 text-xs text-slate-600 space-y-1">
                <p><span class="font-semibold text-indigo-700">Kolom part:</span> customer, part_no, classification, part_name, model, status</p>
                <p><span class="font-semibold text-indigo-700">Kolom substitute:</span> component_part_no, substitute_part_no, substitute_ratio, substitute_priority, substitute_status, substitute_notes</p>
                <p class="text-amber-700">Mode upsert: part yang ada di-update, yang baru dibuat.</p>
            </div>
            <div class="flex justify-end gap-2 pt-1">
                <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-sm font-medium" @click="importOpen = false">Batal</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold">Upload</button>
            </div>
        </form>
    </div>
</div>

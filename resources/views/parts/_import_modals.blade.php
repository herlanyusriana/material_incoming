{{-- Import modals: parts & substitutes --}}
<div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4" x-show="importOpen" x-cloak @keydown.escape.window="importOpen = false">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl border border-slate-200">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
            <div class="text-sm font-semibold text-slate-900">Import Parts</div>
            <button type="button" class="w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50" @click="importOpen = false">&#10005;</button>
        </div>
        <form action="{{ route('parts.import') }}" method="POST" enctype="multipart/form-data" class="px-5 py-4 space-y-3">
            @csrf
            <input type="file" name="file" accept=".xlsx,.xls" required
                class="block w-full text-sm text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
            <div class="flex justify-end gap-2 pt-1">
                <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-sm font-medium" @click="importOpen = false">Batal</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold">Upload</button>
            </div>
        </form>
    </div>
</div>

<div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4" x-show="subImportOpen" x-cloak @keydown.escape.window="subImportOpen = false">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl border border-slate-200">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
            <div class="text-sm font-semibold text-slate-900">Import Substitute</div>
            <button type="button" class="w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50" @click="subImportOpen = false">&#10005;</button>
        </div>
        <form action="{{ route('planning.boms.substitutes.import') }}" method="POST" enctype="multipart/form-data" class="px-5 py-4 space-y-3">
            @csrf
            <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                class="block w-full text-sm text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
            <label class="flex items-center gap-2 text-xs text-slate-600">
                <input type="checkbox" name="auto_create_parts" value="1" class="rounded border-slate-300">
                Auto create RM part jika belum ada di master
            </label>
            <div class="flex justify-end gap-2 pt-1">
                <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-sm font-medium" @click="subImportOpen = false">Batal</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold">Upload</button>
            </div>
        </form>
    </div>
</div>

<x-app-layout>
    <x-slot name="header">
        {{ __('master.vendors.index.header') }}
    </x-slot>

    <div class="py-8">
        <div class="px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif
            
            @if (session('error'))
                <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl p-6 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-slate-200">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">{{ __('master.vendors.index.title') }}</h3>
                            <p class="text-sm text-slate-600 mt-1">{{ __('master.vendors.index.subtitle') }}</p>
                        </div>
                        <div class="flex items-center gap-2">
	                            <form method="GET" id="vendor-filter-form" class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                                <div class="relative w-full sm:w-64">
                                    <label for="vendor-search" class="sr-only">{{ __('master.vendors.index.search_sr') }}</label>
                                    <input type="text" id="vendor-search" name="q" value="{{ $search }}" placeholder="{{ __('master.vendors.index.search_placeholder') }}" class="w-full pl-9 pr-3 py-2 rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                                    <span class="absolute left-3 top-2.5 text-slate-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m21 21-4.35-4.35M11 18a7 7 0 1 1 0-14 7 7 0 0 1 0 14Z"/></svg>
                                    </span>
                                </div>
                                <select name="type" class="py-2 px-4 w-full sm:w-40 rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                    <option value="">{{ __('master.vendors.index.type_all') }}</option>
                                    <option value="import" @selected($type === 'import')>{{ __('master.vendors.index.type_import') }}</option>
                                    <option value="local" @selected($type === 'local')>{{ __('master.vendors.index.type_local') }}</option>
                                    <option value="tolling" @selected($type === 'tolling')>{{ __('master.vendors.index.type_tolling') }}</option>
                                </select>
                                <select name="country" class="py-2 px-4 w-full sm:w-36 rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                    <option value="">{{ __('master.vendors.index.country_all') }}</option>
                                    @foreach(($countryOptions ?? collect()) as $countryOption)
                                        <option value="{{ $countryOption }}" @selected($country === $countryOption)>{{ $countryOption }}</option>
                                    @endforeach
                                </select>
                                <select name="status" class="py-2 px-4 w-full sm:w-44 rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                    <option value="">{{ __('master.vendors.index.status_all') }}</option>
                                    <option value="active" @selected($status === 'active')>{{ __('master.vendors.index.status_active') }}</option>
                                    <option value="inactive" @selected($status === 'inactive')>{{ __('master.vendors.index.status_inactive') }}</option>
                                </select>
                                <button class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium transition-colors">{{ __('master.vendors.index.filter') }}</button>
                                <a href="{{ route('vendors.index') }}" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg text-sm font-medium transition-colors hover:bg-slate-50">{{ __('master.vendors.index.reset') }}</a>
                            </form>
                            <a href="{{ route('vendors.export') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-xl transition-colors text-sm whitespace-nowrap">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0-3-3m3 3 3-3m2 8H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z" />
                                </svg>
                                {{ __('master.vendors.index.export') }}
                            </a>
                            <button type="button" onclick="document.getElementById('import-vendor-modal').classList.remove('hidden')" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-xl transition-colors text-sm whitespace-nowrap">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16v-6m0 0 3 3m-3-3-3 3m8-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                                {{ __('master.vendors.index.import') }}
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto border border-slate-200 rounded-xl">
                        <table class="min-w-full text-sm">
	                            <thead class="bg-gradient-to-r from-slate-50 to-slate-100">
	                                <tr class="text-slate-600 text-xs uppercase tracking-wider">
	                                    <th class="px-4 py-3 text-left font-semibold">{{ __('master.vendors.index.th_name') }}</th>
	                                    <th class="px-4 py-3 text-left font-semibold">{{ __('master.vendors.index.th_type') }}</th>
	                                    <th class="px-4 py-3 text-left font-semibold">{{ __('master.vendors.index.th_country') }}</th>
	                                    <th class="px-4 py-3 text-left font-semibold">{{ __('master.vendors.index.th_contact') }}</th>
	                                    <th class="px-4 py-3 text-left font-semibold">{{ __('master.vendors.index.th_email') }}</th>
	                                    <th class="px-4 py-3 text-left font-semibold">{{ __('master.vendors.index.th_phone') }}</th>
	                                    <th class="px-4 py-3 text-left font-semibold">{{ __('master.vendors.index.th_status') }}</th>
	                                    <th class="px-4 py-3 text-right font-semibold">{{ __('master.vendors.index.th_actions') }}</th>
	                                </tr>
	                            </thead>
	                            <tbody class="divide-y divide-slate-100 bg-white">
	                                @forelse ($vendors as $vendor)
	                                    <tr class="hover:bg-slate-50 transition-colors">
	                                        <td class="px-4 py-4 font-semibold text-slate-900">
	                                            <div class="flex items-center gap-2">
	                                                <span>{{ $vendor->vendor_name }}</span>
	                                                @if (!$vendor->is_complete)
	                                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-1 text-[11px] font-semibold text-amber-700 border border-amber-200"
	                                                        title="{{ __('master.vendors.index.incomplete_title', ['fields' => implode(', ', $vendor->missing_fields)]) }}">
	                                                        {{ __('master.vendors.index.incomplete') }}
	                                                    </span>
	                                                @endif
	                                            </div>
	                                        </td>
	                                        <td class="px-4 py-4 text-slate-700 font-semibold">{{ strtoupper($vendor->vendor_type ?? 'IMPORT') }}</td>
	                                        <td class="px-4 py-4 text-slate-600">{{ $vendor->country_code ?? '-' }}</td>
	                                        <td class="px-4 py-4 text-slate-700">{{ $vendor->contact_person }}</td>
	                                        <td class="px-4 py-4 text-slate-600">{{ $vendor->email }}</td>
	                                        <td class="px-4 py-4 text-slate-600">{{ $vendor->phone }}</td>
                                        <td class="px-4 py-4">
                                            <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full {{ $vendor->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600' }}">
                                                {{ $vendor->status === 'active' ? __('master.vendors.index.status_active') : __('master.vendors.index.status_inactive') }}
                                            </span>
                                        </td>
	                                        <td class="px-4 py-4 text-right">
	                                            <div class="flex justify-end gap-3">
	                                                <a href="{{ route('vendors.edit', $vendor) }}" class="text-indigo-600 hover:text-indigo-700 font-medium">{{ __('master.vendors.index.edit') }}</a>
	                                                <form method="POST" action="{{ route('vendors.destroy', $vendor) }}" onsubmit="return confirm(@js(__('master.vendors.index.delete_confirm')))">
	                                                    @csrf
	                                                    @method('DELETE')
	                                                    <button class="text-red-600 hover:text-red-700 font-medium">{{ __('master.vendors.index.delete') }}</button>
	                                                </form>
	                                            </div>
	                                        </td>
	                                    </tr>
	                                @empty
	                                    <tr>
	                                        <td colspan="8" class="px-4 py-12 text-center text-slate-500">{{ __('master.vendors.index.empty') }}</td>
	                                    </tr>
	                                @endforelse
	                            </tbody>
	                        </table>
	                    </div>

                    <div class="mt-4">{{ $vendors->links() }}</div>
                </div>
        </div>
    </div>

    <!-- Import Modal -->
    <div id="import-vendor-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                <h3 class="text-lg font-bold text-slate-900">{{ __('master.vendors.index.import_title') }}</h3>
                <button type="button" onclick="document.getElementById('import-vendor-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <form action="{{ route('vendors.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label for="vendor-import-file" class="block text-sm font-medium text-slate-700 mb-2">{{ __('master.vendors.index.import_label') }}</label>
                    <input type="file" id="vendor-import-file" name="file" accept=".xlsx,.xls" required class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    <p class="mt-2 text-xs text-slate-500">{{ __('master.vendors.index.import_hint') }}</p>
                </div>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                    <p class="text-xs text-blue-800 font-medium mb-1">{{ __('master.vendors.index.import_required') }}</p>
                    <p class="text-xs text-blue-700">vendor_name, vendor_type, country_code, contact_person, email, phone, address, bank_account, status</p>
                    <p class="text-xs text-blue-600 mt-1">{{ __('master.vendors.index.import_tip') }}</p>
                </div>
                
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="document.getElementById('import-vendor-modal').classList.add('hidden')" class="flex-1 px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition-colors">
                        {{ __('master.vendors.index.cancel') }}
                    </button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-xl transition-colors">
                        {{ __('master.vendors.index.upload_import') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const form = document.getElementById('vendor-filter-form');
            if (!form) return;

            const searchInput = form.querySelector('input[name="q"]');
            const statusSelect = form.querySelector('select[name="status"]');
            const typeSelect = form.querySelector('select[name="type"]');
            const countrySelect = form.querySelector('select[name="country"]');

            let t = null;
            function submitDebounced() {
                window.clearTimeout(t);
                t = window.setTimeout(() => form.requestSubmit(), 350);
            }

            if (searchInput) {
                searchInput.addEventListener('input', submitDebounced);
            }
            if (statusSelect) {
                statusSelect.addEventListener('change', () => form.requestSubmit());
            }
            if (typeSelect) {
                typeSelect.addEventListener('change', () => form.requestSubmit());
            }
            if (countrySelect) {
                countrySelect.addEventListener('change', () => form.requestSubmit());
            }
        })();
    </script>
</x-app-layout>

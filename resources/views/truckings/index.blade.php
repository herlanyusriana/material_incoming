<x-app-layout>
    <x-slot name="header">
        {{ __('master.truckings.index.header') }}
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="grid lg:grid-cols-3 gap-6">
                <div class="bg-white shadow-lg border border-slate-200 rounded-2xl p-6 space-y-4">
                    <div class="pb-3 border-b border-slate-200">
                        <h3 class="text-lg font-bold text-slate-900">{{ __('master.truckings.index.form_title') }}</h3>
                        <p class="text-sm text-slate-600 mt-1">{{ __('master.truckings.index.form_subtitle') }}</p>
                    </div>

                    <form method="POST" action="{{ route('truckings.store') }}" class="space-y-4">
                        @csrf
                        <div class="space-y-1">
                            <x-input-label for="company_name" :value="__('master.truckings.index.company_name')" />
                            <x-text-input id="company_name" name="company_name" type="text" placeholder="{{ __('master.truckings.index.company_placeholder') }}" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('company_name')" class="mt-1" />
                        </div>
                        <div class="space-y-1">
                            <x-input-label for="address" :value="__('master.truckings.index.address')" />
                            <textarea id="address" name="address" rows="3" placeholder="{{ __('master.truckings.index.address_placeholder') }}" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required></textarea>
                            <x-input-error :messages="$errors->get('address')" class="mt-1" />
                        </div>
                        <div class="space-y-1">
                            <x-input-label for="phone" :value="__('master.truckings.index.phone')" />
                            <x-text-input id="phone" name="phone" type="text" placeholder="{{ __('master.truckings.index.phone_placeholder') }}" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                        </div>
                        <div class="space-y-1">
                            <x-input-label for="email" :value="__('master.truckings.index.email')" />
                            <x-text-input id="email" name="email" type="email" placeholder="{{ __('master.truckings.index.email_placeholder') }}" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('email')" class="mt-1" />
                        </div>
                        <div class="space-y-1">
                            <x-input-label for="contact_person" :value="__('master.truckings.index.contact_person')" />
                            <x-text-input id="contact_person" name="contact_person" type="text" placeholder="{{ __('master.truckings.index.contact_placeholder') }}" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('contact_person')" class="mt-1" />
                        </div>
                        <div class="flex justify-end pt-2">
                            <button type="submit" class="w-full px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition-colors shadow-sm">{{ __('master.truckings.index.submit') }}</button>
                        </div>
                    </form>
                </div>

                <div class="lg:col-span-2 bg-white shadow-lg border border-slate-200 rounded-2xl p-6 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-slate-200">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">{{ __('master.truckings.index.list_title') }}</h3>
                            <p class="text-sm text-slate-600 mt-1">{{ __('master.truckings.index.list_subtitle') }}</p>
                        </div>
                        <form method="GET" class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                            <div class="relative w-full sm:w-64">
                                <input type="text" name="q" value="{{ $search }}" placeholder="{{ __('master.truckings.index.search_placeholder') }}" class="w-full pl-9 pr-3 py-2 rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                                <span class="absolute left-3 top-2.5 text-slate-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m21 21-4.35-4.35M11 18a7 7 0 1 1 0-14 7 7 0 0 1 0 14Z"/></svg>
                                </span>
                            </div>
                            <select name="status" class="py-2 px-4 w-full sm:w-44 rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="">{{ __('master.truckings.index.status_all') }}</option>
                                <option value="active" @selected($status === 'active')>{{ __('master.truckings.index.status_active') }}</option>
                                <option value="inactive" @selected($status === 'inactive')>{{ __('master.truckings.index.status_inactive') }}</option>
                            </select>
                            <button class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium transition-colors">{{ __('master.truckings.index.filter') }}</button>
                        </form>
                    </div>

                    <div class="overflow-x-auto border border-slate-200 rounded-xl">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gradient-to-r from-slate-50 to-slate-100">
                                <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                    <th class="px-4 py-3 text-left font-semibold">{{ __('master.truckings.index.th_company') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold">{{ __('master.truckings.index.th_contact') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold">{{ __('master.truckings.index.th_phone') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold">{{ __('master.truckings.index.th_email') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold">{{ __('master.truckings.index.th_status') }}</th>
                                    <th class="px-4 py-3 text-right font-semibold">{{ __('master.truckings.index.th_actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse ($truckings as $trucking)
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-4 py-3">
                                            <span class="font-medium text-slate-900">{{ $trucking->company_name }}</span>
                                            <div class="text-xs text-slate-500 mt-0.5 line-clamp-1">{{ $trucking->address }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-slate-700">{{ $trucking->contact_person ?: '-' }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ $trucking->phone ?: '-' }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ $trucking->email ?: '-' }}</td>
                                        <td class="px-4 py-3">
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $trucking->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-800' }}">
                                                {{ $trucking->status === 'active' ? __('master.truckings.index.status_active') : __('master.truckings.index.status_inactive') }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right space-x-2">
                                            <a href="{{ route('truckings.edit', $trucking) }}" class="inline-flex items-center text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                                                {{ __('master.truckings.index.edit') }}
                                            </a>
                                            <form method="POST" action="{{ route('truckings.destroy', $trucking) }}" class="inline-block" onsubmit="return confirm(@js(__('master.truckings.index.delete_confirm')))">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">{{ __('master.truckings.index.delete') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-8 text-center text-slate-500">
                                            {{ __('master.truckings.index.empty') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($truckings->hasPages())
                        <div class="pt-4">
                            {{ $truckings->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

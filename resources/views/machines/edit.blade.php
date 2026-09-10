<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">{{ __('master.machines.edit.title') }}</h2>
            <p class="text-sm text-slate-500">{{ __('master.machines.edit.subtitle') }}</p>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg p-6">
                <form method="POST" action="{{ route('machines.update', $machine) }}">
                    @method('PUT')
                    @include('machines._form')
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

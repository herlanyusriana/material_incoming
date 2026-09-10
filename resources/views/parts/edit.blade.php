<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">{{ __('master.parts.edit.title') }}</h2>
            <p class="text-sm text-slate-500">{{ __('master.parts.edit.subtitle') }}</p>
        </div>
    </x-slot>

    <div class="py-6">
        <form method="POST" action="{{ route('parts.update', $part) }}">
            @method('PUT')
            @include('parts._form')
        </form>
    </div>
</x-app-layout>

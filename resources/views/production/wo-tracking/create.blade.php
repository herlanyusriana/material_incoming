<x-app-layout>
    <x-slot name="header">{{ __('wo_tracking.create_title') }}</x-slot>
    @include('production.wo-tracking._form', ['wo' => null, 'prefill' => $prefill ?? null])
</x-app-layout>

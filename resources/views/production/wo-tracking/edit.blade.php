<x-app-layout>
    <x-slot name="header">{{ __('wo_tracking.edit_title') }}</x-slot>
    @include('production.wo-tracking._form', ['wo' => $wo])
</x-app-layout>

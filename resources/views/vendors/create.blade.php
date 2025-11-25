<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">New Vendor</h2>
            <p class="text-sm text-gray-500">Add a supplier profile.</p>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg p-6">
                <form method="POST" action="{{ route('vendors.store') }}">
                    @include('vendors._form')
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

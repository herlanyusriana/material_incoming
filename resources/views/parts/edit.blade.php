<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Part</h2>
            <p class="text-sm text-gray-500">Update part details.</p>
        </div>
    </x-slot>

    <div class="py-6">
        <form method="POST" action="{{ route('parts.update', $part) }}">
            @method('PUT')
            @include('parts._form')
        </form>
    </div>
</x-app-layout>

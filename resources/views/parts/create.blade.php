<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">New Part</h2>
            <p class="text-sm text-gray-500">Register a part number.</p>
        </div>
    </x-slot>

    <div class="py-6">
        <form method="POST" action="{{ route('parts.store') }}" id="create-part-form">
            @include('parts._form')
            <input type="hidden" name="confirm_duplicate" id="confirm_duplicate" value="0">
        </form>
    </div>

    @if(session('duplicate_warning'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (confirm(@json(session('duplicate_warning')))) {
                    document.getElementById('confirm_duplicate').value = '1';
                    document.getElementById('create-part-form').submit();
                }
            });
        </script>
    @endif
</x-app-layout>

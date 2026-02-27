@csrf
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <div>
        <x-input-label for="code" value="Machine Code" />
        <x-text-input id="code" name="code" type="text" class="mt-1 w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-4 py-2.5 text-sm uppercase" required maxlength="50" placeholder="e.g. TPL-01" value="{{ old('code', $machine->code ?? '') }}" />
        <x-input-error :messages="$errors->get('code')" class="mt-2" />
    </div>
    <div class="md:col-span-2">
        <x-input-label for="name" value="Machine Name" />
        <x-text-input id="name" name="name" type="text" class="mt-1 w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-4 py-2.5 text-sm" required maxlength="255" placeholder="e.g. TPL Comp Base" value="{{ old('name', $machine->name ?? '') }}" />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="group_name" value="Group Name" />
        <x-text-input id="group_name" name="group_name" type="text" class="mt-1 w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-4 py-2.5 text-sm" maxlength="255" placeholder="e.g. Press, Assembly" value="{{ old('group_name', $machine->group_name ?? '') }}" />
        <x-input-error :messages="$errors->get('group_name')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="cycle_time" value="Cycle Time" />
        <div class="mt-1 flex gap-2">
            <x-text-input id="cycle_time" name="cycle_time" type="number" min="0" step="0.01" class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-4 py-2.5 text-sm" placeholder="0" value="{{ old('cycle_time', $machine->cycle_time ?? 0) }}" />
            <select name="cycle_time_unit" id="cycle_time_unit" class="rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-4 py-2.5 text-sm min-w-[130px]">
                <option value="seconds" {{ old('cycle_time_unit', $machine->cycle_time_unit ?? 'seconds') === 'seconds' ? 'selected' : '' }}>Seconds</option>
                <option value="minutes" {{ old('cycle_time_unit', $machine->cycle_time_unit ?? 'seconds') === 'minutes' ? 'selected' : '' }}>Minutes</option>
                <option value="hours" {{ old('cycle_time_unit', $machine->cycle_time_unit ?? 'seconds') === 'hours' ? 'selected' : '' }}>Hours</option>
            </select>
        </div>
        <x-input-error :messages="$errors->get('cycle_time')" class="mt-2" />
        <x-input-error :messages="$errors->get('cycle_time_unit')" class="mt-2" />
    </div>
    <div class="flex items-end">
        <label class="flex items-center gap-3 cursor-pointer">
            <input type="checkbox" name="is_active" value="1"
                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 h-5 w-5"
                {{ old('is_active', $machine->is_active ?? true) ? 'checked' : '' }}>
            <span class="text-sm font-medium text-gray-700">Active</span>
        </label>
    </div>
</div>

<div class="mt-8 flex items-center justify-end gap-3">
    <a href="{{ route('machines.index') }}" class="border border-gray-300 text-gray-700 rounded-xl px-4 py-2 hover:bg-gray-50">Cancel</a>
    <x-primary-button class="bg-indigo-600 hover:bg-indigo-700 rounded-xl px-6 py-3 text-white">Save</x-primary-button>
</div>

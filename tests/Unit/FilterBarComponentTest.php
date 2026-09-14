<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class FilterBarComponentTest extends TestCase
{
    public function test_filter_bar_keeps_advanced_filters_hidden_until_requested(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-filter-bar>
                <x-slot name="primary">Search</x-slot>
                <x-slot name="advanced">Status and date</x-slot>
            </x-filter-bar>
        BLADE);

        $this->assertStringContainsString('More filters', $html);
        $this->assertStringContainsString('Search', $html);
        $this->assertStringContainsString('Status and date', $html);
        $this->assertStringContainsString('x-show="advancedOpen"', $html);
    }
}

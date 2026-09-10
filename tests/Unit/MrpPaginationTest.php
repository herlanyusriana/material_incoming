<?php

namespace Tests\Unit;

use App\Http\Controllers\Planning\MrpController;
use Illuminate\Http\Request;
use Tests\TestCase;

class MrpPaginationTest extends TestCase
{
    public function test_mrp_rows_are_paginated_with_a_dedicated_query_parameter(): void
    {
        $rows = collect(range(1, 26))->map(fn (int $id) => ['id' => $id])->all();
        $request = Request::create('/planning/mrp', 'GET', [
            'month' => '2026-09',
            'family' => 'Comp Base',
            'buy_page' => 2,
        ]);

        $method = new \ReflectionMethod(MrpController::class, 'paginateRows');
        $method->setAccessible(true);
        $paginator = $method->invoke(new MrpController(), $rows, $request, 'buy_page', 25);

        $this->assertSame(2, $paginator->currentPage());
        $this->assertSame(26, $paginator->total());
        $this->assertSame([['id' => 26]], $paginator->items());
        $this->assertStringContainsString('month=2026-09', $paginator->url(1));
        $this->assertStringContainsString('family=Comp%20Base', $paginator->url(1));
        $this->assertStringContainsString('buy_page=1', $paginator->url(1));
    }
}

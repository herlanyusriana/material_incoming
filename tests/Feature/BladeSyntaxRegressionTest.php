<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class BladeSyntaxRegressionTest extends TestCase
{
    public function test_arrival_edit_compiles_to_valid_php(): void
    {
        $this->assertBladeCompilesToValidPhp('resources/views/arrivals/edit.blade.php');
    }

    public function test_line_stock_label_compiles_to_valid_php(): void
    {
        $this->assertBladeCompilesToValidPhp('resources/views/warehouse/labels/line_stock_label.blade.php');
    }

    private function assertBladeCompilesToValidPhp(string $relativePath): void
    {
        $compiled = Blade::compileString(file_get_contents(base_path($relativePath)));
        $temporaryFile = tempnam(sys_get_temp_dir(), 'blade-lint-');

        file_put_contents($temporaryFile, $compiled);

        try {
            $process = new Process([PHP_BINARY, '-l', $temporaryFile]);
            $process->run();

            $this->assertTrue(
                $process->isSuccessful(),
                $relativePath." compiled to invalid PHP:\n".$process->getErrorOutput().$process->getOutput()
            );
        } finally {
            @unlink($temporaryFile);
        }
    }
}

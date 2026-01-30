<?php

namespace App\View\Compilers;

use Illuminate\View\Compilers\BladeCompiler;

class ResettingBladeCompiler extends BladeCompiler
{
    public function compileString($value)
    {
        $this->forElseCounter = 0;

        return parent::compileString($value);
    }
}


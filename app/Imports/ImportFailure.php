<?php

namespace App\Imports;

final class ImportFailure
{
    public function __construct(
        private readonly int $rowNumber,
        private readonly array $messages,
    ) {
    }

    public function row(): int
    {
        return $this->rowNumber;
    }

    /**
     * @return array<int, string>
     */
    public function errors(): array
    {
        return $this->messages;
    }
}


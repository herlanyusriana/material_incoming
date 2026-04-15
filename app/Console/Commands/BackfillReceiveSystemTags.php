<?php

namespace App\Console\Commands;

use App\Models\Receive;
use Illuminate\Console\Command;

class BackfillReceiveSystemTags extends Command
{
    protected $signature = 'receives:backfill-system-tags {--normalize-existing : Normalize existing tags to uppercase trimmed values too}';

    protected $description = 'Backfill missing receive tags with consistent internal system tags.';

    public function handle(): int
    {
        $updatedMissing = 0;
        $normalizedExisting = 0;
        $normalizeExisting = (bool) $this->option('normalize-existing');

        Receive::query()
            ->orderBy('id')
            ->chunkById(200, function ($receives) use (&$updatedMissing, &$normalizedExisting, $normalizeExisting) {
                foreach ($receives as $receive) {
                    $currentTag = is_string($receive->tag) ? trim($receive->tag) : '';

                    if ($currentTag === '') {
                        $receive->ensureSystemTag();
                        $updatedMissing++;
                        continue;
                    }

                    if ($normalizeExisting) {
                        $normalizedTag = strtoupper($currentTag);
                        if ($normalizedTag !== $receive->tag) {
                            $receive->forceFill(['tag' => $normalizedTag])->saveQuietly();
                            $normalizedExisting++;
                        }
                    }
                }
            });

        $this->info("Receive tags generated: {$updatedMissing}");
        if ($normalizeExisting) {
            $this->info("Receive tags normalized: {$normalizedExisting}");
        }

        return self::SUCCESS;
    }
}

<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait LogsActivity
{
    protected function logActivity(string $action, string $description = '', array $data = []): void
    {
        $user = auth()->user();
        $username = $user?->username ?? 'guest';

        $context = array_filter([
            'user_id' => $user?->id,
            'ip' => request()->ip(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'data' => $data ?: null,
        ]);

        Log::channel('activity')->info("[{$username}] {$action}" . ($description ? " | {$description}" : ''), $context);
    }

    protected function logActivityError(string $action, string $error, array $data = []): void
    {
        $user = auth()->user();
        $username = $user?->username ?? 'guest';

        $context = array_filter([
            'user_id' => $user?->id,
            'ip' => request()->ip(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'error' => $error,
            'data' => $data ?: null,
        ]);

        Log::channel('activity')->error("[{$username}] {$action}", $context);
    }
}

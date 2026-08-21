<?php

namespace App\Console\Commands;

use App\Models\IdempotencyKey;
use Illuminate\Console\Command;

class CleanupIdempotencyKeys extends Command
{
    protected $signature = 'idempotency:cleanup';

    protected $description = 'Remove expired idempotency keys';

    public function handle(): int
    {
        $deleted = IdempotencyKey::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->delete();

        $this->info("Deleted {$deleted} expired idempotency keys.");

        return self::SUCCESS;
    }
}
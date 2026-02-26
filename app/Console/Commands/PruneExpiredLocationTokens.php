<?php

namespace App\Console\Commands;

use App\Models\ServiceRequest;
use Illuminate\Console\Command;

class PruneExpiredLocationTokens extends Command
{
    protected $signature = 'tokens:prune {--days=7 : Clear tokens older than this many days}';

    protected $description = 'Clear expired location-sharing tokens from service requests';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $count = ServiceRequest::query()
            ->whereNotNull('location_token')
            ->where('location_token_expires_at', '<', now()->subDays($days))
            ->update([
                'location_token'            => null,
                'location_token_expires_at' => null,
            ]);

        $this->info("Pruned {$count} expired location token(s) older than {$days} day(s).");

        return self::SUCCESS;
    }
}

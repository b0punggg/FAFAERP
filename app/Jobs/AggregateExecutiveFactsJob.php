<?php

namespace App\Jobs;

use App\Services\Executive\EisFactsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AggregateExecutiveFactsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(EisFactsService $facts): void
    {
        $facts->aggregateRange(now()->subDays(13)->toDateString(), now()->toDateString());
    }
}

<?php

namespace App\Listeners;

use App\Events\AlertCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Services\Notifications\AlertNotificationService;

class HandleAlertCreated implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public int $timeout = 30;

    public array $backoff = [10, 30, 60];

    /**
     * Create the event listener.
     */
    public function __construct(
        private readonly ?AlertNotificationService $notificationService = null
    )
    {
    }

    /**
     * Handle the event.
     */
    public function handle(AlertCreated $event): void
    {
        ($this->notificationService ?? app(AlertNotificationService::class))
            ->send($event->alert);
    }
}

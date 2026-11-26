<?php

use App\Jobs\Generations\DispatchWaitingGenerations;
use App\Jobs\Generations\SyncWithProvider;
use App\Jobs\Webhooks\RetryUnprocessedWebhookEvents;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| Backup jobs in case something was missed:
| - every minute, check on late pictures (the only one that calls Pixelforge,
|   and only when a picture is late)
| - every minute, send waiting pictures if a slot is free
| - every 5 minutes, retry webhook messages that were never handled
| Only one copy of each job runs at a time.
*/
Schedule::job(new SyncWithProvider)->everyMinute()->onOneServer();
Schedule::job(new DispatchWaitingGenerations)->everyMinute()->onOneServer();
Schedule::job(new RetryUnprocessedWebhookEvents)->everyFiveMinutes()->onOneServer();

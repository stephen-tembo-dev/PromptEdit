<?php

use App\Http\Controllers\Webhooks\PixelforgeWebhookController;
use Illuminate\Support\Facades\Route;

/*
| Machine-to-machine routes. They skip the web middleware: no session, no
| cookies and no CSRF token. Pixelforge signs each message instead, and the
| controller checks that signature.
*/

Route::post('/webhooks/pixelforge', PixelforgeWebhookController::class)->name('webhooks.pixelforge');

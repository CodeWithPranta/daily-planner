<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Queued email-verification notification.
 *
 * Sending the verification mail over SMTP can take several seconds, which
 * blocks the registration / "resend verification email" request when the
 * notification is dispatched synchronously. By implementing {@see ShouldQueue}
 * the notification is pushed onto the configured queue connection (see
 * QUEUE_CONNECTION in .env) so the HTTP request returns to the browser
 * immediately, and a queue worker (already launched by "php artisan dev" via
 * `queue:listen`) sends the actual email in the background.
 */
class VerifyEmail extends BaseVerifyEmail implements ShouldQueue
{
    /**
     * Number of times the queued notification may be attempted before failing.
     */
    public int $tries = 5;

    /**
     * Number of seconds to wait before retrying after a failure.
     */
    public int $backoff = 10;

    /**
     * Maximum number of seconds the job is allowed to run before timing out.
     */
    public int $timeout = 30;
}

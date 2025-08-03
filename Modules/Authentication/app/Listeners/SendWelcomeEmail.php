<?php

namespace Modules\Authentication\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Authentication\Events\NewUser;
use Modules\Authentication\Notifications\WelcomeNotification;

class SendWelcomeEmail implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(NewUser $event): void
    {
        $event->user->notify(new WelcomeNotification());
    }
}

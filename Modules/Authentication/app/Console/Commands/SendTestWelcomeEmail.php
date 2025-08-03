<?php

namespace Modules\Authentication\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Modules\Authentication\Notifications\WelcomeNotification;

class SendTestWelcomeEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auth:send-test-welcome-email {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test welcome email to a user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User with email {$email} not found.");
            return 1;
        }
        
        try {
            $user->notify(new WelcomeNotification());
            $this->info("Welcome email sent successfully to {$email}");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to send welcome email: " . $e->getMessage());
            return 1;
        }
    }
} 
# Welcome Email Functionality

This module now includes automatic welcome email functionality that sends a personalized welcome email to new users when they register.

## How It Works

1. **Event**: When a user registers (either as a customer or vendor), a `NewUser` event is dispatched
2. **Listener**: The `SendWelcomeEmail` listener catches this event and sends a welcome email
3. **Notification**: The `WelcomeNotification` creates a personalized welcome email

## Files Modified/Created

### Events
- `app/Events/NewUser.php` - Event that is dispatched when a new user registers

### Listeners
- `app/Listeners/SendWelcomeEmail.php` - Listener that sends the welcome email

### Notifications
- `app/Notifications/WelcomeNotification.php` - Creates the welcome email content

### Providers
- `app/Providers/EventServiceProvider.php` - Registers the event-listener mapping
- `module.json` - Added EventServiceProvider to the providers array

### Repositories
- `app/Repositories/AuthenticationRepository.php` - Added event dispatch to register and vendorRegister methods

### Console Commands
- `app/Console/Commands/SendTestWelcomeEmail.php` - Command to manually test welcome emails

### Tests
- `tests/Feature/WelcomeEmailTest.php` - Automated tests for welcome email functionality

## Email Content

The welcome email includes:
- Personalized greeting with user's name
- Welcome message
- Call-to-action button to get started
- Support information
- App branding

## Testing

### Manual Testing
```bash
# Send a test welcome email to a user
php artisan auth:send-test-welcome-email user@example.com
```

### Automated Testing
```bash
# Run the welcome email tests
php artisan test --filter=WelcomeEmailTest
```

## Configuration

Make sure your mail configuration is properly set up in your `.env` file:

```env
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourapp.com
MAIL_FROM_NAME="${APP_NAME}"
```

## Queue Support

The notification implements `ShouldQueue`, so it will be processed in the background if you have queue workers running:

```bash
# Start queue workers
php artisan queue:work
```

## Customization

To customize the welcome email content, edit the `toMail()` method in `WelcomeNotification.php`.

## Troubleshooting

1. **Email not sending**: Check your mail configuration and queue workers
2. **Event not firing**: Ensure the EventServiceProvider is properly registered
3. **Listener not working**: Check that the event-listener mapping is correct in EventServiceProvider
4. **Linter errors**: The User model has the necessary traits (HasApiTokens, HasRoles, Notifiable) - these are false positives 
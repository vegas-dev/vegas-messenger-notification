# Vegas Messenger Notification

Unified Telegram and MAX notification channels for Laravel. Each channel can independently send directly, use an external relay, or be disabled.

The package contains no relay endpoint, bot token, chat ID, or project secret. All values are configured by the consuming application.

## Installation

```bash
composer require vegas/messenger-notification
php artisan vendor:publish --tag=messenger-config
```

## Configuration

Direct Telegram and relay MAX:

```dotenv
TELEGRAM_NOTIFICATION_DRIVER=direct
TELEGRAM_BOT_TOKEN=
TELEGRAM_CHANNEL_DEFAULT_ID=
TELEGRAM_PROXY=

MAX_NOTIFICATION_DRIVER=vegas-services

VEGAS_SERVICES_URL=
VEGAS_SERVICES_PROJECT=
VEGAS_SERVICES_SIGNING_KEY=
```

Supported drivers are `direct`, `vegas-services`, and `off`. An empty driver also disables the channel and is the default. If `VEGAS_SERVICES_SIGNING_KEY` is empty, a stable signing key is derived from the application's `APP_KEY`.

## Laravel notifications

```php
use Vegas\MessengerNotification\Channels\MaxChannel;
use Vegas\MessengerNotification\Channels\TelegramChannel;
use Vegas\MessengerNotification\Messages\MessengerMessage;

public function via($notifiable)
{
    return [TelegramChannel::class, MaxChannel::class];
}

public function toMessenger($notifiable)
{
    return MessengerMessage::create('*New order*')
        ->idempotencyKey('order-created:' . $notifiable->id)
        ->button('Open', route('orders.show', $notifiable));
}
```

## Documents

```php
use Vegas\MessengerNotification\Messages\MessengerMessage;
use Vegas\MessengerNotification\MessengerNotifier;

app(MessengerNotifier::class)->send(
    MessengerMessage::create('Competitor prices changed.')
        ->idempotencyKey('competitor-prices:' . hash_file('sha256', $path))
        ->document($path, 'prices.xls', 'application/vnd.ms-excel')
);
```

## Diagnostics

```bash
php artisan messenger:signing-key
php artisan messenger:test all
php artisan messenger:test telegram
php artisan messenger:test max
```

## Relay contract

Relay requests are sent to `VEGAS_SERVICES_URL/api/v1/notifications` as signed JSON. The HMAC-SHA256 signature covers the timestamp, project slug, and exact request body. Document contents are included as Base64 and should be limited by the relay server.

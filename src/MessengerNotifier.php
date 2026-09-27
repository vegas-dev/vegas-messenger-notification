<?php

namespace Vegas\MessengerNotification;

use Illuminate\Notifications\AnonymousNotifiable;
use Vegas\MessengerNotification\Messages\MessengerMessage;
use Vegas\MessengerNotification\Notifications\GenericMessengerNotification;

class MessengerNotifier
{
	public function send(MessengerMessage $message, array $channels = null)
	{
		if (!$message->idempotencyKey) {
			$message->idempotencyKey('message:' . bin2hex(random_bytes(16)));
		}

		$channels = $channels ?: ['telegram', 'max'];
		$results = [];

		foreach ($channels as $channel) {
			if (app(MessengerTransportResolver::class)->isDisabled($channel)) {
				continue;
			}

			$results[$channel] = (new AnonymousNotifiable())
				->notifyNow(new GenericMessengerNotification($channel, $message));
		}

		return $results;
	}
}

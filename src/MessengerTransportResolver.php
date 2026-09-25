<?php

namespace Vegas\MessengerNotification;

use InvalidArgumentException;
use Vegas\MessengerNotification\Contracts\MessengerTransport;
use Vegas\MessengerNotification\Transports\MaxDirectTransport;
use Vegas\MessengerNotification\Transports\NullTransport;
use Vegas\MessengerNotification\Transports\TelegramDirectTransport;
use Vegas\MessengerNotification\Transports\VegasServicesTransport;

class MessengerTransportResolver
{
	public function resolve($channel)
	{
		$driver = config('messenger.channels.' . $channel . '.driver', 'off');

		if ($driver === 'direct') {
			$class = $channel === 'telegram' ? TelegramDirectTransport::class : MaxDirectTransport::class;

			return app($class);
		}

		if ($driver === 'vegas-services') {
			return app(VegasServicesTransport::class);
		}

		if ($driver === 'off') {
			return app(NullTransport::class);
		}

		throw new InvalidArgumentException('Unsupported ' . $channel . ' notification driver: ' . $driver);
	}
}

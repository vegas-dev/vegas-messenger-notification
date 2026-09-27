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
		$driver = $this->driver($channel);

		if ($driver === 'direct') {
			$class = $channel === 'telegram' ? TelegramDirectTransport::class : MaxDirectTransport::class;

			return app($class);
		}

		if ($driver === 'vegas-services') {
			return app(VegasServicesTransport::class);
		}

		if ($this->isDisabled($channel)) {
			return app(NullTransport::class);
		}

		throw new InvalidArgumentException('Unsupported ' . $channel . ' notification driver: ' . $driver);
	}

	public function driver($channel)
	{
		return trim((string) config('messenger.channels.' . $channel . '.driver', ''));
	}

	public function isDisabled($channel)
	{
		return in_array($this->driver($channel), ['', 'off'], true);
	}
}

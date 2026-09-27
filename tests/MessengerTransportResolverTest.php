<?php

namespace Vegas\MessengerNotification\Tests;

use Vegas\MessengerNotification\Messages\MessengerMessage;
use Vegas\MessengerNotification\MessengerNotifier;
use Vegas\MessengerNotification\MessengerTransportResolver;
use Vegas\MessengerNotification\Transports\NullTransport;

class MessengerTransportResolverTest extends TestCase
{
	public function testEmptyDriverDisablesChannel()
	{
		config(['messenger.channels.telegram.driver' => '']);

		$resolver = app(MessengerTransportResolver::class);

		$this->assertTrue($resolver->isDisabled('telegram'));
		$this->assertInstanceOf(NullTransport::class, $resolver->resolve('telegram'));
	}

	public function testMissingDriverDisablesChannel()
	{
		config(['messenger.channels.telegram.driver' => null]);

		$resolver = app(MessengerTransportResolver::class);

		$this->assertTrue($resolver->isDisabled('telegram'));
		$this->assertInstanceOf(NullTransport::class, $resolver->resolve('telegram'));
	}

	public function testNotifierSkipsChannelWithEmptyDriver()
	{
		config(['messenger.channels.telegram.driver' => '']);

		$result = app(MessengerNotifier::class)->send(
			MessengerMessage::create('Disabled channel'),
			['telegram']
		);

		$this->assertSame([], $result);
	}
}

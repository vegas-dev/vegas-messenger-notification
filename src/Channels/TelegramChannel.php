<?php

namespace Vegas\MessengerNotification\Channels;

class TelegramChannel extends AbstractMessengerChannel
{
	protected function channel()
	{
		return 'telegram';
	}
}

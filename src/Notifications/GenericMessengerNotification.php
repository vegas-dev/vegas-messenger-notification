<?php

namespace Vegas\MessengerNotification\Notifications;

use Illuminate\Notifications\Notification;
use Vegas\MessengerNotification\Channels\MaxChannel;
use Vegas\MessengerNotification\Channels\TelegramChannel;
use Vegas\MessengerNotification\Messages\MessengerMessage;

class GenericMessengerNotification extends Notification
{
	private $channel;
	private $message;

	public function __construct($channel, MessengerMessage $message)
	{
		$this->channel = $channel;
		$this->message = $message;
	}

	public function via($notifiable)
	{
		return [$this->channel === 'telegram' ? TelegramChannel::class : MaxChannel::class];
	}

	public function toMessenger($notifiable)
	{
		return $this->message;
	}
}

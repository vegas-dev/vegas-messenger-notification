<?php

namespace Vegas\MessengerNotification\Transports;

use Illuminate\Notifications\Notification;
use Vegas\MessengerNotification\Contracts\MessengerTransport;
use Vegas\MessengerNotification\Messages\MessengerMessage;

class NullTransport implements MessengerTransport
{
	public function send($channel, MessengerMessage $message, $notifiable, Notification $notification)
	{
		return null;
	}
}

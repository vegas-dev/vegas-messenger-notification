<?php

namespace Vegas\MessengerNotification\Contracts;

use Illuminate\Notifications\Notification;
use Vegas\MessengerNotification\Messages\MessengerMessage;

interface MessengerTransport
{
	public function send($channel, MessengerMessage $message, $notifiable, Notification $notification);
}

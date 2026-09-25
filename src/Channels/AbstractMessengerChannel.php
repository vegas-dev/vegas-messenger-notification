<?php

namespace Vegas\MessengerNotification\Channels;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;
use Vegas\MessengerNotification\Messages\MessengerMessage;
use Vegas\MessengerNotification\MessengerTransportResolver;

abstract class AbstractMessengerChannel
{
	private $transports;
	private $events;

	public function __construct(MessengerTransportResolver $transports, Dispatcher $events)
	{
		$this->transports = $transports;
		$this->events = $events;
	}

	abstract protected function channel();

	public function send($notifiable, Notification $notification)
	{
		try {
			$message = $notification->toMessenger($notifiable);

			if (is_string($message)) {
				$message = MessengerMessage::create($message);
			}

			if (!$message instanceof MessengerMessage) {
				return null;
			}

			if (!$message->idempotencyKey) {
				$message->idempotencyKey($this->defaultIdempotencyKey($notifiable, $notification));
			}

			return $this->transports
				->resolve($this->channel())
				->send($this->channel(), $message, $notifiable, $notification);
		} catch (Throwable $exception) {
			$this->events->dispatch(new NotificationFailed(
				$notifiable,
				$notification,
				$this->channel(),
				['message' => $exception->getMessage(), 'exception' => $exception]
			));

			Log::error(strtoupper($this->channel()) . ' notification error: ' . $exception->getMessage(), [
				'notification' => get_class($notification),
				'notifiable' => get_class($notifiable),
				'exception' => $exception,
			]);

			throw $exception;
		}
	}

	private function defaultIdempotencyKey($notifiable, Notification $notification)
	{
		$id = method_exists($notifiable, 'getKey') ? $notifiable->getKey() : null;

		return hash('sha256', implode('|', [
			get_class($notification),
			get_class($notifiable),
			(string) $id,
		]));
	}
}

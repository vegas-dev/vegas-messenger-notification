<?php

namespace Vegas\MessengerNotification\Transports;

use Illuminate\Notifications\Notification;
use RuntimeException;
use Vegas\MaxNotificationChannel\MaxBotClient;
use Vegas\MessengerNotification\Contracts\MessengerTransport;
use Vegas\MessengerNotification\Messages\MessengerMessage;

class MaxDirectTransport implements MessengerTransport
{
	public function send($channel, MessengerMessage $message, $notifiable, Notification $notification)
	{
		$chatId = config('messenger.channels.max.chat_id');

		if (!$chatId) {
			return null;
		}

		$payload = [
			'format' => $message->format,
			'notify' => $message->notify,
		];

		if ($message->buttons) {
			$payload['attachments'] = [[
				'type' => 'inline_keyboard',
				'payload' => ['buttons' => $this->buttonRows($message->buttons)],
			]];
		}

		$client = $this->client();

		if ($message->hasDocument()) {
			$message->assertReadableDocument();
			$payload['attachments'][] = $client->uploadAttachmentFile(
				'file',
				$message->documentPath,
				$message->documentFilename,
				$message->documentMime
			);
		}

		$messageId = !$message->hasDocument() && method_exists($notifiable, 'jd')
			? $notifiable->jd('max.message_id')
			: null;

		if ($messageId) {
			return $client->editMessage((string) $messageId, $message->content, $chatId, $payload);
		}

		$response = $client->sendMessage($message->content, $chatId, $payload);

		if ($response && !$message->hasDocument() && method_exists($notifiable, 'jd')) {
			$savedMessageId = isset($response['message_id'])
				? $response['message_id']
				: (isset($response['id']) ? $response['id'] : null);

			if ($savedMessageId) {
				$notifiable->jd('max.message_id', $savedMessageId);
				$notifiable->save();
			}
		}

		return $response;
	}

	private function client()
	{
		$token = config('messenger.channels.max.token');

		if (!$token) {
			throw new RuntimeException('MAX_BOT_TOKEN is not configured.');
		}

		return new MaxBotClient(
			$token,
			config('messenger.channels.max.base_url'),
			config('messenger.channels.max.chat_id')
		);
	}

	private function buttonRows(array $buttons)
	{
		$rows = [];

		foreach ($buttons as $button) {
			$rows[$button['row']][] = [
				'type' => 'link',
				'text' => $button['text'],
				'url' => $button['url'],
			];
		}

		ksort($rows);

		return array_values($rows);
	}
}

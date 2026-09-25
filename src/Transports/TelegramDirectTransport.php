<?php

namespace Vegas\MessengerNotification\Transports;

use GuzzleHttp\Client as HttpClient;
use Illuminate\Notifications\Notification;
use RuntimeException;
use Telegram\Bot\Api;
use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\HttpClients\GuzzleHttpClient;
use Telegram\Bot\Objects\BaseObject;
use Vegas\MessengerNotification\Contracts\MessengerTransport;
use Vegas\MessengerNotification\Messages\MessengerMessage;
use Vegas\MessengerNotification\Support\ProxyResolver;

class TelegramDirectTransport implements MessengerTransport
{
	public function send($channel, MessengerMessage $message, $notifiable, Notification $notification)
	{
		$chatId = config('messenger.channels.telegram.chat_id');

		if (!$chatId) {
			return null;
		}

		$payload = [
			'chat_id' => $chatId,
			'parse_mode' => $message->format === 'markdown' ? 'Markdown' : null,
			'disable_notification' => !$message->notify,
		];

		if ($message->buttons) {
			$payload['reply_markup'] = json_encode([
				'inline_keyboard' => $this->buttonRows($message->buttons),
			], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}

		if ($message->hasDocument()) {
			$message->assertReadableDocument();
			$payload['caption'] = $message->content;
			$payload['document'] = InputFile::create(
				$message->documentPath,
				$message->documentFilename
			);
			$response = $this->api()->sendDocument($payload);

			return $this->normalize($response);
		}

		$payload['text'] = $message->content;
		$messageId = method_exists($notifiable, 'jd') ? $notifiable->jd('telegram.message_id') : null;
		$api = $this->api();

		if ($messageId) {
			$payload['message_id'] = $messageId;

			return $this->normalize($api->editMessageText($payload));
		}

		$response = $api->sendMessage($payload);

		if ($response && method_exists($notifiable, 'jd')) {
			$notifiable->jd('telegram.message_id', $response->getMessageId());
			$notifiable->save();
		}

		return $this->normalize($response);
	}

	private function api()
	{
		$token = config('messenger.channels.telegram.token');

		if (!$token) {
			throw new RuntimeException('TELEGRAM_BOT_TOKEN is not configured.');
		}

		$options = ProxyResolver::guzzleOptions(config('messenger.channels.telegram.proxy'));
		$options['connect_timeout'] = 20;
		$options['timeout'] = 60;

		return new Api(
			$token,
			false,
			new GuzzleHttpClient(new HttpClient($options)),
			config('messenger.channels.telegram.base_url')
		);
	}

	private function buttonRows(array $buttons)
	{
		$rows = [];

		foreach ($buttons as $button) {
			$rows[$button['row']][] = [
				'text' => $button['text'],
				'url' => $button['url'],
			];
		}

		ksort($rows);

		return array_values($rows);
	}

	private function normalize($response)
	{
		return $response instanceof BaseObject
			? $response->all()
			: (is_array($response) ? $response : null);
	}
}

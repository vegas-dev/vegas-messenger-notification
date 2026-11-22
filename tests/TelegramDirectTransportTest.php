<?php

namespace Vegas\MessengerNotification\Tests;

use Illuminate\Notifications\Notification;
use Telegram\Bot\Api;
use Vegas\MessengerNotification\Messages\MessengerMessage;
use Vegas\MessengerNotification\Transports\TelegramDirectTransport;

class TelegramDirectTransportTest extends TestCase
{
	public function test_it_maps_preview_choices_to_telegram_options(): void
	{
		foreach ([[null, null, null], [null, false, true], [null, true, false], [true, false, false], [false, true, true]] as [$choice, $default, $expected]) {
			config(['messenger.channels.telegram.chat_id' => '-123', 'messenger.channels.telegram.link_preview' => $default]);
			$payload = ['chat_id' => '-123', 'parse_mode' => 'Markdown', 'disable_notification' => false, 'text' => 'https://example.com/page'];
			if ($expected !== null) {
				$payload['disable_web_page_preview'] = $expected;
			}
			$api = $this->mock(Api::class);
			$api->shouldReceive('sendMessage')->once()->with($payload)->andReturn(new \Telegram\Bot\Objects\Message([]));
			$message = MessengerMessage::create('https://example.com/page')->linkPreview($choice);

			$result = $this->transport($api)->send('telegram', $message, new \stdClass, new Notification);

			$this->assertSame([], $result);
		}
	}

	public function test_it_omits_preview_options_for_telegram_document_captions(): void
	{
		config(['messenger.channels.telegram.chat_id' => '-123', 'messenger.channels.telegram.link_preview' => false]);
		$api = $this->mock(Api::class);
		$api->shouldReceive('sendDocument')->once()->withArgs(function (array $payload): bool {
			return ! array_key_exists('disable_web_page_preview', $payload) && $payload['caption'] === 'Report';
		})->andReturn(new \Telegram\Bot\Objects\Message([]));
		$message = MessengerMessage::create('Report')->document(__FILE__, 'report.php', 'text/plain');

		$result = $this->transport($api)->send('telegram', $message, new \stdClass, new Notification);

		$this->assertSame([], $result);
	}

	private function transport(Api $api): TelegramDirectTransport
	{
		return new class($api) extends TelegramDirectTransport
		{
			private $testApi;

			public function __construct(Api $api)
			{
				$this->testApi = $api;
			}

			protected function api(): Api
			{
				return $this->testApi;
			}
		};
	}
}

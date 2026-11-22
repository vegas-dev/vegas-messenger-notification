<?php

namespace Vegas\MessengerNotification\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Illuminate\Notifications\Notification;
use Vegas\MaxNotificationChannel\MaxBotClient;
use Vegas\MessengerNotification\Messages\MessengerMessage;
use Vegas\MessengerNotification\Transports\MaxDirectTransport;

class MaxDirectTransportTest extends TestCase
{
	public function test_it_applies_message_choices_before_channel_defaults(): void
	{
		foreach ([
			[null, null, null],
			[null, false, 'true'],
			[null, true, 'false'],
			[false, true, 'true'],
			[true, false, 'false'],
			[false, null, 'true'],
			[true, null, 'false'],
		] as [$choice, $default, $expected]) {
			config(['messenger.channels.max.chat_id' => '-123', 'messenger.channels.max.link_preview' => $default]);
			$http = new MockHandler([new Response(200, [], '{}')]);
			$client = new class($http) extends MaxBotClient
			{
				public function __construct(MockHandler $http)
				{
					parent::__construct('test-token', 'https://max-api.example.com');
					$this->client = new Client(['handler' => $http]);
				}
			};
			$message = MessengerMessage::create('https://example.com/page')->linkPreview($choice)
				->button('Open', 'https://example.com/page');

			$this->transport($client)->send('max', $message, new \stdClass, new Notification);

			$request = $http->getLastRequest();
			parse_str($request->getUri()->getQuery(), $query);
			$expectedQuery = ['chat_id' => '-123'];
			if ($expected !== null) {
				$expectedQuery['disable_link_preview'] = $expected;
			}
			$this->assertSame($expectedQuery, $query);
			$body = json_decode((string) $request->getBody(), true);
			$this->assertArrayNotHasKey('disable_link_preview', $body);
			$this->assertSame('https://example.com/page', $body['text']);
			$this->assertSame('https://example.com/page', $body['attachments'][0]['payload']['buttons'][0][0]['url']);
		}
	}

	public function test_it_keeps_document_attachments_with_previews_disabled(): void
	{
		config(['messenger.channels.max.chat_id' => '-123', 'messenger.channels.max.link_preview' => false]);
		$attachment = ['type' => 'file', 'payload' => ['token' => 'test-file-token']];
		$client = $this->mock(MaxBotClient::class);
		$client->shouldReceive('uploadAttachmentFile')->once()->with('file', __FILE__, 'report.php', 'text/plain')->andReturn($attachment);
		$client->shouldReceive('sendMessage')->once()->with('Report', '-123', [
			'format' => 'markdown', 'notify' => true, 'disable_link_preview' => true, 'attachments' => [$attachment],
		])->andReturn(['message_id' => 'file-message']);
		$message = MessengerMessage::create('Report')->document(__FILE__, 'report.php', 'text/plain');

		$result = $this->transport($client)->send('max', $message, new \stdClass, new Notification);

		$this->assertSame(['message_id' => 'file-message'], $result);
	}

	private function transport(MaxBotClient $client): MaxDirectTransport
	{
		return new class($client) extends MaxDirectTransport
		{
			private $testClient;

			public function __construct(MaxBotClient $client)
			{
				$this->testClient = $client;
			}

			protected function client(): MaxBotClient
			{
				return $this->testClient;
			}
		};
	}
}

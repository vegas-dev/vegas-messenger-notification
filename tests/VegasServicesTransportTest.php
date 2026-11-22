<?php

namespace Vegas\MessengerNotification\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Illuminate\Notifications\Notification;
use Vegas\MessengerNotification\Messages\MessengerMessage;
use Vegas\MessengerNotification\MessengerSignature;
use Vegas\MessengerNotification\Transports\VegasServicesTransport;

class VegasServicesTransportTest extends TestCase
{
	public function test_it_signs_the_resolved_preview_choice_for_each_channel(): void
	{
		foreach (['max', 'telegram'] as $channel) {
			foreach ([[null, null, null], [null, false, true], [null, true, false], [true, false, false], [false, true, true]] as [$choice, $default, $expected]) {
				config([
					'messenger.channels.'.$channel.'.link_preview' => $default,
					'messenger.vegas_services.project' => 'example-project',
					'messenger.vegas_services.url' => 'https://relay.example.com',
					'messenger.vegas_services.signing_key' => 'test-signing-key',
				]);
				$http = new MockHandler([new Response(200, [], '{"accepted":true}')]);
				$transport = new VegasServicesTransport(new MessengerSignature, new Client(['handler' => $http]));
				$message = MessengerMessage::create('https://example.com/page')->linkPreview($choice)->idempotencyKey('test-notification');

				$transport->send($channel, $message, new \stdClass, new Notification);

				$request = $http->getLastRequest();
				$body = (string) $request->getBody();
				$data = json_decode($body, true);
				$this->assertSame($channel, $data['channel']);
				$this->assertSame('test-notification:'.$channel, $data['idempotency_key']);
				if ($expected === null) {
					$this->assertArrayNotHasKey('disable_link_preview', $data['message']);
				} else {
					$this->assertSame($expected, $data['message']['disable_link_preview']);
				}
				$this->assertSame(hash_hmac('sha256', $request->getHeaderLine('X-Vegas-Timestamp')."\nexample-project\n".$body, 'test-signing-key'), $request->getHeaderLine('X-Vegas-Signature'));
			}
		}
	}
}

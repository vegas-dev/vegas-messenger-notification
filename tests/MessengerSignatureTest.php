<?php

namespace Vegas\MessengerNotification\Tests;

use Vegas\MessengerNotification\MessengerSignature;

class MessengerSignatureTest extends TestCase
{
	public function test_it_uses_an_explicit_signing_key()
	{
		config(['messenger.vegas_services.signing_key' => 'relay-secret']);

		$signature = app(MessengerSignature::class)->make('project', '123', '{}');

		$this->assertSame(
			hash_hmac('sha256', "123\nproject\n{}", 'relay-secret'),
			$signature
		);
	}

	public function test_it_derives_a_signing_key_from_the_application_key()
	{
		config([
			'app.key' => 'base64:application-key',
			'messenger.vegas_services.signing_key' => null,
		]);

		$this->assertSame(
			hash_hmac('sha256', 'vegas-services-notifications:v1', 'base64:application-key'),
			app(MessengerSignature::class)->signingKey()
		);
	}
}

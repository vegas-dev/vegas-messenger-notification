<?php

namespace Vegas\MessengerNotification;

use RuntimeException;

class MessengerSignature
{
	public function signingKey($appKey = null)
	{
		$configuredKey = trim((string) config('messenger.vegas_services.signing_key'));

		if ($configuredKey !== '') {
			return $configuredKey;
		}

		$appKey = $appKey ?: config('app.key');

		if (!$appKey) {
			throw new RuntimeException('APP_KEY or VEGAS_SERVICES_SIGNING_KEY is required to sign relay requests.');
		}

		return hash_hmac('sha256', 'vegas-services-notifications:v1', $appKey);
	}

	public function make($project, $timestamp, $body)
	{
		return hash_hmac(
			'sha256',
			$timestamp . "\n" . $project . "\n" . $body,
			$this->signingKey()
		);
	}
}

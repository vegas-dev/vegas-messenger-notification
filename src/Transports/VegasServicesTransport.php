<?php

namespace Vegas\MessengerNotification\Transports;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Notifications\Notification;
use JsonException;
use RuntimeException;
use Vegas\MessengerNotification\Contracts\MessengerTransport;
use Vegas\MessengerNotification\Messages\MessengerMessage;
use Vegas\MessengerNotification\MessengerSignature;

class VegasServicesTransport implements MessengerTransport
{
	private $signatures;
	private $http;

	public function __construct(MessengerSignature $signatures, ClientInterface $http = null)
	{
		$this->signatures = $signatures;
		$this->http = $http;
	}

	public function send($channel, MessengerMessage $message, $notifiable, Notification $notification)
	{
		$project = strtolower(trim((string) config('messenger.vegas_services.project')));
		$url = rtrim((string) config('messenger.vegas_services.url'), '/');

		if ($project === '') {
			throw new RuntimeException('VEGAS_SERVICES_PROJECT is not configured.');
		}

		if ($url === '') {
			throw new RuntimeException('VEGAS_SERVICES_URL is not configured.');
		}

		$payload = [
			'channel' => $channel,
			'idempotency_key' => $message->idempotencyKey . ':' . $channel,
			'message' => $message->toArray(),
		];
		$body = $this->encode($payload);
		$timestamp = (string) time();
		$response = $this->request($url . '/api/v1/notifications', [
			'headers' => [
				'X-Vegas-Project' => $project,
				'X-Vegas-Timestamp' => $timestamp,
				'X-Vegas-Signature' => $this->signatures->make($project, $timestamp, $body),
				'Accept' => 'application/json',
				'Content-Type' => 'application/json',
			],
			'body' => $body,
		]);

		$data = json_decode((string) $response->getBody(), true);

		return is_array($data) ? $data : [];
	}

	private function request($url, array $options)
	{
		$attempt = 0;

		while (true) {
			$attempt++;

			try {
				return $this->client()->request('POST', $url, $options);
			} catch (GuzzleException $exception) {
				if ($attempt >= 3 || !$this->retryable($exception)) {
					throw $exception;
				}

				usleep(250000 * $attempt);
			}
		}
	}

	private function retryable(GuzzleException $exception)
	{
		if ($exception instanceof ConnectException) {
			return true;
		}

		return $exception instanceof RequestException
			&& $exception->hasResponse()
			&& $exception->getResponse()->getStatusCode() >= 500;
	}

	private function client()
	{
		if ($this->http === null) {
			$this->http = new Client([
				'connect_timeout' => (int) config('messenger.vegas_services.connect_timeout', 5),
				'timeout' => (int) config('messenger.vegas_services.timeout', 15),
				'http_errors' => true,
			]);
		}

		return $this->http;
	}

	private function encode(array $payload)
	{
		$options = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

		if (defined('JSON_THROW_ON_ERROR')) {
			$options |= JSON_THROW_ON_ERROR;
		}

		$body = json_encode($payload, $options);

		if ($body === false) {
			throw new RuntimeException('Unable to encode messenger payload: ' . json_last_error_msg());
		}

		return $body;
	}
}

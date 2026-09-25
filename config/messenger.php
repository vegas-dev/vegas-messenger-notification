<?php

return [
	'channels' => [
		'telegram' => [
			'driver' => env('TELEGRAM_NOTIFICATION_DRIVER', 'off'),
			'token' => env('TELEGRAM_BOT_TOKEN'),
			'chat_id' => env('TELEGRAM_CHANNEL_DEFAULT_ID'),
			'base_url' => env('TELEGRAM_BOT_API_URL'),
			'proxy' => env('TELEGRAM_PROXY', env('PROXY_SOCKS5')),
		],
		'max' => [
			'driver' => env('MAX_NOTIFICATION_DRIVER', 'off'),
			'token' => env('MAX_BOT_TOKEN'),
			'chat_id' => env('MAX_CHANNEL_DEFAULT_ID'),
			'base_url' => env('MAX_BOT_API_URL'),
		],
	],

	'vegas_services' => [
		'url' => env('VEGAS_SERVICES_URL'),
		'project' => env('VEGAS_SERVICES_PROJECT', env('APP_NAME')),
		'signing_key' => env('VEGAS_SERVICES_SIGNING_KEY'),
		'connect_timeout' => (int) env('VEGAS_SERVICES_CONNECT_TIMEOUT', 5),
		'timeout' => (int) env('VEGAS_SERVICES_TIMEOUT', 15),
	],
];

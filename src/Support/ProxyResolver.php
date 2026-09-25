<?php

namespace Vegas\MessengerNotification\Support;

class ProxyResolver
{
	public static function normalize($proxy)
	{
		$proxy = trim((string) $proxy);

		if ($proxy === '') {
			return null;
		}

		if (strpos($proxy, '://') === false) {
			return 'socks5h://' . $proxy;
		}

		if (stripos($proxy, 'socks5://') === 0) {
			return 'socks5h://' . substr($proxy, strlen('socks5://'));
		}

		return $proxy;
	}

	public static function guzzleOptions($proxy = null)
	{
		$proxy = self::normalize($proxy);

		return $proxy === null ? [] : ['proxy' => $proxy];
	}
}

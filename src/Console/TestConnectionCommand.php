<?php

namespace Vegas\MessengerNotification\Console;

use Illuminate\Console\Command;
use Vegas\MessengerNotification\Messages\MessengerMessage;
use Vegas\MessengerNotification\MessengerNotifier;
use Vegas\MessengerNotification\MessengerTransportResolver;

class TestConnectionCommand extends Command
{
	protected $signature = 'messenger:test {channel=all : telegram, max or all}';
	protected $description = 'Send a test notification to Telegram and/or MAX';

	public function handle(MessengerNotifier $notifier, MessengerTransportResolver $transports)
	{
		$channel = strtolower((string) $this->argument('channel'));

		if (!in_array($channel, ['all', 'telegram', 'max'], true)) {
			$this->error('Allowed values: telegram, max, all.');

			return 2;
		}

		$channels = $channel === 'all' ? ['telegram', 'max'] : [$channel];
		$message = MessengerMessage::create(implode("\n", [
			'*Messenger connection test*',
			'*Project*: ' . config('messenger.vegas_services.project', config('app.name')),
			'*Time*: ' . date('d.m.Y H:i:s'),
		]))->idempotencyKey('connection-test:' . str_replace('.', '', uniqid('', true)));

		foreach ($channels as $currentChannel) {
			if ($transports->isDisabled($currentChannel)) {
				$this->warn(strtoupper($currentChannel) . ': channel is disabled.');
				continue;
			}

			$notifier->send($message, [$currentChannel]);
			$this->info(strtoupper($currentChannel) . ': request completed.');
		}

		return 0;
	}
}

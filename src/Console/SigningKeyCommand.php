<?php

namespace Vegas\MessengerNotification\Console;

use Illuminate\Console\Command;
use Vegas\MessengerNotification\MessengerSignature;

class SigningKeyCommand extends Command
{
	protected $signature = 'messenger:signing-key';
	protected $description = 'Show the signing key used for the messenger relay';

	public function handle(MessengerSignature $signatures)
	{
		$this->line($signatures->signingKey());

		return 0;
	}
}

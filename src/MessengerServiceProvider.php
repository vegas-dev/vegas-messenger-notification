<?php

namespace Vegas\MessengerNotification;

use Illuminate\Support\ServiceProvider;
use Vegas\MessengerNotification\Console\SigningKeyCommand;
use Vegas\MessengerNotification\Console\TestConnectionCommand;

class MessengerServiceProvider extends ServiceProvider
{
	public function register()
	{
		$this->mergeConfigFrom(__DIR__ . '/../config/messenger.php', 'messenger');

		$this->app->singleton(MessengerSignature::class);
		$this->app->singleton(MessengerTransportResolver::class);
		$this->app->singleton(MessengerNotifier::class);
	}

	public function boot()
	{
		$this->publishes([
			__DIR__ . '/../config/messenger.php' => config_path('messenger.php'),
		], 'messenger-config');

		if ($this->app->runningInConsole()) {
			$this->commands([
				SigningKeyCommand::class,
				TestConnectionCommand::class,
			]);
		}
	}
}

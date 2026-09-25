<?php

namespace Vegas\MessengerNotification\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Vegas\MessengerNotification\MessengerServiceProvider;

abstract class TestCase extends Orchestra
{
	protected function getPackageProviders($app)
	{
		return [MessengerServiceProvider::class];
	}
}

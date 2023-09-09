<?php

namespace Mckue\Excel\Tests;

use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Mckue\Excel\ExcelServiceProvider;

class TestCase extends OrchestraTestCase
{
	use WithWorkbench;

	protected function getPackageProviders($app)
	{
		return [
			ExcelServiceProvider::class,
		];
	}

	/**
	 * @param  \Illuminate\Foundation\Application  $app
	 */
	protected function getEnvironmentSetUp($app)
	{
		$app['config']->set('filesystems.disks.local.root', __DIR__ . '/Data/Disks/Local');
		$app['config']->set('filesystems.disks.test', [
			'driver' => 'local',
			'root'   => __DIR__ . '/Data/Disks/Test',
		]);

		$app['config']->set('database.default', 'testing');
		$app['config']->set('database.connections.testing', [
			'driver'   => 'mysql',
			'host'     => env('DB_HOST'),
			'port'     => env('DB_PORT'),
			'database' => env('DB_DATABASE'),
			'username' => env('DB_USERNAME'),
			'password' => env('DB_PASSWORD'),
		]);

		$app['config']->set('view.paths', [
			__DIR__ . '/Data/Stubs/Views',
		]);
	}

}

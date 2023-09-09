<?php

namespace Mckue\Excel\Tests;

use Mckue\Excel\Concerns\ToArray;
use Mckue\Excel\Excel;

class ImportTest extends TestCase
{
	/**
	 * @var Excel
	 */
	protected Excel $excel;

	protected function setUp(): void
	{
		parent::setUp();

		$this->excel = $this->app->make(Excel::class);
	}

	public function testStoreFromArray()
	{
		$export = new class implements ToArray {

			public function array(array $array): void
			{
				echo "<pre>";
				print_r($array);
				die;
			}
		};

		\Mckue\Excel\Facades\Excel::import($export, 'test/test.xlsx');
	}
}

<?php

namespace Yc\Excel\Tests;

use Yc\Excel\Concerns\ToArray;
use Yc\Excel\Excel;

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

		\Yc\Excel\Facades\Excel::import($export, 'test/test.xlsx');
	}
}

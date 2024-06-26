<?php

namespace Mckue\Excel\Tests;

use Mckue\Excel\Concerns\Exportable;
use Mckue\Excel\Concerns\FromArray;
use Mckue\Excel\Concerns\WithHeadings;
use Mckue\Excel\Concerns\WithTitle;
use Mckue\Excel\Excel;

class ExportTest extends TestCase
{
    /**
     * @var Excel
     */
    protected $SUT;

    protected function setUp(): void
    {
        parent::setUp();

        $this->SUT = $this->app->make(Excel::class);
    }

    public function testStoreFromArray()
    {
        $export = new class implements FromArray, WithHeadings, WithTitle
        {
            use Exportable;

            public function title(): string
            {
                return 'Sheet1';
            }

            public function array(): array
            {
                return [[
                    1, 2, 3,
                ], [
                    3, 4, 5,
                ], [
                    6, 7, 8,
                ]];
            }

            public function headings(): array
            {
                return [
                    ['test1', 'test2', 'test3'],
                    ['test111', 'test222', 'test333'],
                ];
            }
        };

        \Mckue\Excel\Facades\Excel::store($export, 'test/test.xlsx');
        //echo $this->SUT->store(new TestExport(), 'test/test.xlsx', null, 'xlsx');
    }
}

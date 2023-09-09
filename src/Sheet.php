<?php

namespace Mckue\Excel;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Vtiful\Kernel\Excel;
use Mckue\Excel\Concerns\FromArray;
use Mckue\Excel\Concerns\FromCollection;
use Mckue\Excel\Concerns\FromGenerator;
use Mckue\Excel\Concerns\FromIterator;
use Mckue\Excel\Concerns\FromQuery;
use Mckue\Excel\Concerns\OnEachRow;
use Mckue\Excel\Concerns\SkipsEmptyRows;
use Mckue\Excel\Concerns\ToArray;
use Mckue\Excel\Concerns\ToCollection;
use Mckue\Excel\Concerns\ToModel;
use Mckue\Excel\Concerns\WithColumnFormatting;
use Mckue\Excel\Concerns\WithColumnLimit;
use Mckue\Excel\Concerns\WithCustomChunkSize;
use Mckue\Excel\Concerns\WithEvents;
use Mckue\Excel\Concerns\WithHeadings;
use Mckue\Excel\Concerns\WithMappedCells;
use Mckue\Excel\Concerns\WithMapping;
use Mckue\Excel\Concerns\WithRowFormatting;
use Mckue\Excel\Concerns\WithStyles;
use Mckue\Excel\Concerns\WithValidation;
use Mckue\Excel\Events\AfterSheet;
use Mckue\Excel\Events\BeforeSheet;
use Mckue\Excel\Exceptions\ConcernConflictException;
use Mckue\Excel\Exceptions\RowSkippedException;
use Mckue\Excel\Exceptions\SheetNotFoundException;
use Mckue\Excel\Files\TemporaryFileFactory;
use Mckue\Excel\Helpers\ArrayHelper;
use Mckue\Excel\Imports\HeadingRowExtractor;
use Mckue\Excel\Imports\ModelImporter;
use Mckue\Excel\Validators\RowValidator;

/** @mixin Excel */
class Sheet
{
    use DelegatedMacroable, HasEventBus;

    protected int $chunkSize;

    protected TemporaryFileFactory $temporaryFileFactory;

    protected object $exportable;

    private Excel $worksheet;

    public function __construct(Excel $worksheet)
    {
        $this->worksheet = $worksheet;
        $this->chunkSize = config('mckue-excel.exports.chunk_size', 100);
        $this->temporaryFileFactory = app(TemporaryFileFactory::class);
    }

    /**
     * @throws SheetNotFoundException
     */
    public static function make(Excel $spreadsheet, string|int $index): Sheet
    {
        if (is_numeric($index)) {
            return self::byIndex($spreadsheet, $index);
        }

        return self::byName($spreadsheet, $index);
    }

    /**
     * @throws SheetNotFoundException
     */
    public static function byIndex(Excel $spreadsheet, int $index): Sheet
    {
        $sheetList = $spreadsheet->sheetList();
        if (! isset($sheetList[$index])) {
            throw SheetNotFoundException::byIndex($index, count($sheetList));
        }

        return new static($spreadsheet->openSheet($sheetList[$index]));
    }

    /**
     * @throws SheetNotFoundException
     */
    public static function byName(Excel $spreadsheet, string $name): Sheet
    {
        $sheetList = $spreadsheet->sheetList();
        $sheetList = array_flip($sheetList);
        if (! isset($sheetList[$name])) {
            throw SheetNotFoundException::byName($name);
        }

        return new static($spreadsheet->openSheet($name));
    }

    public function open(object $sheetExport): void
    {
        $this->exportable = $sheetExport;

        if ($sheetExport instanceof WithEvents) {
            $this->registerListeners($sheetExport->registerEvents());
        }

        $this->raise(new BeforeSheet($this, $this->exportable));

        if (! $sheetExport instanceof FromQuery && ! $sheetExport instanceof FromCollection && ! $sheetExport instanceof FromArray) {
            throw ConcernConflictException::queryOrCollectionAndView();
        }

        if ($sheetExport instanceof WithHeadings) {
            $this->worksheet->header($sheetExport->headings());
        }

        //if ($sheetExport instanceof WithCharts) {
        //    $this->addCharts($sheetExport->charts());
        //}
    }

    public function export(object $sheetExport): void
    {
        $this->open($sheetExport);

        if ($sheetExport instanceof FromQuery) {
            $this->fromQuery($sheetExport);
        }

        if ($sheetExport instanceof FromCollection) {
            $this->fromCollection($sheetExport);
        }

        if ($sheetExport instanceof FromArray) {
            $this->fromArray($sheetExport);
        }

        if ($sheetExport instanceof FromIterator) {
            $this->fromIterator($sheetExport);
        }

        if ($sheetExport instanceof FromGenerator) {
            $this->fromGenerator($sheetExport);
        }

        $this->close($sheetExport);
    }

    public function import(object $import, int $startRow = 1): void
    {
        if ($import instanceof WithEvents) {
            $this->registerListeners($import->registerEvents());
        }

        $this->raise(new BeforeSheet($this, $import));

        if ($import instanceof WithMappedCells) {
            app(MappedReader::class)->map($import, $this->worksheet);
        } else {
            if ($import instanceof ToModel) {
                app(ModelImporter::class)->import($this->worksheet, $import, $startRow);
            }

            if ($import instanceof ToCollection) {
                $rows = $this->toCollection($import, $startRow);

                if ($import instanceof WithValidation) {
                    $rows = $this->validated($import, $startRow, $rows);
                }

                $import->collection($rows);
            }

            if ($import instanceof ToArray) {
                $rows = $this->toArray($import, $startRow);

                if ($import instanceof WithValidation) {
                    $rows = $this->validated($import, $startRow, $rows);
                }

                $import->array($rows);
            }

            if ($import instanceof OnEachRow) {
                $this->onEachRow($import, $startRow);
            }
        }

        $this->raise(new AfterSheet($this, $import));
    }

    public function onEachRow(object $import, int $startRow = 1): void
    {
        $endColumn = $import instanceof WithColumnLimit ? $import->endColumn() : null;
        $worksheet = $this->worksheet->setSkipRows($startRow);
        $endColumnIndex = $endColumn ? Excel::columnIndexFromString($endColumn) : null;

        $i = $startRow - 1;
        while (($rowArray = $worksheet->nextRow()) !== null) {
            $i++;

            if ($endColumnIndex !== null) {
                $rowArray = array_slice((array) $rowArray, 0, $endColumnIndex + 1);
            }

            if ($import instanceof SkipsEmptyRows && ArrayHelper::isEmpty($rowArray)) {
                continue;
            }

            if ($import instanceof WithValidation) {

                if (method_exists($import, 'prepareForValidation')) {
                    $rowArray = $import->prepareForValidation($rowArray, $i);
                }

                app(RowValidator::class)->validate($rowArray, $import);
            } else {
                $import->onRow($rowArray);
            }
        }
    }

    public function toArray(object $import, int $startRow = 1): array
    {
        $endColumn = $import instanceof WithColumnLimit ? $import->endColumn() : null;
        $rows = [];

        $worksheet = $this->worksheet->setSkipRows($startRow);

        $i = $startRow - 1;
        $endColumnIndex = $endColumn ? Excel::columnIndexFromString($endColumn) : null;
        while (($rowArray = $worksheet->nextRow()) !== null) {
            $i++;
            if ($endColumnIndex !== null) {
                $rowArray = array_slice((array) $rowArray, 0, $endColumnIndex + 1);
            }

            if ($import instanceof SkipsEmptyRows && ArrayHelper::isEmpty($rowArray)) {
                continue;
            }

            if ($import && method_exists($import, 'isEmptyWhen') && $import->isEmptyWhen($rowArray)) {
                continue;
            }

            if ($import instanceof WithMapping) {
                $rowArray = $import->map($rowArray);
            }

            if ($import instanceof WithValidation && method_exists($import, 'prepareForValidation')) {
                $rowArray = $import->prepareForValidation($rowArray, $i);
            }

            $rows[] = $rowArray;
        }

        return $rows;
    }

    public function toCollection(object $import, int $startRow = 1): Collection
    {
        $rows = $this->toArray($import, $startRow);

        return new Collection(array_map(static function (array $row) {
            return new Collection($row);
        }, $rows));
    }

    public function close(object $sheetExport): void
    {
        //if ($sheetExport instanceof WithDrawings) {
        //    $this->addDrawings($sheetExport->drawings());
        //}

        $this->exportable = $sheetExport;

        if ($sheetExport instanceof WithColumnFormatting) {
            foreach ($sheetExport->formatColumns() as $column) {
                $sheetExport->formatColumnCallback($column, $this->worksheet);
            }
        }

        if ($sheetExport instanceof WithRowFormatting) {
            foreach ($sheetExport->formatRows() as $row) {
                $sheetExport->formatRowCallback($row, $this->worksheet);
            }
        }

        if ($sheetExport instanceof WithColumnWidths) {
            foreach ($sheetExport->columnWidths() as $column => $width) {
                $this->setColumnWidth($column, $width);

            }
        }

        if ($sheetExport instanceof WithStyles) {
            $sheetExport->styles($this->worksheet);
        }

        $this->raise(new AfterSheet($this, $this->exportable));

        $this->clearListeners();
    }

    private function setColumnWidth(string $column, float|int $width): void
    {
        $this->worksheet->setColumn($column, $width, $this->worksheet->getHandle());
    }

    public function fromQuery(FromQuery $sheetExport): void
    {
        $sheetExport->query()->chunk($this->getChunkSize($sheetExport), function ($chunk) use ($sheetExport) {
            $this->appendRows($chunk, $sheetExport);
        });
    }

    public function fromCollection(FromCollection $sheetExport): void
    {
        $this->appendRows($sheetExport->collection()->all(), $sheetExport);
    }

    public function fromArray(FromArray $sheetExport): void
    {
        $this->appendRows($sheetExport->array(), $sheetExport);
    }

    public function fromIterator(FromIterator $sheetExport): void
    {
        $this->appendRows($sheetExport->iterator(), $sheetExport);
    }

    public function fromGenerator(FromGenerator $sheetExport): void
    {
        $this->appendRows($sheetExport->generator(), $sheetExport);
    }

    public function formatColumn(string $column, string $format): void
    {
        // If the column is a range, we wouldn't need to calculate the range.
        if (stripos($column, ':') !== false) {

            $this->worksheet
                ->getStyle($column)
                ->getNumberFormat()
                ->setFormatCode($format);
        } else {
            $this->worksheet
                ->getStyle($column . '1:' . $column . $this->worksheet->getHighestRow())
                ->getNumberFormat()
                ->setFormatCode($format);
        }
    }

    public function chunkSize(int $chunkSize): self
    {
        $this->chunkSize = $chunkSize;

        return $this;
    }

    public function getDelegate(): Excel
    {
        return $this->worksheet;
    }

    ///**
    // * @param  Chart|Chart[]  $charts
    // */
    //public function addCharts($charts)
    //{
    //    $charts = \is_array($charts) ? $charts : [$charts];
    //
    //    foreach ($charts as $chart) {
    //        $this->worksheet->addChart($chart);
    //    }
    //}
    public function appendRows(iterable $rows, object $sheetExport): void
    {
        if (method_exists($sheetExport, 'prepareRows')) {
            $rows = $sheetExport->prepareRows($rows);
        }

        $rows = (new Collection($rows))->flatMap(function ($row) use ($sheetExport) {
            if ($sheetExport instanceof WithMapping) {
                $row = $sheetExport->map($row);
            }

            return ArrayHelper::ensureMultipleRows(
                static::mapArraybleRow($row)
            );
        })->toArray();

        $this->worksheet->data($rows);

    }

    public static function mapArraybleRow(mixed $row): array
    {
        // When dealing with eloquent models, we'll skip the relations
        // as we won't be able to display them anyway.
        if (is_object($row) && method_exists($row, 'attributesToArray')) {
            return $row->attributesToArray();
        }

        if ($row instanceof Arrayable) {
            return $row->toArray();
        }

        // Convert StdObjects to arrays
        if (is_object($row)) {
            return json_decode(json_encode($row), true);
        }

        return $row;
    }

    public function getStartRow($sheetImport): int
    {
        return HeadingRowExtractor::determineStartRow($sheetImport);
    }

    protected function validated(WithValidation $import, int $startRow, $rows): Collection|array
    {
        $toValidate = (new Collection($rows))->mapWithKeys(function ($row, $index) use ($startRow) {
            return [($startRow + $index) => $row];
        });

        try {
            app(RowValidator::class)->validate($toValidate->toArray(), $import);
        } catch (RowSkippedException $e) {
            foreach ($e->skippedRows() as $row) {
                unset($rows[$row - $startRow]);
            }
        }

        return $rows;
    }

    private function getChunkSize(object $export): int
    {
        if ($export instanceof WithCustomChunkSize) {
            return $export->chunkSize();
        }

        return $this->chunkSize;
    }
}

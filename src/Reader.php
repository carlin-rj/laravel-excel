<?php

namespace Mckue\Excel;

use Illuminate\Support\Collection;
use Mckue\Excel\Concerns\SkipsUnknownSheets;
use Mckue\Excel\Concerns\WithEvents;
use Mckue\Excel\Concerns\WithMultipleSheets;
use Mckue\Excel\Events\AfterImport;
use Mckue\Excel\Events\BeforeImport;
use Mckue\Excel\Events\ImportFailed;
use Mckue\Excel\Exceptions\SheetNotFoundException;
use Mckue\Excel\Files\TemporaryFile;
use Mckue\Excel\Files\TemporaryFileFactory;
use Mckue\Excel\Transactions\TransactionHandler;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Throwable;

/**@mixin \Vtiful\Kernel\Excel */
class Reader
{
    use DelegatedMacroable, HasEventBus;

    /**
     * @var object[]
     */
    protected array $sheetImports = [];

    /**
     * @var TemporaryFile
     */
    protected TemporaryFile $currentFile;

    /**
     * @var TemporaryFileFactory
     */
    protected TemporaryFileFactory $temporaryFileFactory;

    /**
     * @var TransactionHandler
     */
    protected TransactionHandler $transaction;

    /**
     * @var  \Vtiful\Kernel\Excel
     */
    protected  \Vtiful\Kernel\Excel $reader;

    /**
     * @param  TemporaryFileFactory  $temporaryFileFactory
     * @param  TransactionHandler  $transaction
     */
    public function __construct(TemporaryFileFactory $temporaryFileFactory, TransactionHandler $transaction)
    {
        $this->transaction          = $transaction;
        $this->temporaryFileFactory = $temporaryFileFactory;
    }

    public function __sleep()
    {
        return ['reader', 'sheetImports', 'currentFile', 'temporaryFileFactory', 'reader'];
    }

    public function __wakeup()
    {
        $this->transaction = app(TransactionHandler::class);
    }

	/**
	 * @param object $import
	 * @param string|UploadedFile $filePath
	 * @param string|null $readerType
	 * @param string|null $disk
	 * @return $this
	 * @throws SheetNotFoundException
	 * @throws Throwable
	 */
    public function read(object $import, string|UploadedFile $filePath, string $readerType = null, string $disk = null):self
    {
        $this->reader = $this->getReader($import, $filePath, $disk);

        try {
            $this->loadSpreadsheet($import);

            ($this->transaction)(function () use ($import) {
                foreach ($this->sheetImports as $index => $sheetImport) {
                    if ($sheet = $this->getSheet($import, $sheetImport, $index)) {
                        $sheet->import($sheetImport, $sheet->getStartRow($sheetImport));
                    }
                }
            });

            $this->afterImport($import);
        } catch (Throwable $e) {
            $this->raise(new ImportFailed($e));
            $this->garbageCollect();
            throw $e;
        }

        return $this;
    }

	/**
	 * @param object $import
	 * @param string|UploadedFile $filePath
	 * @param string|null $readerType
	 * @param string|null $disk
	 * @return array
	 * @throws SheetNotFoundException
	 */
    public function toArray(object $import, string|UploadedFile $filePath, string $readerType = null, string $disk = null): array
    {
        $this->reader = $this->getReader($import, $filePath, $readerType, $disk);

        $this->loadSpreadsheet($import);

        $sheets = [];
        foreach ($this->sheetImports as $index => $sheetImport) {
            if ($sheet = $this->getSheet($import, $sheetImport, $index)) {
                $sheets[$index] = $sheet->toArray($sheetImport, $sheet->getStartRow($sheetImport));
            }
        }

        $this->afterImport($import);

        return $sheets;
    }

	/**
	 * @param object $import
	 * @param string|UploadedFile $filePath
	 * @param string|null $readerType
	 * @param string|null $disk
	 * @return Collection
	 * @throws SheetNotFoundException
	 */
    public function toCollection(object $import, string|UploadedFile $filePath, string $readerType = null, string $disk = null): Collection
    {
        $this->reader = $this->getReader($import, $filePath, $readerType, $disk);
        $this->loadSpreadsheet($import);

        $sheets = new Collection();
        foreach ($this->sheetImports as $index => $sheetImport) {
            if ($sheet = $this->getSheet($import, $sheetImport, $index)) {
                $sheets->put($index, $sheet->toCollection($sheetImport, $sheet->getStartRow($sheetImport)));
            }
        }

        $this->afterImport($import);

        return $sheets;
    }

    /**
     * @return \Vtiful\Kernel\Excel
     */
    public function getDelegate(): \Vtiful\Kernel\Excel
    {
        return $this->reader;
    }

    /**
     * @param  object  $import
     */
    public function loadSpreadsheet(object $import): void
    {
		$this->sheetImports = $this->buildSheetImports($import);
        $this->beforeImport($import);
    }

    /**
     * @param  object  $import
     */
    public function beforeImport(object $import): void
    {
        $this->raise(new BeforeImport($this, $import));
    }

    /**
     * @param  object  $import
     */
    public function afterImport(object $import): void
    {
        $this->raise(new AfterImport($this, $import));

        $this->garbageCollect();
    }

    /**
     * @param  object  $import
     * @return array
     */
    public function getWorksheets(object $import): array
    {
        // Csv doesn't have worksheets.
        if (!method_exists($this->reader, 'listWorksheetNames')) {
            return ['Worksheet' => $import];
        }

        $worksheets     = [];
        $worksheetNames = $this->reader->sheetList();
        if ($import instanceof WithMultipleSheets) {
            $sheetImports = $import->sheets();

            foreach ($sheetImports as $index => $sheetImport) {
                // Translate index to name.
                if (is_numeric($index)) {
                    $index = $worksheetNames[$index] ?? $index;
                }

                // Specify with worksheet name should have which import.
                $worksheets[$index] = $sheetImport;
            }

            // Load specific sheets.
            if (method_exists($this->reader, 'setLoadSheetsOnly')) {
                $this->reader->setLoadSheetsOnly(
                    collect($worksheetNames)->intersect(array_keys($worksheets))->values()->all()
                );
            }
        } else {
            // Each worksheet the same import class.
            foreach ($worksheetNames as $name) {
                $worksheets[$name] = $import;
            }
        }

        return $worksheets;
    }

	/**
	 * @param object $import
	 * @param object $sheetImport
	 * @param string|int $sheetName
	 * @return Sheet|null
	 *
	 * @throws SheetNotFoundException
	 */
    protected function getSheet(object $import, object $sheetImport, string|int $sheetName): Sheet|null
    {
        try {
            return Sheet::make($this->reader, $sheetName);
        } catch (SheetNotFoundException $e) {
            if ($import instanceof SkipsUnknownSheets) {
                $import->onUnknownSheet($sheetName);

                return null;
            }

            if ($sheetImport instanceof SkipsUnknownSheets) {
                $sheetImport->onUnknownSheet($sheetName);

                return null;
            }

            throw $e;
        }
    }

    /**
     * @param  object  $import
     * @return array
     */
    private function buildSheetImports(object $import): array
    {
        $sheetImports = [];
        if ($import instanceof WithMultipleSheets) {
            $sheetImports = $import->sheets();
        } else {
			$sheetImports[] = $import;
		}
        return $sheetImports;
    }

	/**
	 * @param object $import
	 * @param string|UploadedFile $filePath
	 * @param string|null $readerType
	 * @param string|null $disk
	 * @return \Vtiful\Kernel\Excel
	 *
	 */
    private function getReader(object $import, string|UploadedFile $filePath, string|null $readerType = null, string|null $disk = null): \Vtiful\Kernel\Excel
    {

        if ($import instanceof WithEvents) {
            $this->registerListeners($import->registerEvents());
        }

        $fileExtension     = pathinfo($filePath, PATHINFO_EXTENSION);
        $temporaryFile     = $this->temporaryFileFactory->makeLocal(null, $fileExtension);
        $this->currentFile = $temporaryFile->copyFrom(
            $filePath,
            $disk
        );
		return (new \Vtiful\Kernel\Excel(['path' => $this->currentFile->getDirName()]))->openFile($this->currentFile->getFileName());
    }

    /**
     * Garbage collect.
     */
    private function garbageCollect(): void
    {
        $this->clearListeners();

        // Force garbage collecting
        unset($this->sheetImports, $this->reader);

        $this->currentFile->delete();
    }
}

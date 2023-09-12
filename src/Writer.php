<?php

namespace Mckue\Excel;

use Mckue\Excel\Concerns\WithEvents;
use Mckue\Excel\Concerns\WithProperties;
use Mckue\Excel\Concerns\WithTitle;
use Mckue\Excel\Events\BeforeExport;
use Mckue\Excel\Events\BeforeWriting;
use Mckue\Excel\Files\RemoteTemporaryFile;
use Mckue\Excel\Files\TemporaryFile;
use Mckue\Excel\Files\TemporaryFileFactory;
use Mckue\Excel\Concerns\WithMultipleSheets;
use Mckue\Excel\Exceptions\SheetNotFoundException;

class Writer
{
	use DelegatedMacroable, HasEventBus;
	/**
	 * @var \Vtiful\Kernel\Excel
	 */
	protected \Vtiful\Kernel\Excel $spreadsheet;

	/**
	 * @var object
	 */
	protected object $exportable;

	/**
	 * @var TemporaryFileFactory
	 */
	protected TemporaryFileFactory $temporaryFileFactory;

	/**
	 * @param  TemporaryFileFactory  $temporaryFileFactory
	 */
	public function __construct(TemporaryFileFactory $temporaryFileFactory)
	{
		$this->temporaryFileFactory = $temporaryFileFactory;
	}

	/**
	 * @param object $export
	 * @param string $writerType
	 * @return TemporaryFile
	 * @throws SheetNotFoundException
	 */
	public function export(object $export, string $writerType): TemporaryFile
	{
		$temporaryFile = $this->temporaryFileFactory->makeLocal(null, strtolower($writerType));

		$this->open($export, $temporaryFile);

		$sheetExports = [$export];
		if ($export instanceof WithMultipleSheets) {
			$sheetExports = $export->sheets();
		}

		foreach ($sheetExports as $sheetExport) {
			$this->addNewSheet($sheetExport->title())->export($sheetExport);
		}

		return $this->write($export, $temporaryFile, $writerType);
	}

	/**
	 * @param object $export
	 * @param TemporaryFile $temporaryFile
	 * @return $this
	 * @throws SheetNotFoundException
	 */
	public function open(object $export, TemporaryFile $temporaryFile): self
	{
		$this->exportable = $export;

		if ($export instanceof WithEvents) {
			$this->registerListeners($export->registerEvents());
		}

		$this->spreadsheet = new \Vtiful\Kernel\Excel(['path'=>$temporaryFile->getDirName()]);

		//多sheet必须给表头名字
		$sheetName = $export instanceof WithTitle ? $export->title() : 'Sheet1';
		if ($export instanceof WithMultipleSheets) {
			$sheetExports = $export->sheets();
			$sheetExport = reset($sheetExports);
			if (! $sheetExport instanceof WithTitle) {
				throw SheetNotFoundException::byTitle();
			}
			$sheetName = $sheetExport->title();
		}

		if (config('mckue-excel.exports.model') === Excel::MODE_NORMAL) {
			$this->spreadsheet->fileName($temporaryFile->getFileName(), $sheetName);
		} else {
			//wps打不开需要给false
			$this->spreadsheet->constMemory($temporaryFile->getFileName(), $sheetName, false);
		}

		$this->handleDocumentProperties($export);

		//if ($export instanceof WithBackgroundColor) {
		//    $defaultStyle    = $this->spreadsheet->getDefaultStyle();
		//    $backgroundColor = $export->backgroundColor();
		//
		//    if (is_string($backgroundColor)) {
		//        $defaultStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($backgroundColor);
		//    }
		//
		//    if (is_array($backgroundColor)) {
		//        $defaultStyle->applyFromArray(['fill' => $backgroundColor]);
		//    }
		//
		//    if ($backgroundColor instanceof Color) {
		//        $defaultStyle->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor($backgroundColor);
		//    }
		//}

		//if ($export instanceof WithDefaultStyles) {
		//    $defaultStyle = $this->spreadsheet->getDefaultStyle();
		//    $styles       = $export->defaultStyles($defaultStyle);
		//
		//    if (is_array($styles)) {
		//        $defaultStyle->applyFromArray($styles);
		//    }
		//}

		$this->raise(new BeforeExport($this, $this->exportable));

		return $this;
	}

	/**
	 * @param  object  $export
	 * @param  TemporaryFile  $temporaryFile
	 * @param  string  $writerType
	 * @return TemporaryFile
	 */
	public function write(object $export, TemporaryFile $temporaryFile, string $writerType): TemporaryFile
	{
		$this->exportable = $export;

		//$this->spreadsheet->setActiveSheetIndex(0);

		$this->raise(new BeforeWriting($this, $this->exportable));

		$this->spreadsheet->output();

		if ($temporaryFile instanceof RemoteTemporaryFile) {
			$temporaryFile->updateRemote();
			$temporaryFile->deleteLocalCopy();
		}

		$this->clearListeners();

		$this->spreadsheet->close();

		unset($this->spreadsheet);

		return $temporaryFile;
	}

	/**
	 * @param  string  $sheetName
	 * @return Sheet
	 */
	public function addNewSheet(string $sheetName):Sheet
	{
		if (! $this->spreadsheet->existSheet($sheetName)) {
			$this->spreadsheet->addSheet($sheetName);
		}
		return new Sheet($this->spreadsheet);
	}

	/**
	 * @return \Vtiful\Kernel\Excel
	 */
	public function getDelegate(): \Vtiful\Kernel\Excel
	{
		return $this->spreadsheet;
	}


	/**
	 * @param  int  $sheetIndex
	 * @return Sheet
	 *
	 */
	public function getSheetByIndex(int $sheetIndex): Sheet
	{
		return new Sheet($this->getDelegate()->getSheet($sheetIndex));
	}

	/**
	 * @param  string  $concern
	 * @return bool
	 */
	public function hasConcern(string $concern): bool
	{
		return $this->exportable instanceof $concern;
	}

	/**
	 * @param  object  $export
	 */
	protected function handleDocumentProperties(object $export): void
	{
		$properties = config('mckue-excel.exports.properties', []);

		if ($export instanceof WithProperties) {
			$properties = array_merge($properties, $export->properties());
		}

		$props = $this->spreadsheet;

		foreach (array_filter($properties) as $property => $value) {
			switch ($property) {
				case 'gridline':
					$props->gridline($value);
					break;
				case 'zoom':
					$props->zoom($value);
					break;
			}
		}
	}
}

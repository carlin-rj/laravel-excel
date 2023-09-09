<?php
namespace Mckue\Excel;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Mckue\Excel\Files\TemporaryFile;
use Mckue\Excel\Helpers\FileTypeDetector;
use Mckue\Excel\Files\Filesystem;

class Excel implements Exporter, Importer
{
	/** @var int 固定内存模式 */
	public const MODE_MEMORY = 1;

	/** @var int 普通模式 */
	public const MODE_NORMAL = 0;


	public const XLSX     = 'Xlsx';

	public const CSV      = 'Csv';

	public const XLS      = 'Xls';

	/**
	 * @var Writer
	 */
	protected Writer $writer;

	protected Reader $reader;

	/**
	 * @var Filesystem
	 */
	protected Filesystem $filesystem;

	public function __construct(
		Writer $writer,
		Reader $reader,
		Filesystem $filesystem
	) {
		$this->writer       = $writer;
		$this->reader       = $reader;
		$this->filesystem   = $filesystem;
	}

	public function store(object $export, string $filePath, string $disk = null, string $writerType = null, array $diskOptions = []): bool
	{
		$temporaryFile = $this->export($export, $filePath, $writerType);

		$exported = $this->filesystem->disk($disk, $diskOptions)->copy(
			$temporaryFile,
			$filePath
		);

		$temporaryFile->delete();

		return $exported;
	}

	public function import(object $import, string|UploadedFile $filePath, string $disk = null, string $readerType = null): Reader
	{
		$readerType = FileTypeDetector::detect($filePath, $readerType);
		return $this->reader->read($import, $filePath, $readerType, $disk);
	}

	public function toArray(object $import, string|UploadedFile $filePath, string $disk = null, string $readerType = null): array
	{
		$readerType = FileTypeDetector::detect($filePath, $readerType);

		return $this->reader->toArray($import, $filePath, $readerType, $disk);
	}

	public function toCollection(object $import, string|UploadedFile $filePath, string $disk = null, string $readerType = null): Collection
	{
		$readerType = FileTypeDetector::detect($filePath, $readerType);

		return $this->reader->toCollection($import, $filePath, $readerType, $disk);
	}

	protected function export($export, string $fileName, string $writerType = null): TemporaryFile
	{
		$writerType = FileTypeDetector::detectStrict($fileName, $writerType);

		return $this->writer->export($export, $writerType);
	}

	public function download($export, string $fileName, string $writerType = null, array $headers = []): BinaryFileResponse
	{
		return response()->download(
			$this->export($export, $fileName, $writerType)->getLocalPath(),
			$fileName,
			$headers
		)->deleteFileAfterSend();
	}

	public function raw(object $export, string $writerType): string
	{
		$temporaryFile = $this->writer->export($export, $writerType);

		$contents = $temporaryFile->contents();
		$temporaryFile->delete();

		return $contents;
	}
}

<?php

namespace Mckue\Excel;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

interface Exporter
{
	/**
	 * @param object $export
	 * @param string $filePath
	 * @param string|null $disk
	 * @param string|null $writerType
	 * @param array $diskOptions
	 * @return bool
	 */
    public function store(object $export, string $filePath, string $disk = null, string $writerType = null, array $diskOptions = []): bool;

	/**
	 * @param object $export
	 * @param string $fileName
	 * @param string|null $writerType
	 * @param array $headers
	 * @return BinaryFileResponse
	 */
	public function download($export, string $fileName, string $writerType = null, array $headers = []): BinaryFileResponse;

	/**
	 * @param object $export
	 * @param string $writerType
	 * @return string
	 */
	public function raw(object $export, string $writerType): string;
}

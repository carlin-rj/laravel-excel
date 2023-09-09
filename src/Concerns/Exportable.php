<?php

namespace Mckue\Excel\Concerns;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Mckue\Excel\Exceptions\NoFilenameGivenException;
use Mckue\Excel\Exceptions\NoFilePathGivenException;
use Mckue\Excel\Exporter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

trait Exportable
{
	public function download(string $fileName = null, string $writerType = null, array $headers = null): BinaryFileResponse
	{
		$headers    = $headers ?? $this->headers ?? [];
		$fileName   = $fileName ?? $this->fileName ?? null;
		$writerType = $writerType ?? $this->writerType ?? null;

		if (null === $fileName) {
			throw new NoFilenameGivenException();
		}

		return $this->getExporter()->download($this, $fileName, $writerType, $headers);
	}

	/**
	 * @param string|null $filePath
	 * @param string|null $disk
	 * @param string|null $writerType
	 * @param mixed|array $diskOptions
	 * @return bool
	 *
	 */
    public function store(string $filePath = null, string $disk = null, string $writerType = null, array $diskOptions = []): bool
    {
        $filePath = $filePath ?? $this->filePath ?? null;

        if (null === $filePath) {
            throw NoFilePathGivenException::export();
        }

        return $this->getExporter()->store(
            $this,
            $filePath,
            $disk ?? $this->disk ?? null,
            $writerType ?? $this->writerType ?? null,
            $diskOptions ?: $this->diskOptions ?? []
        );
    }

	/**
	 * @param $writerType
	 * @return string
	 */
	public function raw($writerType = null): string
	{
		$writerType = $writerType ?? $this->writerType ?? null;

		return $this->getExporter()->raw($this, $writerType);
	}

	/**
	 * Create an HTTP response that represents the object.
	 *
	 * @param Request $request
	 * @return Response
	 *
	 * @throws \Maatwebsite\Excel\Exceptions\NoFilenameGivenException
	 */
	public function toResponse(Request $request): Response
	{
		return $this->download();
	}

    /**
     * @return Exporter
     */
    private function getExporter(): Exporter
    {
        return app(Exporter::class);
    }
}

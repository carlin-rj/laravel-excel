<?php

namespace Mckue\Excel\Concerns;

use Illuminate\Console\OutputStyle;
use Illuminate\Support\Collection;
use Mckue\Excel\Exceptions\NoFilePathGivenException;
use Mckue\Excel\Importer;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Mckue\Excel\Reader;

trait Importable
{
    /**
     * @var OutputStyle|null
     */
    protected ?OutputStyle $output;

    /**
     * @param  string|UploadedFile|null  $filePath
     * @param  string|null  $disk
     * @param  string|null  $readerType
     * @return Reader
     *
     * @throws NoFilePathGivenException
     */
    public function import(string|UploadedFile|null $filePath = null, string $disk = null, string $readerType = null): Reader
	{
        $filePath = $this->getFilePath($filePath);

        return $this->getImporter()->import(
            $this,
            $filePath,
            $disk ?? $this->disk ?? null,
            $readerType ?? $this->readerType ?? null
        );
    }

    /**
     * @param  string|UploadedFile|null  $filePath
     * @param  string|null  $disk
     * @param  string|null  $readerType
     * @return array
     *
     * @throws NoFilePathGivenException
     */
    public function toArray(string|UploadedFile|null $filePath = null, string $disk = null, string $readerType = null): array
    {
        $filePath = $this->getFilePath($filePath);

        return $this->getImporter()->toArray(
            $this,
            $filePath,
            $disk ?? $this->disk ?? null,
            $readerType ?? $this->readerType ?? null
        );
    }

    /**
     * @param  string|UploadedFile|null  $filePath
     * @param  string|null  $disk
     * @param  string|null  $readerType
     * @return Collection
     *
     * @throws NoFilePathGivenException
     */
    public function toCollection(string|UploadedFile|null $filePath = null, string $disk = null, string $readerType = null): Collection
    {
        $filePath = $this->getFilePath($filePath);

        return $this->getImporter()->toCollection(
            $this,
            $filePath,
            $disk ?? $this->disk ?? null,
            $readerType ?? $this->readerType ?? null
        );
    }

    /**
     * @param  OutputStyle  $output
     * @return $this
     */
    public function withOutput(OutputStyle $output): self
	{
        $this->output = $output;

        return $this;
    }

    /**
     * @return OutputStyle
     */
    public function getConsoleOutput(): OutputStyle
    {
        if (!$this->output instanceof OutputStyle) {
            $this->output = new OutputStyle(new StringInput(''), new NullOutput());
        }

        return $this->output;
    }

    /**
     * @param  UploadedFile|string|null  $filePath
     * @return UploadedFile|string
     *
     * @throws NoFilePathGivenException
     */
    private function getFilePath(string|UploadedFile|null $filePath = null): string|UploadedFile
	{
        $filePath = $filePath ?? $this->filePath ?? null;

        if (null === $filePath) {
            throw NoFilePathGivenException::import();
        }

        return $filePath;
    }

    /**
     * @return Importer
     */
    private function getImporter(): Importer
    {
        return app(Importer::class);
    }
}

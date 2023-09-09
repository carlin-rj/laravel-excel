<?php

namespace Mckue\Excel\Concerns;

use Illuminate\Foundation\Bus\PendingDispatch;
use Mckue\Excel\Exceptions\NoFilenameGivenException;
use Mckue\Excel\Exceptions\NoFilePathGivenException;
use Mckue\Excel\Exporter;

trait Exportable
{
    /**
     * @param  string  $filePath
     * @param  string|null  $disk
     * @param  string|null  $writerType
     * @param  mixed  $diskOptions
     * @return bool|PendingDispatch
     *
     * @throws NoFilePathGivenException
     */
    public function store(string $filePath = null, string $disk = null, string $writerType = null, $diskOptions = [])
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
     * @return Exporter
     */
    private function getExporter(): Exporter
    {
        return app(Exporter::class);
    }
}

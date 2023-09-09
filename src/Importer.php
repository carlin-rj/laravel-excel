<?php

namespace Mckue\Excel;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface Importer
{
    /**
     * @param  object  $import
     * @param  string|UploadedFile  $filePath
     * @param  string|null  $disk
     * @param  string|null  $readerType
     * @return Reader
     */
    public function import(object $import, string|UploadedFile $filePath, string $disk = null, string $readerType = null): Reader;

    /**
     * @param  object  $import
     * @param  string|UploadedFile  $filePath
     * @param  string|null  $disk
     * @param  string|null  $readerType
     * @return array
     */
    public function toArray(object $import, string|UploadedFile $filePath, string $disk = null, string $readerType = null): array;

    /**
     * @param  object  $import
     * @param  string|UploadedFile  $filePath
     * @param  string|null  $disk
     * @param  string|null  $readerType
     * @return Collection
     */
    public function toCollection(object $import, string|UploadedFile $filePath, string $disk = null, string $readerType = null): Collection;

}

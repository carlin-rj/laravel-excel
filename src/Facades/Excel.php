<?php

namespace Mckue\Excel\Facades;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Mckue\Excel\Excel as BaseExcel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @method static BinaryFileResponse download(object $export, string $fileName, string $writerType = null, array $headers = [])
 * @method static bool store(object $export, string $filePath, string $disk = null, string $writerType = null, $diskOptions = [])
 * @method static string raw(object $export, string $writerType)
 * @method static BaseExcel import(object $import, string|UploadedFile $filePath, string $disk = null, string $readerType = null)
 * @method static array toArray(object $import, string|UploadedFile $filePath, string $disk = null, string $readerType = null)
 * @method static Collection toCollection(object $import, string|UploadedFile $filePath, string $disk = null, string $readerType = null)
 */
class Excel extends Facade
{

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'excel';
    }
}

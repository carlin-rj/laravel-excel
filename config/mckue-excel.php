<?php

use Mckue\Excel\Excel;

return [
    'exports' => [
        /**
         * 1.固定内存模式(速度慢) 2.普通模式(内存占用大，速度快)
         */
        'model' => Excel::MODE_MEMORY,
        /*
        |--------------------------------------------------------------------------
        | Chunk size
        |--------------------------------------------------------------------------
        |
        | When using FromQuery, the query is automatically chunked.
        | Here you can specify how big the chunk should be.
        |
        */
        'chunk_size' => 1000,

        /*
        |--------------------------------------------------------------------------
        | Worksheet properties
        |--------------------------------------------------------------------------
        |
        */
        'properties' => [

        ],
    ],

    //'imports' => [
    //
    //
    //
    //],

    /*
    |--------------------------------------------------------------------------
    | Extension detector
    |--------------------------------------------------------------------------
    |
    | Configure here which writer/reader type should be used when the package
    | needs to guess the correct type based on the extension alone.
    |
    */
    'extension_detector' => [
        'xlsx' => Excel::XLSX,
        'xlsm' => Excel::XLSX,
        'xltx' => Excel::XLSX,
        'xltm' => Excel::XLSX,
        'xls'  => Excel::XLS,
        'xlt'  => Excel::XLS,

        /*
        |--------------------------------------------------------------------------
        | PDF Extension
        |--------------------------------------------------------------------------
        |
        | Configure here which Pdf driver should be used by default.
        | Available options: Excel::MPDF | Excel::TCPDF | Excel::DOMPDF
        |
        */
    ],

    /*
    |--------------------------------------------------------------------------
    | Transaction Handler
    |--------------------------------------------------------------------------
    |
    | By default the import is wrapped in a transaction. This is useful
    | for when an import may fail and you want to retry it. With the
    | transactions, the previous import gets rolled-back.
    |
    | You can disable the transaction handler by setting this to null.
    | Or you can choose a custom made transaction handler here.
    |
    | Supported handlers: null|db
    |
    */
    'transactions' => [
        'handler' => 'null',
        'db'      => [
            'connection' => null,
        ],
    ],

    'temporary_files' => [

        /*
        |--------------------------------------------------------------------------
        | Local Temporary Path
        |--------------------------------------------------------------------------
        |
        | When exporting and importing files, we use a temporary file, before
        | storing reading or downloading. Here you can customize that path.
        |
        */
        'local_path' => storage_path('framework/cache/laravel-excel'),

        /*
        |--------------------------------------------------------------------------
        | Remote Temporary Disk
        |--------------------------------------------------------------------------
        |
        | When dealing with a multi server setup with queues in which you
        | cannot rely on having a shared local temporary path, you might
        | want to store the temporary file on a shared disk. During the
        | queue executing, we'll retrieve the temporary file from that
        | location instead. When left to null, it will always use
        | the local path. This setting only has effect when using
        | in conjunction with queued imports and exports.
        |
        */
        'remote_disk'   => null,
        'remote_prefix' => null,

        /*
        |--------------------------------------------------------------------------
        | Force Resync
        |--------------------------------------------------------------------------
        |
        | When dealing with a multi server setup as above, it's possible
        | for the clean up that occurs after entire queue has been run to only
        | cleanup the server that the last AfterImportJob runs on. The rest of the server
        | would still have the local temporary file stored on it. In this case your
        | local storage limits can be exceeded and future imports won't be processed.
        | To mitigate this you can set this config value to be true, so that after every
        | queued chunk is processed the local temporary file is deleted on the server that
        | processed it.
        |
        */
        'force_resync_remote' => null,
    ],
];

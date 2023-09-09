<?php

namespace Mckue\Excel\Concerns;

interface SkipsUnknownSheets
{
    /**
     * @param  string|int  $sheetName
     */
    public function onUnknownSheet($sheetName);
}

<?php

namespace Mckue\Excel\Concerns;

interface OnEachRow
{
    /**
     * @param  array  $row
     */
    public function onRow(array $row);
}

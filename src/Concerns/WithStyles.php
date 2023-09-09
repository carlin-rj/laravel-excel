<?php

namespace Mckue\Excel\Concerns;

use Vtiful\Kernel\Excel;

interface WithStyles
{
    public function styles(Excel $sheet);
}

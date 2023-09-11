<?php

namespace Mckue\Excel\Concerns;

use Vtiful\Kernel\Excel;

interface WithColumnFormatting
{
    /**
     * 通过worksheet对象设置所需要的单元格格式
     */
    public function formatColumns(Excel $worksheet);
}

<?php

namespace Mckue\Excel\Concerns;

use Vtiful\Kernel\Excel;

interface WithRowFormatting
{
    /**
     * 设置那一列需要设置格式
     */
    public function formatRows(): array;

    /**
     * 回调一列并且通过worksheet对象设置所需要的单元格格式
     *
     * @author: whj
     *
     * @date: 2023/9/9 09:40
     */
    public function formatRowCallback(string $column, Excel $worksheet): array;
}

<?php

namespace Mckue\Excel\Concerns;

interface WithMergeCells
{
    /**
     * @return array
     */
    public function mergeCells(): array;
}

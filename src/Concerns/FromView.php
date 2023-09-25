<?php

namespace Mckue\Excel\Concerns;

use Illuminate\Contracts\View\View;

interface FromView
{
    /**
     * @return View
     */
    public function view(): View;
}

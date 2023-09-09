<?php

namespace Mckue\Excel\Events;

use Mckue\Excel\Sheet;

class BeforeSheet extends Event
{
    /**
     * @var Sheet
     */
    public Sheet $sheet;

    /**
     * @var object
     */
    private object $exportable;

    /**
     * @param  Sheet  $sheet
     * @param  object  $exportable
     */
    public function __construct(Sheet $sheet, object $exportable)
    {
        $this->sheet       = $sheet;
        $this->exportable  = $exportable;
    }

    /**
     * @return Sheet
     */
    public function getSheet(): Sheet
    {
        return $this->sheet;
    }

    /**
     * @return object
     */
    public function getConcernable(): object
    {
        return $this->exportable;
    }

    /**
     * @return mixed
     */
    public function getDelegate(): Sheet
    {
        return $this->sheet;
    }
}

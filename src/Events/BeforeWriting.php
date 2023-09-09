<?php

namespace Mckue\Excel\Events;

use Mckue\Excel\Writer;

class BeforeWriting extends Event
{
    /**
     * @var Writer
     */
    public Writer $writer;

    /**
     * @var object
     */
    private object $exportable;

    /**
     * @param  Writer  $writer
     * @param  object  $exportable
     */
    public function __construct(Writer $writer, object $exportable)
    {
        $this->writer     = $writer;
        $this->exportable = $exportable;
    }

    /**
     * @return Writer
     */
    public function getWriter(): Writer
    {
        return $this->writer;
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
    public function getDelegate(): Writer
    {
        return $this->writer;
    }
}

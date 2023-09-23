<?php

namespace Mckue\Excel\Exceptions;

use LogicException;

class ConcernConflictException extends LogicException implements LaravelExcelException
{
    /**
     * @return ConcernConflictException
     */
    public static function queryOrCollectionOrGenerator()
    {
        return new static('Cannot use FromQuery, FromArray or FromCollection or FromGenerator on the same sheet.');
    }
}

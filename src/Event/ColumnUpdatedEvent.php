<?php

namespace App\Event;

use App\Entity\Col;
use Symfony\Contracts\EventDispatcher\Event;

class ColumnUpdatedEvent extends Event
{
    public const NAME = 'column.updated';

    public function __construct(
        private Col $column
    ) {}

    public function getColumn(): Col
    {
        return $this->column;
    }
}
<?php

namespace App\Event;

use App\Entity\Board;
use Symfony\Contracts\EventDispatcher\Event;

class BoardUpdatedEvent extends Event
{
    public const NAME = 'board.updated';

    public function __construct(
        private Board $board
    ) {}

    public function getBoard(): Board
    {
        return $this->board;
    }
}
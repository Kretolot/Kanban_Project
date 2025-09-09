<?php

namespace App\Event;

use App\Entity\Task;
use Symfony\Contracts\EventDispatcher\Event;

class TaskUpdatedEvent extends Event
{
    public const NAME = 'task.updated';

    public function __construct(
        private Task $task
    ) {}

    public function getTask(): Task
    {
        return $this->task;
    }
}
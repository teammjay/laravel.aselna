<?php

namespace App\Utils;

use UnderflowException;

class Queue
{
    private $items = [];

    // Add an element to the end of the queue
    public function enqueue($item) {
        $this->items[] = $item;
    }

    // Remove an element from the front of the queue
    public function dequeue() {
        if ($this->isEmpty()) {
            throw new UnderflowException("Queue is empty");
        }
        return array_shift($this->items);
    }

    // Peek at the front of the queue without removing it
    public function peek() {
        if ($this->isEmpty()) {
            throw new UnderflowException("Queue is empty");
        }
        return $this->items[0];
    }

    // Check if the queue is empty
    public function isEmpty() {
        return empty($this->items);
    }

    // Get the size of the queue
    public function size() {
        return count($this->items);
    }

    // Print the queue
    public function printQueue() {
        echo implode(", ", $this->items) . PHP_EOL;
    }
}

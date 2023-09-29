<?php
declare(strict_types=1);

namespace App\Lib\SpanIterators;

/**
 * @template-implements \Iterator<int>
 */
class DailySpanIterator implements \Iterator
{
    private array $months = [0];
    private int $count = 1;
    private int $index = 0;

    /**
     * @return int
     */
    public function current(): mixed
    {
        return $this->months[$this->index];
    }

    /**
     * @return void
     */
    public function next(): void
    {
        $this->index++;
    }

    /**
     * @return void
     */
    public function rewind(): void
    {
        $this->index = 0;
    }

    /**
     * @return int
     */
    public function key(): int
    {
        return $this->index;
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        return isset($this->months[$this->key()]);
    }

    /**
     * @return void
     */
    public function reverse(): void
    {
        $this->months = array_reverse($this->months);
        $this->rewind();
    }

    /**
     * @return int
     */
    public function totalCount(): int
    {
        return $this->count;
    }
}

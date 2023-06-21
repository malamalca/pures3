<?php
declare(strict_types=1);

namespace App\Lib;

// for internal use
class EvalMathStack
{
    /**
     * @var array $stack
     */
    public $stack = [];

    /**
     * @var array $tokens
     */
    public $tokens = [];

    /**
     * @var int $count
     */
    public $count = 0;

    /**
     * Push value to stack
     *
     * @param mixed $val Value
     * @param string $token Token
     * @return void
     */
    public function push($val, $token = null)
    {
        $this->stack[$this->count] = $val;
        if (isset($token)) {
            $this->tokens[$this->count] = $token;
        }
        $this->count++;
    }

    /**
     * Pop value from stack
     *
     * @return mixed
     */
    public function pop()
    {
        if ($this->count > 0) {
            $this->count--;

            return $this->stack[$this->count];
        }

        return null;
    }

    /**
     * Get last value
     *
     * @param int $n Counter
     * @return bool|string
     */
    public function last($n = 1)
    {
        if (isset($this->stack[$this->count - $n])) {
            return $this->stack[$this->count - $n];
        } else {
            return false;
        }
    }
}

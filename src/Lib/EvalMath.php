<?php
declare(strict_types=1);

namespace App\Lib;

/*
================================================================================

EvalMath - PHP Class to safely evaluate math expressions
Copyright (C) 2005 Miles Kaufmann http://www.twmagic.com/

================================================================================

NAME
    EvalMath - safely evaluate math expressions

SYNOPSIS
      include('evalmath.class.php');
      $m = new EvalMath;
      // basic evaluation:
      $result = $m->evaluate('2+2');
      // supports: order of operation; parentheses; negation; built-in functions
      $result = $m->evaluate('-8(5/2)^2*(1-sqrt(4))-8');
      // create your own variables
      $m->evaluate('a = e^(ln(pi))');
      // or functions
      $m->evaluate('f(x,y) = x^2 + y^2 - 2x*y + 1');
      // and then use them
      $result = $m->evaluate('3*f(42,a)');

DESCRIPTION
    Use the EvalMath class when you want to evaluate mathematical expressions
    from untrusted sources.  You can define your own variables and functions,
    which are stored in the object.  Try it, it's fun!

METHODS
    $m->evalute($expr)
        Evaluates the expression and returns the result.  If an error occurs,
        prints a warning and returns false.  If $expr is a function assignment,
        returns true on success.

    $m->e($expr)
        A synonym for $m->evaluate().

    $m->vars()
        Returns an associative array of all user-defined variables and values.

    $m->funcs()
        Returns an array of all user-defined functions.

PARAMETERS
    $m->suppress_errors
        Set to true to turn off warnings when evaluating expressions

    $m->last_error
        If the last evaluation failed, contains a string describing the error.
        (Useful when suppress_errors is on).

AUTHOR INFORMATION
    Copyright 2005, Miles Kaufmann.

LICENSE
    Redistribution and use in source and binary forms, with or without
    modification, are permitted provided that the following conditions are
    met:

    1   Redistributions of source code must retain the above copyright
        notice, this list of conditions and the following disclaimer.
    2.  Redistributions in binary form must reproduce the above copyright
        notice, this list of conditions and the following disclaimer in the
        documentation and/or other materials provided with the distribution.
    3.  The name of the author may not be used to endorse or promote
        products derived from this software without specific prior written
        permission.

    THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR
    IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
    WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
    DISCLAIMED. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT,
    INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
    (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
    SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
    HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT,
    STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
    ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
    POSSIBILITY OF SUCH DAMAGE.

*/

class EvalMath
{
    /**
     * @var \App\Lib\EvalMath|null $instance
     */
    protected static $instance = null;

    /**
     * @var bool $suppress_errors
     * */
    private $suppress_errors = true;

    /**
     * @var string|null $last_error
     */
    private $last_error = null;

    /**
     * @var array $options
     */
    private $options = ['decimalSeparator' => '.', 'thousandsSeparator' => ''];

    /**
     * Variables and constants
     *
     * @var array $v
     */
    private $v = ['e' => 2.71, 'pi' => 3.14];

    /**
     * User defined functions and constants
     *
     * @var array $f
     */
    private $f = ['pow' => ['args' => ['b', 'p'], 'func' => ['b', 'p', '^']]];

    /**
     * Build-in constants
     *
     * @var array $vb
     */
    private $vb = ['e', 'pi']; // constants

    /**
     * Build-in functions
     *
     * @var array $fb
     */
    private $fb = [
        'sin', 'sinh', 'arcsin', 'asin', 'arcsinh', 'asinh',
        'cos', 'cosh', 'arccos', 'acos', 'arccosh', 'acosh',
        'tan', 'tanh', 'arctan', 'atan', 'arctanh', 'atanh',
        'sqrt', 'abs', 'ln', 'log', 'exp',
        'degtorad', 'radtodeg',
    ];

    /**
     * Returns object instance
     *
     * @param array $options Options
     * @return \App\Lib\EvalMath
     */
    public static function getInstance($options = [])
    {
        if (static::$instance === null) {
            static::$instance = new EvalMath($options);
        }

        return static::$instance;
    }

    /**
     * Constructor
     *
     * @param array $options Options
     * @return void
     */
    protected function __construct($options = [])
    {
        // make the variables a little more accurate
        $this->v['pi'] = pi();
        $this->v['e'] = exp(1);

        $locale_info = localeconv();
        $this->options['decimalSeparator'] = $locale_info['decimal_point'];
        $this->options['thousandsSeparator'] = $locale_info['thousands_sep'];

        $this->options = array_replace_recursive($this->options, (array)$options);
    }

    /**
     * Destory singleton instance
     *
     * @return void
     */
    public function destroy()
    {
        static::$instance = null;
    }

    /**
     * Get specified option
     *
     * @param string $name Option name
     * @return mixed
     */
    public function getOption($name)
    {
        $ret = false;
        if (isset($this->options[$name])) {
            $ret = $this->options[$name];
        }

        return $ret;
    }

    /**
     * Set options
     *
     * @param array $options Options
     * @return \App\Lib\EvalMath|null
     */
    public function setOptions($options)
    {
        $this->options = array_merge($this->options, (array)$options);

        return static::$instance;
    }

    /**
     * Get error
     *
     * @return mixed
     */
    public function getError()
    {
        return $this->last_error;
    }

    /**
     * Private clone method to prevent cloning of the instance of the
     * *Singleton* instance.
     *
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * Shortcut method for evaluation
     *
     * @param string $expr Mathematical expression.
     * @return mixed
     */
    public function e($expr)
    {
        return $this->evaluate($expr);
    }

    /**
     * General evaluation method. Tries to guess weather it is variable assigment, function assigment
     * or expression.
     *
     * @param string $expr Mathematical expression.
     * @return mixed
     */
    public function evaluate($expr)
    {
        $this->last_error = null;
        $expr = trim((string)$expr);
        if (substr($expr, -1, 1) == ';') {
            $expr = substr($expr, 0, strlen($expr) - 1); // strip semicolons at the end
        }
        if (substr($expr, 0, 1) == '=') {
            $expr = substr($expr, 1, strlen($expr) - 1); // strip = at the beginning
        }
        //===============
        // is it a variable assignment?
        if (preg_match('/^\s*([a-zA-Z]\w*)\s*=\s*(.+)$/', $expr, $matches)) {
            if (in_array($matches[1], $this->vb)) { // make sure we're not assigning to a constant
                return $this->trigger("cannot assign to constant '$matches[1]'");
            }
            $tokens = $this->nfx($matches[2]);
            if ($tokens) {
                $tmp = $this->pfx($tokens);
                if ($tmp === false) {
                    return false; // get the result and make sure it's good
                }
            } else {
                return false;
            }
            $this->v[$matches[1]] = $tmp; // if so, stick it in the variable array

            return $this->v[$matches[1]]; // and return the resulting value
        //===============
        // is it a function assignment?
        } elseif (
            preg_match(
                '/^\s*([a-zA-Z]\w*)\s*\(\s*([a-zA-Z]\w*(?:\s*,\s*[a-zA-Z]\w*)*)\s*\)\s*=\s*(.+)$/',
                $expr,
                $matches
            )
        ) {
            $fnn = $matches[1]; // get the function name
            if (in_array($matches[1], $this->fb)) { // make sure it isn't built in
                return $this->trigger("cannot redefine built-in function '$matches[1]()'");
            }
            $args = explode(',', preg_replace("/\s+/", '', $matches[2])); // get the arguments
            $stack = $this->nfx($matches[3]);
            if ($stack === false) {
                return false; // see if it can be converted to postfix
            }

            $stackSize = count($stack);
            for ($i = 0; $i < $stackSize; $i++) { // freeze the state of the non-argument variables
                $token = $stack[$i];
                if (preg_match('/^[a-zA-Z]\w*$/', $token) && !in_array($token, $args)) {
                    if (array_key_exists($token, $this->v)) {
                        $stack[$i] = $this->v[$token];
                    } else {
                        return $this->trigger("undefined variable '$token' in function definition");
                    }
                }
            }
            $this->f[$fnn] = ['args' => $args, 'func' => $stack];

            return true;
        //===============
        } else {
            $tokens = $this->nfx($expr);
            if ($tokens) {
                return $this->pfx($tokens); // straight up evaluation, woo
            } else {
                return false;
            }
        }
    }

    /**
     * Evaluates expression in local format.
     *
     * @param string $expr Math expression for evaluation.
     * @param array $lvars Array of local variables.
     * @return string|bool
     */
    public function evaluateExpression($expr, $lvars = [])
    {
        $tokens = $this->nfx($expr);

        if ($tokens) {
            return $this->pfx($tokens, $lvars);
        } else {
            return false;
        }
    }

    /**
     * Returns variables in specified expression
     * 20120429 :: miha nahtigal
     *
     * @param array $tokens Array of tokens
     * @return array
     */
    public function usedVars($tokens)
    {
        $ret = [];
        $vars = array_keys($this->v);
        foreach ($tokens as $token) {
            if (in_array($token, $vars) && ($token != 'pi') && ($token != 'e')) {
                $ret[] = $token;
            }
        }

        return $ret;
    }

    /**
     * Checks that a value is a valid float.
     * If no decimal point is found a false will be returned. The sign is optional.
     *
     * @param string|float|int $number Decimal or int number in local format
     * @param array $options Options
     * @return bool Success
     */
    public function isValidFloat($number, $options = [])
    {
        $settings = array_merge($this->options, (array)$options);

        $regex = '/^[-+]?(0|([1-9]([0-9]*)))(\\' . $settings['decimalSeparator'] . '{1}[0-9]{1,})?$/';

        return is_int($number) || is_float($number) || preg_match($regex, (string)$number);
    }

    /**
     * Converts decimal number with local separators to general representation eg. 56.78
     *
     * @param string|int|float $number Decimal or int number in local format
     * @param array $options Options which can override default settings. $options['foce'] -
     * @return float|null|string
     */
    public function delocalize($number, $options = [])
    {
        if ((is_float($number) || is_int($number)) && empty($options['force'])) {
            return $number;
        }

        $result = null;
        if ($this->isValidFloat($number, $options)) {
            $settings = array_merge($this->options, (array)$options);

            $pairs = ['(' => '', ')' => ''];
            if (!empty($settings['thousandsSeparator'])) {
                $pairs[$settings['thousandsSeparator']] = '';
            }
            if (!empty($settings['decimalSeparator'])) {
                $pairs[$settings['decimalSeparator']] = '.';
            }
            if (!empty($settings['currencySymbol'])) {
                $pairs[$settings['currencySymbol']] = '';
            }

            $result = trim(strtr((string)$number, $pairs));
            if (empty($options['force'])) {
                $result = (float)$result;
            }
        } else {
            $tokens = $this->nfx(substr((string)$number, 1));
            if ($tokens) {
                $result = '=' . $this->gfx($tokens, [], ['decimalSeparator' => '.']);
            }
        }

        return $result;
    }

    /**
     * Converts decimal number to local representation
     *
     * @param float|string $number Decimal or int number
     * @param array $options Options which can override default settings
     * @return float|string|bool
     */
    public function localize($number, $options = [])
    {
        $result = '';
        if ($number === '') {
            return $result;
        }

        if (is_numeric($number)) {
            $settings = array_merge($this->options, (array)$options);

            $whole = floor((float)$number);
            $decimal = round((float)($number - $whole), 4);
            $decimal_str = substr((string)$decimal, 2);
            if (empty($decimal_str)) {
                $decimal_str = 0;
            }

            $result = number_format($whole, 0, '.', $settings['thousandsSeparator']) .
                $settings['decimalSeparator'] .
                $decimal_str;
        } else {
            $tokens = $this->nfx(substr($number, 1), ['decimalSeparator' => '.', 'thousandsSeparator' => ',']);
            if ($tokens) {
                $localized = $this->gfx($tokens);
                if ($localized) {
                    $result = '=' . $localized;
                }
            }
        }

        return $result;
    }

    /**
     * Add variable to EvalMath
     *
     * @param string $name Variable name
     * @param mixed $value Variable value
     * @return void
     */
    public function setVar($name, $value)
    {
        $this->v[$name] = $value;
    }

    /**
     * Remove variable from EvalMath
     *
     * @param string $name Variable name
     * @return void
     */
    public function unsetVar($name)
    {
        unset($this->v[$name]);
    }

    /**
     * Fetch all variables defined in EvalMath
     *
     * @return array
     */
    public function vars()
    {
        $output = $this->v;
        unset($output['pi']);
        unset($output['e']);

        return $output;
    }

    /**
     * Fetch all functions defined in EvalMath
     *
     * @return array
     */
    public function funcs()
    {
        $output = [];
        foreach ($this->f as $fnn => $dat) {
            $output[] = $fnn . '(' . implode(',', $dat['args']) . ')';
        }

        return $output;
    }

    //===================== HERE BE INTERNAL METHODS ====================\\

    /**
     * Convert infix to postfix notation
     *
     * @param string $expr Expression
     * @param array $options Options
     * @return array|false
     */
    public function nfx($expr, $options = [])
    {
        $options = array_merge($this->options, $options);

        $index = 0;
        $stack = new EvalMathStack();
        $output = []; // postfix form of expression, to be passed to pfx()
        // $expr = trim(strtolower($expr)); MIHA NAHTIGAL
        $expr = trim((string)$expr);

        $ops = ['+', '-', '*', '/', '^', '_'];
        $ops_r = ['+' => 0, '-' => 0, '*' => 0, '/' => 0, '^' => 1]; // right-associative operator?
        $ops_p = ['+' => 0, '-' => 0, '*' => 1, '/' => 1, '_' => 1, '^' => 2]; // operator precedence

        $expecting_op = false; // we use this in syntax-checking the expression
                               // and determining when a - is a negation

        if (preg_match("/[^\w\s+*^\/()\.,;-]/", $expr, $matches)) { // make sure the characters are all good
            return $this->trigger("illegal character '{$matches[0]}'");
        }

        while (1) { // 1 Infinite Loop ;)
            $op = substr($expr, $index, 1); // get the first character at the current index
            // find out if we're currently at the beginning of a number/variable/function/parenthesis/operand
            if (!empty($options['thousandsSeparator'])) {
                $ex = preg_match(
                    '/^([a-zA-Z]\w*\(?|[\d\\' . $options['thousandsSeparator'] . ']+' .
                        '(?:\\' . $options['decimalSeparator'] . '\d*)?|\\' . $options['decimalSeparator'] . '\d+|\()/',
                    substr($expr, $index),
                    $match
                );
            } else {
                $ex = preg_match(
                    '/^([a-zA-Z]\w*\(?|\d+(?:\\' . $options['decimalSeparator'] . '\d*)?|' .
                    '\\' . $options['decimalSeparator'] . '\d+|\()/',
                    substr($expr, $index),
                    $match
                );
            }

            //===============
            if ($op == '-' && !$expecting_op) { // is it a negation instead of a minus?
                $stack->push('_'); // put a negation on the stack
                $index++;
            } elseif ($op == '_') { // we have to explicitly deny this, because it's legal on the stack
                return $this->trigger("illegal character '_'"); // but not in the input expression
            //===============
            } elseif ((in_array($op, $ops) || $ex) && $expecting_op) { // are we putting an operator on the stack?
                if ($ex) { // are we expecting an operator but have a number/variable/function/opening parethesis?
                    $op = '*';
                    $index--; // it's an implicit multiplication
                }
                // heart of the algorithm:
                while (
                    $stack->count > 0 &&
                    ($o2 = $stack->last()) &&
                    in_array($o2, $ops) &&
                    ($ops_r[$op] ? $ops_p[$op] < $ops_p[$o2] : $ops_p[$op] <= $ops_p[$o2])
                ) {
                    $output[] = $stack->pop(); // pop stuff off the stack into the output
                }
                // many thanks: http://en.wikipedia.org/wiki/Reverse_Polish_notation#The_algorithm_in_detail
                $stack->push($op); // finally put OUR operator onto the stack
                $index++;
                $expecting_op = false;
            //===============
            } elseif ($op == ')' && $expecting_op) { // ready to close a parenthesis?
                while (($o2 = $stack->pop()) != '(') { // pop off the stack back to the last (
                    if (is_null($o2)) {
                        return $this->trigger("unexpected ')'");
                    } else {
                        $output[] = $o2;
                    }
                }
                if ($stack->last(2) && preg_match("/^([a-zA-Z]\w*)\($/", (string)$stack->last(2), $matches)) { // did we just close a function?
                    $fnn = $matches[1]; // get the function name
                    $argCount = $stack->pop(); // see how many arguments there were (cleverly stored on the stack, thank you)
                    $output[] = $stack->pop(); // pop the function and push onto the output
                    if (in_array($fnn, $this->fb)) { // check the argument count
                        if ($argCount > 1) {
                            return $this->trigger(sprintf('too many arguments (%s given, 1 expected)', $argCount));
                        }
                    } elseif (array_key_exists($fnn, $this->f)) {
                        if ($argCount != count($this->f[$fnn]['args'])) {
                            return $this->trigger(sprintf(
                                'wrong number of arguments (%1$d given, %2$d) expected)',
                                $argCount,
                                count($this->f[$fnn]['args'])
                            ));
                        }
                    } else { // did we somehow push a non-function on the stack? this should never happen
                        return $this->trigger('internal error');
                    }
                }
                $index++;
            //===============
            } elseif ($op == ';' && $expecting_op) { // did we just finish a function argument?
                while (($o2 = $stack->pop()) != '(') {
                    if (is_null($o2)) {
                        return $this->trigger("unexpected ','"); // oops, never had a (
                    } else {
                        $output[] = $o2; // pop the argument expression stuff and push onto the output
                    }
                }
                // make sure there was a function
                if (!preg_match("/^([a-zA-Z]\w*)\($/", (string)$stack->last(2), $matches)) {
                    return $this->trigger("unexpected ','");
                }
                $stack->push($stack->pop() + 1); // increment the argument count
                $stack->push('('); // put the ( back on, we'll need to pop back to it again
                $index++;
                $expecting_op = false;
            //===============
            } elseif ($op == '(' && !$expecting_op) {
                $stack->push('('); // that was easy
                $index++;
                $allow_neg = true;
            //===============
            } elseif ($ex && !$expecting_op) { // do we now have a function/variable/number?
                $expecting_op = true;
                $val = $match[1];
                if (preg_match("/^([a-zA-Z]\w*)\($/", $val, $matches)) { // may be func, or variable w/ implicit multiplication against parentheses...
                    if (in_array($matches[1], $this->fb) || array_key_exists($matches[1], $this->f)) { // it's a func
                        $stack->push($val);
                        $stack->push(1);
                        $stack->push('(');
                        $expecting_op = false;
                    } else { // it's a var w/ implicit multiplication
                        $val = $matches[1];
                        $output[] = $val;
                    }
                } else { // it's a plain old var or num
                    $noThousandsSeparator = $val;
                    if (!empty($options['thousandsSeparator'])) {
                        $noThousandsSeparator = strtr($val, [$options['thousandsSeparator'] => '']);
                    }
                    if ($this->isValidFloat($noThousandsSeparator, $options)) {
                        $output[] = $this->delocalize($noThousandsSeparator, $options);
                    } else {
                        $output[] = $val;
                    }
                }
                $index += strlen($val);
            //===============
            } elseif ($op == ')') { // miscellaneous error checking
                return $this->trigger("unexpected ')'");
            } elseif (in_array($op, $ops) && !$expecting_op) {
                return $this->trigger(sprintf('unexpected operator \'%s\'', $op));
            } else { // I don't even want to know what you did to get here
                return $this->trigger(sprintf('an unexpected error occured in expression \'%s\'', $expr));
            }
            if ($index == strlen($expr)) {
                if (in_array($op, $ops)) { // did we end with an operator? bad.
                    return $this->trigger(sprintf('operator \'%s\' lacks operand', $op));
                } else {
                    break;
                }
            }

            // step the index past whitespace (pretty much turns whitespace
            // into implicit multiplication if no operator is there)
            while (substr($expr, $index, 1) == ' ') {
                $index++;
            }
        }
        while ($op = $stack->pop()) { // pop everything off the stack and push onto output
            if ($op == '(') {
                return $this->trigger('expecting \')\''); // if there are (s on the stack, ()s were unbalanced
            }
            $output[] = $op;
        }

        return $output;
    }

    /**
     * Evaluate postfix notation
     *
     * @param array $tokens Tokens
     * @param array $vars Variables
     * @param array $options Options
     * @return string|bool
     */
    public function pfx($tokens, $vars = [], $options = [])
    {
        if ($tokens == false) {
            return false;
        }
        $options = array_merge($this->options, $options);

        $stack = new EvalMathStack();

        foreach ($tokens as $token) { // nice and easy
            // if the token is a binary operator, pop two values off the stack, do the operation, and push the result back on
            // Miha Nahtigal :: put third parameter for strict checking
            // Miha Nahtigal :: fixed bug when Varname=0 variable assigment was not working because in_array(0, array('+'..')) returned true
            if (in_array($token, ['+', '-', '*', '/', '^'], true)) {
                $op2 = $stack->pop();
                if (is_null($op2)) {
                    return $this->trigger('internal error');
                }
                $op1 = $stack->pop();
                if (is_null($op1)) {
                    return $this->trigger('internal error');
                }
                switch ($token) {
                    case '+':
                        $stack->push($op1 + $op2);
                        break;
                    case '-':
                        $stack->push($op1 - $op2);
                        break;
                    case '*':
                        $stack->push($op1 * $op2);
                        break;
                    case '/':
                        if ($op2 == 0) {
                            return $this->trigger('division by zero');
                        }
                        $stack->push($op1 / $op2);
                        break;
                    case '^':
                        $stack->push(pow($op1, $op2));
                        break;
                }
            // if the token is a unary operator, pop one value off the stack, do the operation, and push it back on
            } elseif ($token == '_') {
                $stack->push(-1 * $stack->pop());
            // if the token is a function, pop arguments off the stack, hand them to the function, and push the result back on
            } elseif (preg_match("/^([a-zA-Z]\w*)\($/", (string)$token, $matches)) { // it's a function!
                $fnn = $matches[1];
                if (in_array($fnn, $this->fb)) { // built-in function:
                    $op1 = $stack->pop();
                    if (is_null($op1)) {
                        return $this->trigger('internal error');
                    }

                    $fnn = preg_replace('/^arc/', 'a', $fnn); // for the 'arc' trig synonyms
                    if ($fnn == 'ln') {
                        $fnn = 'log';
                    } elseif ($fnn == 'degtorad') {
                        $fnn = 'deg2rad';
                    } elseif ($fnn == 'radtodeg') {
                        $fnn = 'rad3deg';
                    }

                    if (!is_null($fnn) && is_callable($fnn)) {
                        $stack->push($fnn($op1));
                    }
                    //eval('$stack->push(' . $fnn . '($op1));'); // perfectly safe eval()
                } elseif (array_key_exists($fnn, $this->f)) { // user function
                    // get args
                    $args = [];
                    for ($i = count($this->f[$fnn]['args']) - 1; $i >= 0; $i--) {
                        $args[$this->f[$fnn]['args'][$i]] = $stack->pop();
                        if (is_null($args[$this->f[$fnn]['args'][$i]])) {
                            return $this->trigger('internal error');
                        }
                    }
                    $stack->push($this->pfx($this->f[$fnn]['func'], $args)); // yay... recursion!!!!
                }
            // if the token is a number or variable, push it on the stack
            } else {
                // 20120429 :: miha nahtigal :: update for international numbers
                //if (is_numeric($token)) {
                //  $stack->push($token);
                if ($this->isValidFloat($token, $options)) {
                    $stack->push($this->delocalize($token, $options));
                } elseif (array_key_exists($token, $this->v)) {
                    $stack->push($this->v[$token]);
                } elseif (array_key_exists($token, $vars)) {
                    $stack->push($vars[$token]);
                } else {
                    return $this->trigger(sprintf('undefined variable \'%s\'', $token));
                }
            }
        }
        // when we're out of tokens, the stack should have a single element, the final result
        if ($stack->count != 1) {
            return $this->trigger('internal error');
        }

        return $stack->pop();
    }

    /**
     * convert postfix to generalized infix
     * 20120429 :: miha nahtigal
     *
     * @param array $tokens Tokens
     * @param array $vars Variables
     * @param array $options Options
     * @return string|bool
     */
    public function gfx($tokens, $vars = [], $options = [])
    {
        if ($tokens == false) {
            return false;
        }
        $options = array_merge($this->options, $options);
        $ret = '';

        $stack = new EvalMathStack();
        $ops_p = ['+' => 0, '-' => 0, '*' => 1, '/' => 1, '_' => 1, '^' => 2, '#' => 3]; // operator precedence

        foreach ($tokens as $token) { // nice and easy
            // if the token is a binary operator, pop two values off the stack, do the operation, and push the result back on
            if (in_array($token, ['+', '-', '*', '/', '^'])) {
                $op2 = $stack->pop();
                if (is_null($op2)) {
                    return $this->trigger('internal error');
                }
                if (isset($stack->tokens[$stack->count]) && ($ops_p[$stack->tokens[$stack->count]] < $ops_p[$token])) {
                    $op2 = '(' . $op2 . ')';
                }

                $op1 = $stack->pop();
                if (is_null($op1)) {
                    return $this->trigger('internal error');
                }
                if (isset($stack->tokens[$stack->count]) && ($ops_p[$stack->tokens[$stack->count]] < $ops_p[$token])) {
                    $op1 = '(' . $op1 . ')';
                }

                $expr = '';
                switch ($token) {
                    case '+':
                        $expr = $op1 . '+' . $op2;
                        break;
                    case '-':
                        $expr = $op1 . '-' . $op2;
                        break;
                    case '*':
                        $expr = $op1 . '*' . $op2;
                        break;
                    case '/':
                        $expr = $op1 . '/' . $op2;
                        break;
                    case '^':
                        $expr = $op1 . '^' . $op2;
                        break;
                }
                $stack->push($expr, $token);

            // if the token is a unary operator, pop one value off the stack, do the operation, and push it back on
            } elseif ($token == '_') {
                $expr = $stack->pop();
                if (isset($stack->tokens[$stack->count]) && ($ops_p[$stack->tokens[$stack->count]] < $ops_p[$token])) {
                    $expr = '(' . $expr . ')';
                }

                $stack->push('-' . $expr, $token);
            // if the token is a function, pop arguments off the stack, hand them to the function, and push the result back on
            } elseif (preg_match("/^([a-zA-Z]\w*)\($/", (string)$token, $matches)) { // it's a function!
                $fnn = $matches[1];
                if (in_array($fnn, $this->fb)) { // built-in function:
                    $op1 = $stack->pop();
                    if (is_null($op1)) {
                        return $this->trigger('internal error');
                    }
                    $fnn = preg_replace('/^arc/', 'a', $fnn); // for the 'arc' trig synonyms
                    if ($fnn == 'ln') {
                        $fnn = 'log';
                    }
                    $stack->push($fnn . '(' . $op1 . ')', '#');
                } elseif (array_key_exists($fnn, $this->f)) { // user function
                    // get args
                    $args = [];
                    for ($i = count($this->f[$fnn]['args']) - 1; $i >= 0; $i--) {
                        $args[$this->f[$fnn]['args'][$i]] = $stack->pop();
                        if (is_null($args[$this->f[$fnn]['args'][$i]])) {
                            return $this->trigger('internal error');
                        }
                    }
                    $stack->push($this->gfx($this->f[$fnn]['func'], $args)); // yay... recursion!!!!
                }
            // if the token is a number or variable, push it on the stack
            } else {
                if (is_numeric($token)) {
                    $l = localeconv();
                    $token = strtr((string)$token, [$l['decimal_point'] => $options['decimalSeparator']]);
                    $stack->push($token);
                } elseif ($this->isValidFloat($token, $options)) {
                    $stack->push($this->delocalize($token, $options));
                } elseif (array_key_exists($token, $this->v)) {
                    $stack->push($token);
                } elseif (array_key_exists($token, $vars)) {
                    $stack->push($token);
                } else {
                    return $this->trigger(sprintf('undefined variable \'%s\'', $token));
                }
            }
        }
        // when we're out of tokens, the stack should have a single element, the final result
        if ($stack->count != 1) {
            return $this->trigger('internal error');
        }

        return (string)$stack->pop();
    }

    /**
     * Trigger an error, but nicely, if need be
     *
     * @param string $msg Message
     * @return false
     */
    private function trigger($msg)
    {
        $this->last_error = $msg;
        if (!$this->suppress_errors) {
            trigger_error($msg, E_USER_WARNING);
        }

        return false;
    }
}

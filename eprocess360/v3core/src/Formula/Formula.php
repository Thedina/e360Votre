<?php
namespace eprocess360\v3core\Formula;
use eprocess360\v3core\Keydict;
use eprocess360\v3core\Keydict\Entry;
use jlawrence\eos\Parser;

/**
 * Created by PhpStorm.
 * User: kiranebilak
 * Date: 2/25/16
 * Time: 9:44 AM
 */

class Formula
{
    private $keydicts;
    private $environmentVariables;
    private static $string_open = ['"',"'"];
    private static $function_open = ['('];
    private static $function_close = [')'];
    // private static $math_open = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, '+', '-', '*', '/'];
    // private static $math_middle = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, '+', '-', '*', '/', '.'];
    private static $string_token_open = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    private static $string_token_middle = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ.1234567890_';
    private static $parameter_delimiter = [','];
    private $debug = false;
    private $functions;
    /** @var Entry[] Keeps track of the variables that function use so they can be reported */
    private $lastEvalVariables;

    public function __construct()
    {
        $this->functions = new Functions($this); // instantiate the function handler
    }

    /**
     * @param $namespace
     * @return Keydict
     */
    public function getKeydict($namespace)
    {
        return $this->keydicts[$namespace];
    }

    /**
     * @return Keydict\Entry[]
     */
    public function getLastEvalVariables()
    {
        return $this->lastEvalVariables;
    }

    /**
     * @return mixed
     */
    public function getEnvironmentVariables($variable)
    {
        return $this->environmentVariables[$variable];
    }

    // call a function
    private function _call($function, $function_parameters)
    {
        $out = [];
        foreach ($function_parameters as $parameter) {
            $out[] = self::evaluate($parameter, true);
        }
        if ($this->functions->_exists($function)) {
            if (sizeof($function_parameters)) {
                return $this->functions->$function(...$out);
            }
            return $this->functions->$function();
        }
        throw new \Exception("Formula function {$function}() not available.");
    }

    // temp function for variable lookup
    private function _variable($variable)
    {
        // todo connect keydict values - do it in the Functions class
        return $this->functions->_variable($variable);
    }

    public function registerLastEvalVariable(Entry $entry)
    {
        $this->lastEvalVariables[$entry->getName()] = $entry;
        return $this;
    }

    public static function last($array)
    {
        end($array);
        return current($array);
    }

    public static function clean($formula)
    {
        $out = '';
        $open = [];
        $last = null;
        while ($formula) {
            $skip = false;
            $nextChar = substr($formula, 0, 1);
            $formula = substr($formula, 1);
            if ($nextChar == ' ' && sizeof($open) == 0) {
                $skip = true; // strip character
            } elseif ($nextChar == '\\' && substr($formula, 0, 1) == self::last($open)) {
                $out .= $nextChar;
                $nextChar = substr($formula, 0, 1);
                $formula = substr($formula, 1);
            } elseif (in_array($nextChar, self::$string_open) && self::last($open) != $nextChar) {
                $open[] = $nextChar;
            } elseif (in_array($nextChar, self::$string_open) && self::last($open)  == $nextChar) {
                array_pop($open);
            } elseif (($pos = array_search($nextChar, self::$function_open))!==false) {
                $open[] = self::$function_close[$pos];
            } elseif (in_array($nextChar, self::$function_close) && self::last($open)  == $nextChar) {
                array_pop($open);
            }
            if (!$skip) $out .= $nextChar;
        }
        return $out;
    }

    public function addKeydict($name, Keydict $keydict)
    {
        $this->keydicts[$name] = $keydict;
    }

    public function setEnvironmentVariable($variable, $value)
    {
        $this->environmentVariables[$variable] = $value;
    }

    public function parse($formula, $reset = false)
    {
        if ($reset) $this->lastEvalVariables = [];
        $output = $this->evaluate($formula);
        return Parser::solveIF($output);
    }

    public function copy()
    {
        $formula = new Formula();
        $formula->import('environmentVariables', $this->environmentVariables);
        $formula->import('keydicts', $this->keydicts);
        return $formula;
    }

    protected function import($name, $value)
    {
        $this->$name = $value;
    }

    private function evaluate($formula, $useObj = false)
    {
        if ($this->debug) echo 'evaluate(): '.$formula.PHP_EOL;
        // strip whitespace from the current scope
        $formula = self::clean($formula);
        // parse tokens
        $token = '';
        $out = '';
        $structure = null;
        $structure_end = null;
        $function_parameter = '';
        $function_parameters = [];
        if ($this->debug) echo 'evaluate(): '.$formula.PHP_EOL;
        while (strlen($formula)) {
            $nextChar = substr($formula, 0, 1);
            $formula = substr($formula, 1);
            if (!$structure) {
                // no open structures, detect mode
                if (strpos(self::$string_token_open, $nextChar)!==false) {
                    // string token open mode, identify the type of string by reading
                    $token = $nextChar;
                    $structure = 'string_token';
                } elseif(($pos = array_search($nextChar, self::$string_open))!==false) {
                    if ($this->debug) echo 'opening plain string'.PHP_EOL;
                    // string open mode
                    $token  = $nextChar;
                    $structure = 'string';
                    $structure_end = $nextChar;
                } else {
                    // math, leave it alone
                    $out .= $nextChar;
                }
            } elseif ($structure == 'string_token') {
                if (strpos(self::$string_token_middle, $nextChar)!==false) {
                    // continue reading
                    $token .= $nextChar;
                } elseif (($pos = array_search($nextChar, self::$function_open))!==false) {
                    // function opened, we'll switch to parameter processing
                    // the $token becomes the name of the function
                    $structure = 'function';
                    $structure_end = self::$function_close[$pos]; // there can be multiple delimiters for functions
                    if ($this->debug) echo 'setting end to '.$pos.' = '.self::$function_close[$pos].PHP_EOL;
                } else {
                    // dead end, we now have a string-based token that should be equated to a variable
                    // kill the structure
                    $out .= self::_variable($token);
                    $structure = null;
                    $structure_end = null;
                    $formula = $nextChar . $formula;
                }
            } elseif ($structure == 'function') {
                // specifically for reading in function parameters
                // we need to parse the $formula until we hit the function end delimiter
                // parameters need to be separated out
                $function_open = [];
                $function_parameters = [];
                $function_parameter = '';
                $inString = function () use (&$function_open) {
                    if ($this->debug) echo 'inString '.implode (', ',$function_open);
                    foreach (self::$string_open as $stringDelimiter) {
                        if (in_array($stringDelimiter, $function_open)) {
                            if ($this->debug) echo ' == true'.PHP_EOL;
                            return true;
                        }
                    }
                    if ($this->debug) echo ' == false'.PHP_EOL;
                    return false;
                };

                while (strlen($formula) || in_array($nextChar, self::$function_close)) {
                    if ($this->debug) echo 'step in function parse: '.$nextChar.PHP_EOL;
                    $break = 0;
                    if ($nextChar == $structure_end && sizeof($function_open) == 0) {
                        // end the parameter search and call the function
                        if ($this->debug) echo 'structure_end reached'.PHP_EOL;
                        $break = 2;
                    } elseif (in_array($nextChar, self::$parameter_delimiter)) {
                        $break = 1;
                        if ($this->debug) echo 'function parameter close '.PHP_EOL;
                    } elseif ($nextChar == '\\' && substr($formula, 0, 1) == self::last($function_open)) {
                        $function_parameter .= $nextChar;
                        $nextChar = substr($formula, 0, 1);
                        $formula = substr($formula, 1);
                        if ($this->debug) echo 'escaped'.PHP_EOL;
                    } elseif (in_array($nextChar, self::$string_open) && !$inString()) {
                        $function_open[] = $nextChar;
                        if ($this->debug) echo 'function parameter open '.$nextChar.PHP_EOL;
                        if ($this->debug) echo 'function open stack is ('.implode('.',$function_open).')'.PHP_EOL;
                    } elseif (in_array($nextChar, self::$string_open) && self::last($function_open) == $nextChar) {
                        array_pop($function_open);
                        if ($this->debug) echo 'intent pop string close'.PHP_EOL;
                    } elseif (($pos = array_search($nextChar, self::$function_open))!==false && !$inString()) {
                        if ($this->debug) echo 'function parameter open F '.$nextChar.PHP_EOL;
                        $function_open[] = self::$function_close[$pos];
                    } elseif (in_array($nextChar, self::$function_close) && self::last($function_open)  == $nextChar
                        && !$inString()) {
                        array_pop($function_open);
                        if ($this->debug) echo 'intent pop function close'.PHP_EOL;
                    }
                    if ($break == 0) {
                        $function_parameter .= $nextChar;
                    } else {
                        if ($this->debug) echo 'break'.$break.$function_parameter.PHP_EOL;
                        // we have a parameter to add if strlen()
                        if (strlen($function_parameter)) {
                            $function_parameters[] = $function_parameter;
                        }
                        $function_parameter = '';
                        if ($break===2) {
                            $structure = null;
                            $structure_end = null;
                            if ($this->debug) echo 'call '.$token .' using ('.implode(',', $function_parameters).')'.PHP_EOL;
                            $out .= $this->_call($token, $function_parameters);
                            break;
                        }
                    }
                    $nextChar = substr($formula, 0, 1);
                    if ($nextChar == '(') {
                        if ($this->debug) echo 'detect open func', ($pos = array_search($nextChar, self::$function_open))!==false , 'instring', (int)!$inString(), PHP_EOL;
                    }
                    if ($this->debug) echo 'function parameter next '.$nextChar.' open('.implode(',',$function_open).') inString'.(int)$inString().PHP_EOL;
                    $formula = substr($formula, 1);
                }
                if ($structure_end != null) {
                    throw new \Exception("Structure End Token {$structure_end} Expected.");
                }
                // $function_open = [];
                $function_parameters = [];
                $function_parameter = '';
            } elseif ($structure == 'string') {
                // continue reading until end of string
                if (!$structure_end) throw new \Exception("Structure End Token {$structure_end} Expected.");
                // check for escaped string
                if ($nextChar == '\\' && substr($formula, 0, 1) == $structure_end) {
                    $nextChar = substr($formula, 0, 1);
                    $formula = substr($formula, 1);
                }
                $token .= $nextChar;
            }
            if ($this->debug) echo 'structure: '.($structure?:'null').' end '.($structure_end?:'null').' token '.$token.' parameters ('.implode(', ',$function_parameters).') remainder '.$function_parameter.' ::::' .$formula.PHP_EOL;
        }
        if ($structure == 'string_token') {
            $out .= $this->_variable($token);
            $structure = null;
            $structure_end = null;
        } elseif ($structure == 'string' && $out == '') {
            // the entire result is a string
            if ($this->debug) echo 'STRING::'.(int)$useObj.': '.$token.PHP_EOL;
            $item = new FormulaString();
            $item->setRawValue($token);
            if ($useObj) {
                return $item;
            }
            $out = $item->getValue();
        }
        return $out;
    }
}
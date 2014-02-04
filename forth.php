<?php

/**
 * A set of classes and functions to implement a subset of the FORTH
 * programming laguage.
 *
 * @author Greg Marquez <greg.marquez@gmail.com>
 * @copyright 2013, 2014 Richard Greg Marquez
 * @package gregforth
 * @version 0.0.1
 */

// TODO : make everything on the stack ints
// TODO : Stack empty message
// TODO : encapsulate forth machine state (stack, etc.)
// TODO : Add ."
// TODO : Make it so that multiple definitions can exist, with the MRD being used
// TODO : add 'forget'
// TODO : Add 'marker'
// TODO : Add 'include'
// TODO : Add 'use'
// TODO : Add 'list'
// TODO : Add '('
// TODO : optimize with references

// NOTE : After doing 5 bazillion ops that take only the stack reference,
// now need to pass in a 'state'...so do I special case it for the new op,
// or retrofit all of the other ones?  Planning might have caught this.

// NOTE : Decided to use accessor functions instead of obeying YAGNI

/**
 * Correctly implement a multi-byte aware case insensitive string compare
 *
 * @param string $str1 one of the strings to compare
 * @param string $str2 the other string to compare
 * @param mixed $encoding OPTIONAL encoding to use (defaults to mb_internal_encoding())
 * @return < 0 if str1 is less than str2; > 0 if str1 is greater than str2, and 0 if they are equal.
 */
function mb_strcasecmp($str1, $str2, $encoding = null) {
    if (null === $encoding) { $encoding = mb_internal_encoding(); }
    return strcmp(mb_strtoupper($str1, $encoding), mb_strtoupper($str2, $encoding));
}

/**
 * Find out if a variable is an executable function
 *
 * @param mixed $f the variable to check
 * @return TRUE if the variable references an executable function, FALSE otherwise
 */
function is_function($f) {
    return (is_string($f) && function_exists($f)) || (is_object($f) && ($f instanceof Closure));
}

/**
 * Find out if a variable is a 'Closure' (aka 'function object')
 *
 * @param mixed $f the variable to check
 * @return TRUE if the variable references a Closure object, FALSE otherwise
 */
function is_closure($f) {
    return (is_object($f) && ($f instanceof Closure));
}

// TODO : add these to the forth object
define("COMPILE_TOKEN", ':');
define("ENDCOMPILE_TOKEN", ';');
define("FORTH_TRUE", -1);
define("FORTH_FALSE", 0);


// : star 42 emit ;
// addToDictionary($dictionary, 'star', '42 emit');

/** */
$nativeQuit = function (&$forthMachineState) {
    echo "goodbye!\n";
    return (false);
};

/** */
$nativeEmit = function (&$forthMachineState) {
    $val = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    printf('%c', $val);
    return (true);
};

/** */
$nativeCR = function (&$forthMachineState) {
    echo "\n";
    return (true);
};

/** */
$nativeSpaces = function (&$forthMachineState) {
    $val = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    echo str_repeat (' ', $val);
    return (true);
};

/** */
$nativeDot = function (&$forthMachineState) {
    $val = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    echo $val . ' ';
    return (true);
};

/** */
$nativeDotS = function (&$forthMachineState) {
    echo $forthMachineState->stackToString(ForthMachineState::ID_PARAMETER_STACK)  . "\n";
    return (true);
};

/** */
$nativePlus = function (&$forthMachineState) {
    $val1 = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $val2 = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, $val1 + $val2);
    return (true);
};

/** */
$nativeMinus = function (&$forthMachineState) {
    $val2 = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $val1 = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, $val1 - $val2);
    return (true);
};

/** */
$nativeMult = function (&$forthMachineState) {
    $val2 = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $val1 = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, $val1 * $val2);
    return (true);
};

/** */
$nativeDiv = function (&$forthMachineState) {
    $val2 = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $val1 = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, intval($val1 / $val2));
    return (true);
};

/** */
$nativeMod = function (&$forthMachineState) {
    $val2 = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $val1 = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, $val1 % $val2);
    return (true);
};

/** */
$nativeSlashMod = function (&$forthMachineState) {
    $val2 = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $val1 = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, $val1 % $val2);
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, intval($val1 / $val2));
    return (true);
};

/** */
$nativeMin = function (&$forthMachineState) {
    $val2 = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $val1 = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, ($val1 < $val2) ? $val1 : $val2);
    return (true);
};

/** */
$nativeMax = function (&$forthMachineState) {
    $val2 = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $val1 = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, ($val1 > $val2) ? $val1 : $val2);
    return (true);
};

/** */
$nativeNegate = function (&$forthMachineState) {
    $val = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, -$val);
    return (true);
};

/** */
$nativeAbs = function (&$forthMachineState) {
    $val = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, ($val >= 0) ? $val : -$val);
    return (true);
};

/** */
$nativeGT = function (&$forthMachineState) {
    $val2 = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $val1 = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, ($val1 > $val2) ? FORTH_TRUE : FORTH_FALSE);
    return (true);
};

/** */
$nativeLT = function (&$forthMachineState) {
    $val2 = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $val1 = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, ($val1 < $val2) ? FORTH_TRUE : FORTH_FALSE);
    return (true);
};

/** */
$nativeEQ = function (&$forthMachineState) {
    $val2 = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $val1 = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, ($val1 === $val2) ? FORTH_TRUE : FORTH_FALSE);
    return (true);
};

/** */
$nativeNEQ = function (&$forthMachineState) {
    $val2 = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $val1 = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, ($val1 !== $val2) ? FORTH_TRUE : FORTH_FALSE);
    return (true);
};

/** */
$nativeEQZ = function (&$forthMachineState) {
    $val = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, ($val === 0) ? FORTH_TRUE : FORTH_FALSE);
    return (true);
};

/** */
$nativeNEQZ = function (&$forthMachineState) {
    $val = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, ($val !== 0) ? FORTH_TRUE : FORTH_FALSE);
    return (true);
};

/** */
$nativeDup = function (&$forthMachineState) {
    $val = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, $val);
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, $val);
    return (true);
};

/** */
$nativeDupNZ = function (&$forthMachineState) {
    $val = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, $val);
    if ($val !== 0) {
        $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, $val);
    }
    return (true);
};

/** */
$nativeDrop = function (&$forthMachineState) {
    $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    return (true);
};

/** */
$nativeSwap = function (&$forthMachineState) {
    $val2 = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $val1 = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, $val2);
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, $val1);
    return (true);
};

/** */
$nativeOver = function (&$forthMachineState) {
    $val2 = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $val1 = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, $val1);
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, $val2);
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, $val1);
    return (true);
};

/** */
// ROT ( n1 n2 n3 -- n2 n3 n1 )
$nativeRot = function (&$forthMachineState) {
    $n1 = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $n2 = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $n3 = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, $n2);
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, $n3);
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, $n1);
    return (true);
};

/** */
$nativeNip = function (&$forthMachineState) {
    $val2 = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $val1 = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, $val2);
    return (true);
};

/** */
// 2SWAP ( d1 d2 -- d2 d1 )
$native2Swap = function (&$forthMachineState) {
    $d2b = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $d2a = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $d1b = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $d1a = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, $d2a);
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, $d2b);
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, $d1a);
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, $d1b);
    return (true);
};

/** */
// 2DUP ( d -- d d )
$native2Dup = function (&$forthMachineState) {
    $db = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $da = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, $da);
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, $db);
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, $da);
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, $db);
    return (true);
};

/** */
// 2OVER ( d1 d2 -- d1 d2 d1 )
$native2Over = function (&$forthMachineState) {
    $d2b = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $d2a = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $d1b = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $d1a = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, $d1a);
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, $d1b);
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, $d2a);
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, $d2b);
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, $d1a);
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, $d1b);
    return (true);
};

/** */
// 2DROP ( d -- )
$native2Drop = function (&$forthMachineState) {
    $db = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $da = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    return (true);
};

/** */
$nativeInvert = function (&$forthMachineState) {
    $b = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, ($b === FORTH_FALSE) ? FORTH_TRUE : FORTH_FALSE);
    return (true);
};

// ----------

/** */
$nativeIf = function (&$forthMachineState) {
    $condition = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    return (true);
};

/** */
$nativeElse = function (&$forthMachineState) {
    return (true);
};

/** */
$nativeThen = function (&$forthMachineState) {
    return (true);
};

/**
 * DO -
 *  1) remove limit and index from the stack.
 *  2) place IP, limit and index on the return stack
 *  (limit index -- ) [ -- ip limit index]
 */
$nativeDo = function (&$forthMachineState) {
    $index = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);
    $limit = $forthMachineState->pop(ForthMachineState::ID_PARAMETER_STACK);

    $forthMachineState->push(ForthMachineState::ID_RETURN_STACK, $forthMachineState->getProgramCounter());
    $forthMachineState->push(ForthMachineState::ID_RETURN_STACK, $limit);
    $forthMachineState->push(ForthMachineState::ID_RETURN_STACK, $index);

    return (true);
};

/**
 * LOOP -
 *  1)  add 1 to index.
 *  2)  if <> limit
 *          pc = ip
 *      else
 *          pop control stack 3 times
 *  ( -- ) [ip limit index -- ip limit index | ]
 */
$nativeLoop = function (&$forthMachineState) {
    $index = &$forthMachineState->getStackItemReference(ForthMachineState::ID_RETURN_STACK, 0);
    $index += 1;

    $limit = $forthMachineState->stackPeek(ForthMachineState::ID_RETURN_STACK, 1);

    //echo "index : $index, limit : $limit\n";

    if ($index !== $limit) {
        $forthMachineState->setProgramCounter($forthMachineState->stackPeek(ForthMachineState::ID_RETURN_STACK, 2));
    }
    else {
        $forthMachineState->pop(ForthMachineState::ID_RETURN_STACK);
        $forthMachineState->pop(ForthMachineState::ID_RETURN_STACK);
        $forthMachineState->pop(ForthMachineState::ID_RETURN_STACK);
    }
    return (true);
};

/** I copies the top of the loop control stack onto the parameter stack. */
$nativeI = function (&$forthMachineState) {
    $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, $forthMachineState->stackPeek(ForthMachineState::ID_RETURN_STACK, 0));

    return (true);
};

$nativeJ = function (&$forthMachineState) {
    return (true);
};


/**
 * the class that keeps the FORTH machine state (stack, dictionary, etc.)
 */
class ForthMachineState {
    protected $_isDefining = false;
    protected $_definingFnName = NULL;
    protected $_definingFnBody = '';

    /**** Program Counter ****/

    /** */
    protected $_pc = 0;

    /** */
    public function getProgramCounter() {
        return $this->_pc;
    }

    /** */
    public function setProgramCounter($val) {
        if (is_int($val)) {
            $this->_pc = $val;
        }
    }

    /** */
    public function incrementProgramCounter() {
        $this->_pc += 1;
    }

    /**** Dictionary *****/

    protected $_dictionary = array();


    /** */
    public function getDictionaryEntry($key) {
        if (isset($key) && array_key_exists($key, $this->_dictionary)) {
            return ($this->_dictionary[$key]);
        }
        else {
            return (NULL);
        }
    }
    /** */
    public function addToDictionary($key, $value) {
        $returnValue = TRUE;
        if (isset($key) && (is_string($value) || is_closure($value))) {
            if (is_string($value)) {
                $value = trim($value);
                $this->printDebugMessage(self::DICTIONARY_DEBUGGING, "Defining fn '$key' as '$value'\n");
            }
            else {
                $this->printDebugMessage(self::DICTIONARY_DEBUGGING, "Defining fn '$key' as native function\n");
            }
            $this->_dictionary[$key] = $value;
        }
        else {
            $returnValue = FALSE;
        }
        return ($returnValue);
    }

    /**** Stacks *****/

    const ID_PARAMETER_STACK = 0;
    const ID_RETURN_STACK = 1;

    protected   $_stack = array();
    protected   $_returnStack = array();
    private     $_nullReference = NULL;

    private function stackIDToString($stackID) {
        $s = '(?)';
        switch ($stackID) {
            case self::ID_PARAMETER_STACK:
                $s = '(parameter stack)';
                break;
            case self::ID_RETURN_STACK:
                $s = '(RETURN STACK)';
                break;
        }
        return $s;
    }

    protected function &getStackReference($stackID) {
        $targetStack = NULL;
        if ($stackID === self::ID_PARAMETER_STACK) {
            $targetStack    = &$this->_stack;
        }
        elseif ($stackID === self::ID_RETURN_STACK) {
            $targetStack    = &$this->_returnStack;
        }
        return $targetStack;
    }

    /** */
    public function push($stackID, $value) {
        $this->printDebugMessage(self::STACK_DEBUGGING, $this->stackIDToString($stackID) . "push()\n");

        $stack = &$this->getStackReference($stackID);
        if ($stack !== NULL) {
            array_push($stack, $value);
            $this->printDebugMessage(self::STACK_DEBUGGING, 'after push : ' . $this->stackToString($stackID) . "\n");
        }
    }

    /** */
    public function pop($stackID) {
        $this->printDebugMessage(self::STACK_DEBUGGING, $this->stackIDToString($stackID) . "pop()\n");

        $returnValue = NULL;
        $stack = &$this->getStackReference($stackID);
        if (($stack !== NULL) && (! empty($stack))) {
            $returnValue = array_pop($stack);
            $this->printDebugMessage(self::STACK_DEBUGGING, 'after pop : ' . $this->stackToString($stackID) . "\n");
        }
        return $returnValue;
    }

    /** */
    public function stackToString($stackID) {
        $s = '';
        $indicator = '?';
        if ($stackID === self::ID_PARAMETER_STACK) {
            $indicator = '';
        }
        elseif ($stackID === self::ID_RETURN_STACK) {
            $indicator = 'r';
        }
        $stack = &$this->getStackReference($stackID);
        if ($stack !== NULL) {
            $count = count($stack);
            $s = "$indicator<$count> ";
            foreach ($stack as $element) {
                $s .= $element . ' ';
            }
        }

        return (trim($s));
    }

    /** */
    public function stackPeek($stackID, $offset) {
        unset($returnValue);
        $stack = &$this->getStackReference($stackID);
        if ($stack !== NULL) {
            $numberOfStackItems = count($stack);
            if ($numberOfStackItems > $offset) {
                $returnValue = $stack[$numberOfStackItems - $offset - 1];
            }
        }
        return $returnValue;
    }

    /** */
    public function &getStackItemReference($stackID, $offset) {
        $stack = &$this->getStackReference($stackID);
        if ($stack !== NULL) {
            $numberOfStackItems = count($stack);
            if ($numberOfStackItems > $offset) {
                return $stack[$numberOfStackItems - $offset - 1];
            }
        }
        return $this->_nullReference;
    }

    /**** ****/

    /** */
    public function isDefining() {
        return ($this->_isDefining);
    }
    /** */
    public function setIsDefining($t_f) {
        $this->_isDefining = $t_f;
    }

    /** */
    public function definingFnName() {
        return ($this->_definingFnName);
    }
    /** */
    public function setDefiningFnName($name) {
        $this->_definingFnName = $name;
    }

    /** */
    public function definingFnBody() {
        return ($this->_definingFnBody);
    }
    /** */
    public function setDefiningFnBody($text) {
        $this->_definingFnBody = $text;
    }
    /** */
    public function appendToDefiningFnBody($text) {
        $this->_definingFnBody .= $text;
    }

    // *************
    // * Debugging *
    // *************

    const STACK_DEBUGGING = 1;
    const DICTIONARY_DEBUGGING = 2;

    protected $_debugStackFlag = FALSE;
    protected $_debugDictionaryFlag = FALSE;

    /** */
    public function isDebuggingOn($type) {
        if ($type === self::STACK_DEBUGGING) {
            return ($this->_debugStackFlag);
        }
        elseif ($type === self::DICTIONARY_DEBUGGING) {
            return ($this->_debugDictionaryFlag);
        }
    }

    /** */
    public function setDebugging($type, $val) {
        if ($type === self::STACK_DEBUGGING) {
            $this->_debugStackFlag = $val;
        }
        elseif ($type === self::DICTIONARY_DEBUGGING) {
            $this->_debugDictionaryFlag = $val;
        }
    }

    /** */
    public function printDebugMessage($type, $msg) {
        if ($this->isDebuggingOn($type)) {
            if ($type === self::STACK_DEBUGGING) {
                echo ' s->' . $msg . "\n";
            }
            elseif ($type === self::DICTIONARY_DEBUGGING) {
                echo ' d->' . $msg . "\n";
            }
        }
    }
}



/**
 * The interface to the FORTH machine...you feed it lines of FORTH
 * code, and it updates the internal FORTH machine state and executes any FORTH
 * code.
 */
class ForthMachine {
    /** */
    public function addToDictionary($fnName, $tokens) {
        $this->_forthMachineState->addToDictionary($fnName, $tokens);
    }

    /** */
    public function executeString($line) {
        $forthMachineState =& $this->_forthMachineState;

        $line = trim($line);
        $this->printDebugMessage(self::PARSER_DEBUGGING, "executing : '$line'");

        $shouldContinue = true;
        $isError = false;

        $tokens = explode(' ', $line);
        //foreach ($tokens as $token) {
        $instructionCount = count($tokens);
        for ($forthMachineState->setProgramCounter(0); $forthMachineState->getProgramCounter() < $instructionCount; ) {

            $token = $tokens[$forthMachineState->getProgramCounter()];
            $forthMachineState->incrementProgramCounter();
            $this->printDebugMessage(self::PARSER_DEBUGGING, "token : '$token'");

            if (strcmp(COMPILE_TOKEN, $token) === 0) {
                if ($forthMachineState->isDefining() === false) {
                    $forthMachineState->setIsDefining(true);
                }
                else {
                    $isError = true;
                    break;
                }
            }
            elseif (strcmp(ENDCOMPILE_TOKEN, $token) === 0) {
                if ($forthMachineState->isDefining() === true) {
                    $forthMachineState->setIsDefining(false);
                    $fnName = $forthMachineState->definingFnName();
                    $fnBody = $forthMachineState->definingFnBody();
                    $this->printDebugMessage(self::PARSER_DEBUGGING, "defining $fnName as '$fnBody'");
                    $this->addToDictionary($forthMachineState->definingFnName(), $forthMachineState->definingFnBody());
                    $forthMachineState->setDefiningFnName(NULL);
                    $forthMachineState->setDefiningFnBody('');
                }
                else {
                    $isError = true;
                    break;
                }
            }
            else {
                if ($forthMachineState->isDefining()) {
                    if (is_null($forthMachineState->definingFnName())) {
                        $forthMachineState->setDefiningFnName($token);
                    }
                    else
                    {
                        $forthMachineState->appendToDefiningFnBody($token . ' ');
                    }
                }
                else {
                    $this->printDebugMessage(self::PARSER_DEBUGGING, "looking up : '$token'");

                    $programCode = $this->getDictionaryEntry($token);
                    if (! is_null($programCode)) {
                        $this->printDebugMessage(self::PARSER_DEBUGGING, "calling : '$token'");
                        $shouldContinue = $this->callForthCode($programCode);
                        $this->printDebugMessage(self::PARSER_DEBUGGING, "returned from call : '$token'");
                    }
                    else if (is_numeric($token)) {
                        $this->printDebugMessage(self::PARSER_DEBUGGING, "pushing onto stack : '$token'");
                        $forthMachineState->push(ForthMachineState::ID_PARAMETER_STACK, intval($token));
                    }
                    else {
                        $isError = true;
                        break;
                    }
                }
            }
        }
        if ($isError === true) {
            echo "?error - ($line)\n";
            $isError = false;
            //$shouldContinue = false;
        }
        return ($shouldContinue);
    }


    /** */
    protected $_forthMachineState;

    /** */
    protected function getDictionaryEntry($fnName) {
        return ($this->_forthMachineState->getDictionaryEntry($fnName));
    }

    /** */
    protected function callForthCode(& $forthCode) {
        $shouldContinue = TRUE;
        if (! is_null($forthCode)) {
            if (is_closure($forthCode)) {
                $shouldContinue = $forthCode($this->_forthMachineState);
            }
            elseif (is_string($forthCode)) {
                // first, push the PC counter, make the call, then pop the PC counter
                $this->_forthMachineState->push(ForthMachineState::ID_RETURN_STACK, $this->_forthMachineState->getProgramCounter());

                $shouldContinue = $this->executeString($forthCode);

                $this->_forthMachineState->setProgramCounter($this->_forthMachineState->pop(ForthMachineState::ID_RETURN_STACK));
            }
            else {
                $shouldContinue = FALSE;
            }
        }
        else {
            $shouldContinue = FALSE;
        }
        return ($shouldContinue);
    }

    // *************
    // * Debugging *
    // *************
    const STACK_DEBUGGING = 1;
    const DICTIONARY_DEBUGGING = 2;
    const PARSER_DEBUGGING = 3;

    protected $_debugParserFlag = FALSE;

    public function isDebuggingOn($type) {
        if ($type === self::PARSER_DEBUGGING) {
            return ($this->_debugParserFlag);
        }
        else {
            return ($this->_forthMachineState->isDebuggingOn($type));
        }
    }

    /** */
    public function setDebugging($type, $val) {
        if ($type === self::PARSER_DEBUGGING) {
            $this->_debugParserFlag = $val;
        }
        else {
            $this->_forthMachineState->setDebugging($type, $val);
        }
    }

    /** */
    public function printDebugMessage($type, $msg) {
        if ($this->isDebuggingOn($type)) {
            $prefix = '';
            switch($type) {
                case self::PARSER_DEBUGGING:
                    $prefix = ' [parser]:';
                    break;
                case self::DICTIONARY_DEBUGGING:
                    $prefix = ' [dictionary]:';
                    break;
                case self::STACK_DEBUGGING:
                    $prefix = ' [stack]:';
                    break;
                case defualt:
                    $prefix = ' [?]:';
                    break;
            }
            echo $prefix . $msg . "\n";
            //$this->_forthMachineState->printDebugMessage($type, $msg);
        }
    }

    /** */
    function __construct() {
        $this->_forthMachineState = new ForthMachineState();
    }
}

// ***** Start ****************************************************************

$forth = new ForthMachine();
$forth->setDebugging(ForthMachine::STACK_DEBUGGING, FALSE);
$forth->setDebugging(ForthMachine::DICTIONARY_DEBUGGING, FALSE);
$forth->setDebugging(ForthMachine::PARSER_DEBUGGING, FALSE);

$forth->addToDictionary('emit', $nativeEmit);
$forth->addToDictionary('cr', $nativeCR);
$forth->addToDictionary('spaces', $nativeSpaces);
$forth->addToDictionary('quit', $nativeQuit);
$forth->addToDictionary('.', $nativeDot);
$forth->addToDictionary('+', $nativePlus);
$forth->addToDictionary('-', $nativeMinus);
$forth->addToDictionary('*', $nativeMult);
$forth->addToDictionary('/', $nativeDiv);
$forth->addToDictionary('mod', $nativeMod);
$forth->addToDictionary('/mod', $nativeSlashMod);
$forth->addToDictionary('min', $nativeMin);
$forth->addToDictionary('max', $nativeMax);
$forth->addToDictionary('negate', $nativeNegate);
$forth->addToDictionary('abs', $nativeAbs);
$forth->addToDictionary('<', $nativeLT);
$forth->addToDictionary('>', $nativeGT);
$forth->addToDictionary('=', $nativeEQ);
$forth->addToDictionary('<>', $nativeNEQ);
$forth->addToDictionary('0=', $nativeEQZ);
$forth->addToDictionary('0<>', $nativeNEQZ);
$forth->addToDictionary('dup', $nativeDup);
$forth->addToDictionary('?dup', $nativeDupNZ);
$forth->addToDictionary('drop', $nativeDrop);
$forth->addToDictionary('swap', $nativeSwap);
$forth->addToDictionary('over', $nativeOver);
$forth->addToDictionary('rot', $nativeRot);
$forth->addToDictionary('nip', $nativeNip);
$forth->addToDictionary('tuck', 'swap over');
$forth->addToDictionary('.s', $nativeDotS);
$forth->addToDictionary('2swap', $native2Swap);
$forth->addToDictionary('2dup', $native2Dup);
$forth->addToDictionary('2over', $native2Over);
$forth->addToDictionary('2drop', $native2Drop);
$forth->addToDictionary('invert', $nativeInvert);

$forth->addToDictionary('if', $nativeIf);
$forth->addToDictionary('else', $nativeElse);
$forth->addToDictionary('then', $nativeThen);
$forth->addToDictionary('do', $nativeDo);
$forth->addToDictionary('loop', $nativeLoop);
$forth->addToDictionary('i', $nativeI);
$forth->addToDictionary('j', $nativeJ);

do {
    $isError = false;
    echo "\nok ";
    $line = fgets(STDIN);
    $shallContinue = $forth->executeString($line);
} while ($shallContinue);
?>

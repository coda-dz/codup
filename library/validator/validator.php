<?php


namespace Phiber\Validator;

use Phiber\Event as Events;

class validator
{

    const EVENT_NOT_OBJECT = 'validator.notobject';
    const EVENT_FILE_NOTFOUND = 'validator.filenotfound';
    const EVENT_NOTVALID = 'validator.notvalid';

    public static $callbacks = array();
    public $subject = array();
    protected $candidate;
    protected $key;
    protected $errors;
    protected $error_msg;

    public function __construct(array $subject = array(), $msg = null)
    {
        self::addDefaultValidators();
        if (!is_array($subject)) {
            $subject = array($subject);
        }
        $this->subject = $subject;
        $this->error_msg = $msg;
    }
    public function is($key, $error_msg = null)
    {
        if (array_key_exists($key, $this->subject)) {
            $this->fill($this->subject[$key], $error_msg);
            return $this;
        }
        $this->fill($key, $error_msg);

        return $this;
    }
    public static function addValidator($method, $callback)
    {
        if (!is_object($callback)) {
            Events\eventful::notify(new Events\event(self::EVENT_NOT_OBJECT, 'validator', 'error'));
            return false;
        }
        self::$callbacks[strtolower($method)] = $callback;
        return true;
    }
    public static function addValidatorsArray(array $validators)
    {
        foreach ($validators as $name => $callback) {
            if (!self::addValidator($name, $callback)) {
                return false;
            }
        }
        return true;
    }
    public static function addValidatorFile($file)
    {
        if (file_exists($file)) {
            $validators = include $file;
            return self::addValidatorsArray($validators);
        }
        Events\eventful::notify(new Events\event(self::EVENT_FILE_NOTFOUND, 'validator', 'error'));
        return false;
    }
    public function hasErrors()
    {
        return count($this->errors);
    }
    public function getErrors()
    {
        return $this->errors;
    }
    public function valid()
    {
        if ($this->hasErrors()) {
            return false;
        }
        return true;
    }
    public function __call($method, $args)
    {
        if (!$this->error_msg = end($args)) {
            $this->error_msg = 'Not valid!';
        }
        $validator_name = strtolower($method);

        if (!array_key_exists($validator_name, self::$callbacks)) {
            throw new \BadMethodCallException("Unknown validator method $method()");
        }

        $validator = self::$callbacks[$validator_name];
        array_unshift($args, $this->candidate);
        $result = (bool)call_user_func_array($validator, $args);

        if ($result === false) {
            $this->errors[$this->key][] = $this->error_msg;
            Events\eventful::notify(new Events\event(self::EVENT_NOTVALID, 'validator', $this->error_msg, 'error'));
        }
        return $this;
    }
    protected function fill($key, $error_msg)
    {
        if (null === $error_msg) {
            $error_msg = 'Not valid!';
        }
        $this->candidate = $key;
        $this->key = $key;
        $this->error_msg = $error_msg;
    }
    protected static function addDefaultValidators()
    {
        self::$callbacks['null'] = function ($str) {
            return $str === null || $str === '';
        };
        self::$callbacks['max'] = function ($str, $max) {
            $len = strlen($str);
            return $len <= $max;
        };
        self::$callbacks['min'] = function ($str, $min) {
            $len = strlen($str);
            return $len >= $min;
        };
        self::$callbacks['int'] = function ($str) {
            return (string)$str === ((string)(int)$str);
        };
        self::$callbacks['float'] = function ($str) {
            return (string)$str === ((string)(float)$str);
        };
        self::$callbacks['email'] = function ($str) {
            return filter_var($str, FILTER_VALIDATE_EMAIL) !== false;
        };
        self::$callbacks['url'] = function ($str) {
            return filter_var($str, FILTER_VALIDATE_URL) !== false;
        };

        self::$callbacks['alnum'] = function ($str) {
            return ctype_alnum($str);
        };
        self::$callbacks['alpha'] = function ($str) {
            return ctype_alpha($str);
        };
        self::$callbacks['contains'] = function ($str, $needle) {
            return strpos($str, $needle) !== false;
        };
        self::$callbacks['sameas'] = function ($str, $needle) {
            return (strcmp($str, $needle) === 0);
        };
        self::$callbacks['regex'] = function ($str, $pattern) {
            return preg_match($pattern, $str);
        };
        self::$callbacks['chars'] = function ($str, $chars) {
            return preg_match("/[$chars]+/i", $str);
        };
    }
}

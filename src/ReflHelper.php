<?php
/**
 * @author  saberyjs
 * @email saberyjs@gmail.com
 * @qq-email 1174332406@qq.com
 * @see https://github.com/SABERYJS/php-refl-helper
 * @license  MIT
 * User: saberyjs
 */

namespace saberyjs\refl_helper;

use saberyjs\exception\ClassNotFoundException;

/**
 * this is a dependency injector
 * **/
class ReflHelper
{

    /**
     * if we encounter a  value that has not default value,we can init it`value with it
     * @var $defaultValue array
     * **/
    private $defaultValue = [];

    /**
     * @var  $config array;
     * **/
    private $config = [
        'method' => [
            'getInstance'
        ],
        'factory' => []
    ];
    /**
     * @var $instance ReflHelper
     * **/
    private static $instance;

    /**
     * @param $config array
     * @param $defaultValue array
     * **/
    private function __construct($config, $defaultValue)
    {
        !empty($config) && $this->setConfig($config);
        !empty($defaultValue) && $this->configDefaultValue($defaultValue);
    }

    /**
     * @param  $config array
     * **/
    public function configDefaultValue(array $config)
    {
        if (!is_array($config)) {
            throw  new \InvalidArgumentException('$config must be type array');
        }
        $this->defaultValue = array_merge($this->defaultValue, $config);
    }

    /**
     * @param  $config array
     * **/
    public function setConfig(array $config)
    {
        if (!is_array($config)) {
            throw  new \InvalidArgumentException('$config must be type array');
        }
        $this->config = array_merge($this->config, $config);
    }


    /**
     * @param $key string
     * @param $default mixed
     * @param $isValue bool
     * @return  mixed
     * **/
    public function getConfig($key, $default = null, $isValue)
    {
        $source = $isValue ? $this->defaultValue : $this->config;
        if (isset($source[$key])) {
            return $source[$key];
        } else {
            return $default;
        }
    }

    /**
     * @param  $callback callable
     * @return  object|null
     * **/
    private function callBackupMethod(callable $callback)
    {
        if (!is_callable($callback)) {
            throw  new \InvalidArgumentException('$callback must be type callable');
        }
        if (!empty(($methods = $this->getConfig('method', 'getInstance', false)))) {
            if (!is_array($methods)) {
                $methods = [$methods];
            }
        } else {
            $methods[] = 'getInstance';
        }
        foreach ($methods as $met) {
            $ret = call_user_func($callback, $met);
            if (!empty($ret) && is_object($ret)) {
                return $ret;
            } else {
                continue;
            }
        }
        return null;
    }

    /**
     * @param  $config array
     * @param $defaultValue
     * @throws  \InvalidArgumentException
     * @return  ReflHelper
     * **/
    public static function getInstance(array $config = null, array $defaultValue = null)
    {
        if (empty(self::$instance)) {
            self::$instance = new self($config, $defaultValue);
        }
        return self::$instance;
    }

    /**
     * @param   $className string
     * @throws  \RuntimeException|\ReflectionException
     * @return  object
     * **/
    public function createFromFactory($className)
    {
        if (empty($this->config['factory'])) {
            return null;
        }

        if (!isset($this->config['factory'][$className])) {
            return null;
        }

        $value = $this->config['factory'][$className];
        if (is_object($value)&&!$value instanceof \Closure) {
            return $value;
        } else {
            //$value can be string or array,but must be callable
            if (is_callable($value)) {
                if (is_string($value) || $value instanceof \Closure) {
                    return call_user_func_array($value, $this->parseMethodDependences(new \ReflectionFunction($value)));
                } else {
                    //type array
                    return $this->callMethod($value[0], $value[1]);
                }
            } else {
                throw new \RuntimeException('please check your <b>ReflHelper</b> config');
            }
        }
    }


    /**
     * @param  $className string|\ReflectionClass
     * @return  object
     * @throws  \RuntimeException|\ReflectionException
     * **/
    public function get($className)
    {
        try {
            if (is_string($className)) {
                /**
                 * please check whether you has register related autoload method
                 * **/
                if (!class_exists($className)) {
                    throw  new \RuntimeException("$className class not exist");
                }
                $reflClass = new \ReflectionClass($className);
            } else {
                if (!is_object($className) || !$className instanceof \ReflectionClass) {
                    throw new \InvalidArgumentException('$className can be string or ReflectionClass');
                }
                /**
                 * @var $reflClass \ReflectionClass
                 * **/
                $reflClass = $className;
                $className = $reflClass->getName();
            }

            /**
             * factory exist for specified $className
             * **/
            if(!empty(($inst=$this->createFromFactory($className)))){
                return $inst;
            }

            /**
             * check if constructor exist
             * **/
            $method = $reflClass->getMethod('__construct');
            if (empty($method) || !$method->isPublic()) {
                //constructor is private or not exist ,so we can only call other method
                //this can be configured by call method setConfig
                $callback = function ($met) use ($reflClass, $className) {
                    if (!is_string($met)) {
                        throw  new \InvalidArgumentException('$reflClass must be type of string');
                    }
                    return $this->callStaticMethod($className, $met, $this->parseMethodDependences($reflClass->getMethod($met)));
                };
                if (!empty(($inst = $this->callBackupMethod($callback)))) {
                    return $inst;
                } else {
                    throw  new \RuntimeException("can not init class $className");
                }
            } else {
                return $this->createObject($reflClass, $this->parseMethodDependences($method));
            }
            //parse method params firstly

        } catch (\ReflectionException $e) {
            throw  new \RuntimeException($e->getMessage());
        }
    }

    /**
     * @param $reflClass \ReflectionClass
     * @param $params array
     * @return  object
     * **/
    private function createObject($reflClass, $params)
    {
        return $reflClass->newInstanceArgs($params);
    }

    /**
     * @param  $className string
     * @param $method string
     * @param  $params array
     * @throws  \ReflectionException|\RuntimeException
     * @return object|null
     * **/
    public function callStaticMethod($className, $method, $params)
    {
        if (!class_exists($className)) {
            throw new ClassNotFoundException($className);
        }
        $reflClass = new \ReflectionClass($className);
        //check method exist
        if (!empty(($refMethod = $reflClass->getMethod($method))) && $refMethod->isStatic()) {
            return call_user_func_array($className . '::' . $method, $params);
        } else {
            throw new \RuntimeException("can not call static $method of $className");
        }
    }

    /**
     * @param  $target object
     * @param  $method string
     * @param  $params array
     * @return  mixed
     * @throws  \ReflectionException|\InvalidArgumentException|\RuntimeException
     * */
    public function callMethod($target, $method, $params = null)
    {
        if (!is_object($target)) {
            throw  new \InvalidArgumentException('$target is must be type of Object');
        }

        if ($method === '__construct') {
            throw  new \RuntimeException('if you want get a instance of specified class,you should call like this: (new ReflHelper())->get');
        }
        $reflClass = new \ReflectionClass($target);

        if ($reflClass->getMethod($method) === null) {
            $className = $reflClass->getName();
            throw  new \RuntimeException("{$className}  has not method called {$method}");
        }

        if (is_null($params)) {
            $params = self::parseMethodDependences($reflClass->getMethod($method));
        }
        return ($reflClass->getMethod($method))->invokeArgs($target, $params);
    }


    /**
     * @param  $method \ReflectionMethod|\ReflectionFunction
     * @return  array
     * @throws  \InvalidArgumentException|\RuntimeException|\ReflectionException
     * **/
    private function parseMethodDependences($method)
    {
        if (!$method instanceof \ReflectionMethod && !$method instanceof \ReflectionFunction) {
            throw  new \InvalidArgumentException('$method must type of ReflectionClass or ReflectionFunction');
        }

        $params = [];
        //echo 'sdk';die();
        foreach ($method->getParameters() as $parameter) {
            if ($parameter->getClass() === null) {
                if ($parameter->isDefaultValueAvailable()) {
                    $params[] = $parameter->getDefaultValue();
                } else {
                    $argName = $parameter->getName();
                    if (!empty(($defValue = $this->getConfig($argName, null, true)))) {
                        $params[] = $defValue;
                        continue;
                    }
                    $methodName = $method->getName();
                    throw  new \InvalidArgumentException("$argName is required for method $methodName");
                }
            } else {
                $className = $parameter->getClass()->getName();
                if (!class_exists($className)) {
                    throw  new \RuntimeException("class $className not exist");
                }
                $params[] = $this->get($className);
            }
        }
        return $params;
    }
}
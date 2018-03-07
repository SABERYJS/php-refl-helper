<?php
/**
 * Created by PhpStorm.
 * User: saberyjs
 * Date: 18-3-6
 * Time: 下午11:59
 */

namespace saberyjs\refl_helper;
class ReflHelper
{

    /**
     * if we encounter a  value that has not default value,we can init it`value with it
     * @var $defaultValue array
     * **/
    private static $defaultValue = [];

    /**
     * @param  $config array
     * **/
    public static function configDefaultValue($config)
    {
        self::$defaultValue = $config;
    }

    /**
     * @param  $className string|\ReflectionClass
     * @return  object
     * @throws  \RuntimeException|\ReflectionException
     * **/
    public static function getInstance($className)
    {
        try {
            if (is_string($className)) {
                if (!class_exists($className)) {
                    throw  new \RuntimeException("$className class not exist");
                }
                $reflClass = new \ReflectionClass($className);
            } else {
                /**
                 * @var $reflClass \ReflectionClass
                 * **/
                $reflClass = $className;
                $className = $reflClass->getName();
            }
            $method = $reflClass->getMethod('__construct');
            if (!$method->isPublic()) {
                //constructor is private ,so we can only call other method
                //here,we call getInstance method
                if (($method = $reflClass->getMethod('getInstance')) !== null && $method->isPublic() && $method->isStatic()) {
                    $method = $reflClass->getMethod('getInstance');
                    return self::callMethod($reflClass, $method, self::parseMethodDependences($method));
                } else {
                    throw  new \RuntimeException("can not init class $className");
                }
            } else {
                return self::createObject($reflClass, self::parseMethodDependences($method));
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
    private static function createObject($reflClass, $params)
    {
        return $reflClass->newInstanceArgs($params);
    }

    /**
     * @param  $reflClass \ReflectionClass
     * @param  $method \ReflectionMethod
     * @param  $params array
     * @return  mixed
     * @throws  \ReflectionException
     * */
    public static function callMethod($reflClass, $method, $params = null)
    {
        if (is_string($reflClass)) {
            if (!class_exists($reflClass)) {
                throw  new \RuntimeException("$reflClass not exist");
            }
            $reflClass = new \ReflectionClass($reflClass);
        }
        if (!$reflClass instanceof \ReflectionClass) {
            throw new \RuntimeException('arg $reflClass must instance of ReflectionClass');
        }
        if (!is_string($method) && !$method instanceof \ReflectionMethod) {
            throw new \InvalidArgumentException('$method can be string or ReflectionMethod instance');
        }

        if (is_object($method)) {
            $method = $method->getName();
        }

        if ($method === '__construct') {
            throw  new \RuntimeException('if you want get a instance of specified class,you should call method ReflHelper::getInstance');
        }

        if ($reflClass->getMethod($method) === null) {
            $className = $reflClass->getName();
            throw  new \RuntimeException("{$className}  has not method called {$method}");
        }

        if (is_null($params)) {
            $params = self::parseMethodDependences($method);
        }
        return ($reflClass->getMethod($method))->invokeArgs($reflClass, $params);
    }


    /**
     * @param  $method \ReflectionMethod
     * @return  array
     * @throws  \InvalidArgumentException|\RuntimeException|\ReflectionException
     * **/
    private static function parseMethodDependences($method)
    {
        if (!$method instanceof \ReflectionMethod) {
            throw  new \InvalidArgumentException('$method must type of ReflectionClass');
        }

        $params = [];
        foreach ($method->getParameters() as $parameter) {
            if ($parameter->getClass() != null) {
                if ($parameter->isDefaultValueAvailable()) {
                    $params[] = $parameter->getDefaultValue();
                } else {
                    $argName = $parameter->getName();
                    if(isset(self::$defaultValue[$argName])){
                        $params[]=self::$defaultValue[$argName];
                    }
                    $methodName = $method->getName();
                    throw  new \InvalidArgumentException("$argName is required for method $methodName");
                }
            } else {
                $className = $parameter->getClass()->getName();
                if (!class_exists($className)) {
                    throw  new \RuntimeException("class $className not exist");
                }
                $params[] = self::getInstance($className);
            }
        }
        return $params;
    }
}
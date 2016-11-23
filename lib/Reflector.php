<?php

namespace Atreyu;

interface Reflector
{
    /**
     * Retrieves ReflectionClass instances, caching them for future retrieval
     *
     * @param string $class
     * @return \ReflectionClass
     */
    public function getClass($class);

    /**
     * Retrieves and caches the constructor (ReflectionMethod) for the specified class
     *
     * @param string $class
     * @return \ReflectionMethod
     */
    public function getCtor($class);

    /**
     * Retrieves and caches an array of constructor parameters for the given class
     *
     * @param string $class
     * @return \ReflectionParameter[]
     */
    public function getCtorParams($class);

    /**
     * Retrieves the class type-hint from a given ReflectionParameter or doc block
     *
     * ReflectionParameter:
     *
     * There is no way to directly access a parameter's type-hint without
     * instantiating a new ReflectionClass instance and calling its getName()
     * method. This method stores the results of this approach so that if
     * the same parameter type-hint or ReflectionClass is needed again we
     * already have it cached.
     *
     * Doc block:
     *
     * There might be more than one class type-hinted definition in the doc block
     * If there is more than one type-hinted definition in the doc block and there are arguments given (optional)
     * then the doc block type-hinted definitions will each be matched against the given arguments.
     * If no argument match is found, the first type-hinted definition will be used instead.
     *
     * If there is more than one type-hinted definition in the doc block and no arguments are given
     * then the first doc block type-hinted definition will be used.
     *
     * @param \ReflectionFunctionAbstract $function
     * @param \ReflectionParameter        $param
     * @param array                       $arguments
     *
     * @return
     */
    public function getParamTypeHint(\ReflectionFunctionAbstract $function, \ReflectionParameter $param, array $arguments = []);

    /**
     * Retrieves and caches a reflection for the specified function
     *
     * @param string $functionName
     * @return \ReflectionFunction
     */
    public function getFunction($functionName);

    /**
     * Retrieves and caches a reflection for the specified class method
     *
     * @param mixed $classNameOrInstance
     * @param string $methodName
     * @return \ReflectionMethod
     */
    public function getMethod($classNameOrInstance, $methodName);
}

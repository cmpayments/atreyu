<?php

namespace Atreyu;

use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Context;

class StandardReflector extends BaseReflector implements Reflector
{
    public function getClass($class)
    {
        return new ExtendedReflectionClass($class);
    }

    public function getCtor($class)
    {
        $reflectionClass = new ExtendedReflectionClass($class);

        return $reflectionClass->getConstructor();
    }

    public function getCtorParams($class)
    {
        return ($reflectedCtor = $this->getCtor($class))
            ? $reflectedCtor->getParameters()
            : null;
    }

    public function getParamTypeHint(\ReflectionFunctionAbstract $function, \ReflectionParameter $param, array $arguments = [])
    {
        if ($reflectionClass = $param->getClass()) {
            $typeHint = $reflectionClass->getName();
        } elseif (($function instanceof \ReflectionMethod)
            && ($docBlockParams = $this->getDocBlock($function)->getTagsByName('param'))
            && !empty($docBlockParams)
        ) {
            $typeHint = $this->getParamDocBlockHint($docBlockParams, $param, $arguments);
        } else {
            $typeHint = null;
        }

        return $typeHint;
    }

    public function getFunction($functionName)
    {
        return new \ReflectionFunction($functionName);
    }

    public function getMethod($classNameOrInstance, $methodName)
    {
        $className = is_string($classNameOrInstance)
            ? $classNameOrInstance
            : get_class($classNameOrInstance);

        return new \ReflectionMethod($className, $methodName);
    }

    public function getDocBlock(\ReflectionMethod $method)
    {
        $class = $this->getClass($method->class);

        return new DocBlock(
            $method->getDocComment(),
            new Context(
                $class->getNamespaceName(),
                $class->getUseStatements()
            )
        );
    }
}

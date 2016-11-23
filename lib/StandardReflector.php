<?php

namespace Atreyu;

use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Context;

class StandardReflector implements Reflector
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
        } elseif (($docBlockParams = $this->getDocBlock($function)->getTagsByName('param')) && !empty($docBlockParams)) {

            $typeHint = false;

            /** @var DocBlock\Tag\ParamTag $docBlockParam */
            foreach ($docBlockParams as $docBlockParam) {

                if (($param->getName() === ltrim($docBlockParam->getVariableName(), '$'))
                    && (!empty($docBlockParam->getType()))
                ) {
                    $definitions = explode('|', $docBlockParam->getType());

                    foreach ($arguments as $key => $argument) {

                        foreach ($definitions as $definition) {

                            if (is_object($argument)
                                && in_array(ltrim($definition, '\\'), $this->getImplemented(get_class($argument)))
                                && (is_numeric($key) || (ltrim($docBlockParam->getVariableName(), '$') === $key)
                                )) {
                                $typeHint = $definition;

                                // no need to loop again, since we found a match already!
                                continue 3;
                            }
                        }
                    }

                    if ($typeHint === false) {

                        // use first definition, there is no way to know which instance of the hinted doc block definitions is actually required
                        // because there were either no arguments given or no argument match was found
                        list($typeHint, ) = $definitions;
                    }
                }
            }
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

    public function getDocBlock(\ReflectionFunctionAbstract $method)
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

    public function getImplemented($className)
    {
        return array_merge([$className], class_implements($className));
    }
}

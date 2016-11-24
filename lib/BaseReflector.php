<?php

namespace Atreyu;

use phpDocumentor\Reflection\DocBlock;

abstract class BaseReflector
{
    /**
     * @param array                $docBlockParams
     * @param \ReflectionParameter $param
     * @param array                $arguments
     *
     * @return bool|string
     */
    public function getParamDocBlockHint(array $docBlockParams, \ReflectionParameter $param, array $arguments = [])
    {
        $typeHint = false;

        /** @var DocBlock\Tag\ParamTag $docBlockParam */
        foreach ($docBlockParams as $docBlockParam) {

            if (!($docBlockParam instanceof DocBlock\Tag\ParamTag)) {
                continue;
            }

            $type = $docBlockParam->getType();
            $docBlockParamName = $docBlockParam->getVariableName();

            if (($param->getName() === ltrim($docBlockParamName, '$'))
                && (!empty($type))
            ) {
                $definitions = explode('|', $type);

                foreach ($arguments as $key => $argument) {

                    foreach ($definitions as $definition) {

                        if (is_object($argument)
                            && in_array(ltrim($definition, '\\'), $this->getImplemented(get_class($argument)))
                            && (is_numeric($key) || (ltrim($docBlockParamName, '$') === $key)
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
                    list($firstDefinition,) = $definitions;

                    if (!in_array(strtolower($firstDefinition), ['int', 'float', 'bool', 'string', 'array'])) {

                        $typeHint = $firstDefinition;
                    }
                }
            }
        }

        return $typeHint;
    }

    /**
     * @param $className
     *
     * @return array
     */
    public function getImplemented($className)
    {
        return array_merge([$className], class_implements($className));
    }
}

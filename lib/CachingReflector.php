<?php

namespace Atreyu;

use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Context;

class CachingReflector extends BaseReflector implements Reflector
{
    const CACHE_KEY_CLASSES = 'atreyu.refls.classes.';
    const CACHE_KEY_CTORS = 'atreyu.refls.ctors.';
    const CACHE_KEY_CTOR_PARAMS = 'atreyu.refls.ctor-params.';
    const CACHE_KEY_FUNCS = 'atreyu.refls.funcs.';
    const CACHE_KEY_METHODS = 'atreyu.refls.methods.';
    const CACHE_KEY_DOC_BLOCK = 'atreyu.refls.doc-block.';
    const CACHE_KEY_IMPLEMENTED = 'atreyu.refls.implemented.';

    private $reflector;
    private $cache;

    public function __construct(Reflector $reflector = null, ReflectionCache $cache = null)
    {
        $this->reflector = $reflector ?: new StandardReflector;
        $this->cache = $cache ?: new ReflectionCacheArray;
    }

    public function getClass($class)
    {
        $cacheKey = self::CACHE_KEY_CLASSES.strtolower($class);

        if (!$reflectionClass = $this->cache->fetch($cacheKey)) {
            $reflectionClass = new ExtendedReflectionClass($class);
            $this->cache->store($cacheKey, $reflectionClass);
        }

        return $reflectionClass;
    }

    public function getCtor($class)
    {
        $cacheKey = self::CACHE_KEY_CTORS.strtolower($class);

        $reflectedCtor = $this->cache->fetch($cacheKey);

        if ($reflectedCtor === false) {
            $reflectionClass = $this->getClass($class);
            $reflectedCtor = $reflectionClass->getConstructor();
            $this->cache->store($cacheKey, $reflectedCtor);
        }

        return $reflectedCtor;
    }

    public function getCtorParams($class)
    {
        $cacheKey = self::CACHE_KEY_CTOR_PARAMS.strtolower($class);

        $reflectedCtorParams = $this->cache->fetch($cacheKey);

        if (false !== $reflectedCtorParams) {
            return $reflectedCtorParams;
        } elseif ($reflectedCtor = $this->getCtor($class)) {
            $reflectedCtorParams = $reflectedCtor->getParameters();
        } else {
            $reflectedCtorParams = null;
        }

        $this->cache->store($cacheKey, $reflectedCtorParams);

        return $reflectedCtorParams;
    }

    public function getParamTypeHint(\ReflectionFunctionAbstract $function, \ReflectionParameter $param, array $arguments = [])
    {
        $lowParam = strtolower($param->name);

        if ($function instanceof \ReflectionMethod) {
            $lowClass = strtolower($function->class);
            $lowMethod = strtolower($function->name);
            $paramCacheKey = self::CACHE_KEY_CLASSES."{$lowClass}.{$lowMethod}.param-{$lowParam}";
        } else {
            $lowFunc = strtolower($function->name);
            $paramCacheKey = ($lowFunc !== '{closure}')
                ? self::CACHE_KEY_FUNCS.".{$lowFunc}.param-{$lowParam}"
                : null;
        }

        $typeHint = ($paramCacheKey === null) ? false : $this->cache->fetch($paramCacheKey);

        if (false !== $typeHint) {
            return $typeHint;
        }

        if ($reflectionClass = $param->getClass()) {
            $typeHint = $reflectionClass->getName();
            $classCacheKey = self::CACHE_KEY_CLASSES.strtolower($typeHint);
            $this->cache->store($classCacheKey, $this->getClass($param->getClass()->getName()));
        } elseif (($function instanceof \ReflectionMethod)
            && ($docBlockParams = $this->getDocBlock($function)->getTagsByName('param'))
            && !empty($docBlockParams)
        ) {
            $typeHint = $this->getParamDocBlockHint($docBlockParams, $param, $arguments);

            // store the ExtendedReflectionClass in the cache
            if ($typeHint !== false) {

                $classCacheKey = self::CACHE_KEY_CLASSES.strtolower($typeHint);
                $this->cache->store($classCacheKey, $this->getClass($typeHint));
            }
        } else {
            $typeHint = null;
        }

        $this->cache->store($paramCacheKey, $typeHint);

        return $typeHint;
    }

    public function getFunction($functionName)
    {
        $lowFunc = strtolower($functionName);
        $cacheKey = self::CACHE_KEY_FUNCS.$lowFunc;

        $reflectedFunc = $this->cache->fetch($cacheKey);

        if (false === $reflectedFunc) {
            $reflectedFunc = new \ReflectionFunction($functionName);
            $this->cache->store($cacheKey, $reflectedFunc);
        }

        return $reflectedFunc;
    }

    public function getMethod($classNameOrInstance, $methodName)
    {
        $className = is_string($classNameOrInstance)
            ? $classNameOrInstance
            : get_class($classNameOrInstance);

        $cacheKey = self::CACHE_KEY_METHODS.strtolower($className).'.'.strtolower($methodName);

        if (!$reflectedMethod = $this->cache->fetch($cacheKey)) {
            $reflectedMethod = new \ReflectionMethod($className, $methodName);
            $this->cache->store($cacheKey, $reflectedMethod);
        }

        return $reflectedMethod;
    }

    public function getDocBlock(\ReflectionMethod $method)
    {
        $cacheKey = self::CACHE_KEY_DOC_BLOCK.strtolower($method->class);
        if (!$docBlock = $this->cache->fetch($cacheKey)) {

            $class = $this->getClass($method->class);

            $docBlock = new DocBlock(
                $method->getDocComment(),
                new Context(
                    $class->getNamespaceName(),
                    $class->getUseStatements()
                )
            );

            $this->cache->store($cacheKey, $docBlock);
        }

        return $docBlock;
    }

    /**
     * @param $className
     *
     * @return array|bool
     */
    public function getImplemented($className)
    {
        $cacheKey = self::CACHE_KEY_IMPLEMENTED.strtolower($className);

        if (!$implemented = $this->cache->fetch($cacheKey)) {
            $implemented = parent::getImplemented($className);
            $this->cache->store($cacheKey, $implemented);
        }

        return $implemented;
    }
}

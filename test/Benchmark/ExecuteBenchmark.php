<?php

namespace Atreyu\Test\Benchmark;

use Athletic\AthleticEvent;
use Atreyu\Injector;

class ExecuteBenchmark extends AthleticEvent
{
    private $injector;
    private $noop;

    public function classSetUp()
    {
        $this->injector = new Injector();
        $this->noop = new Noop();
    }

    /**
     * @baseline
     * @iterations 10000
     */
    public function native_invoke_closure()
    {
        call_user_func(function () {
            // call-target, intenionally left empty
        });
    }

    /**
     * @iterations 10000
     */
    public function native_invoke_method()
    {
        call_user_func([$this->noop, 'noop']);
    }

    /**
     * @iterations 10000
     */
    public function invoke_closure()
    {
        $this->injector->execute(function () {
            // call-target, intenionally left empty
        });
    }

    /**
     * @iterations 10000
     */
    public function invoke_method()
    {
        $this->injector->execute([$this->noop, 'noop']);
    }

    /**
     * @iterations 10000
     */
    public function invoke_with_named_parameters()
    {
        $this->injector->execute([$this->noop, 'namedNoop'], [':name' => 'foo']);
    }
}

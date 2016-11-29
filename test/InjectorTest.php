<?php

namespace Atreyu\test;

use Atreyu\Injector;
use Atreyu\InjectorException;

class InjectorTest extends \PHPUnit_Framework_TestCase
{
    public function testMakeInstanceInjectsSimpleConcreteDependency()
    {
        $injector = new Injector;
        $this->assertEquals(new TestNeedsDep(new TestDependency),
            $injector->make('Atreyu\Test\TestNeedsDep')
        );
    }

    public function testMakeInstanceReturnsNewInstanceIfClassHasNoConstructor()
    {
        $injector = new Injector;
        $this->assertEquals(new TestNoConstructor, $injector->make('Atreyu\Test\TestNoConstructor'));
    }

    public function testMakeInstanceReturnsAliasInstanceOnNonConcreteTypehint()
    {
        $injector = new Injector;
        $injector->alias('Atreyu\Test\DepInterface', 'Atreyu\Test\DepImplementation');
        $this->assertEquals(new DepImplementation, $injector->make('Atreyu\Test\DepInterface'));
    }

    /**
     * @expectedException \Atreyu\InjectionException
     * @expectedExceptionCode \Atreyu\Injector::E_NEEDS_DEFINITION
     */
    public function testMakeInstanceThrowsExceptionOnInterfaceWithoutAlias()
    {
        $injector = new Injector;
        $injector->make('Atreyu\Test\DepInterface');
    }

    /**
     * @expectedException \Atreyu\InjectionException
     * @expectedExceptionCode \Atreyu\Injector::E_NEEDS_DEFINITION
     */
    public function testMakeInstanceThrowsExceptionOnNonConcreteCtorParamWithoutImplementation()
    {
        $injector = new Injector;
        $injector->make('Atreyu\Test\RequiresInterface');
    }

    public function testMakeInstanceBuildsNonConcreteCtorParamWithAlias()
    {
        $injector = new Injector;
        $injector->alias('Atreyu\Test\DepInterface', 'Atreyu\Test\DepImplementation');
        $obj = $injector->make('Atreyu\Test\RequiresInterface');
        $this->assertInstanceOf('Atreyu\Test\RequiresInterface', $obj);
    }

    public function testMakeInstancePassesNullCtorParameterIfNoTypehintOrDefaultCanBeDetermined()
    {
        $injector = new Injector;
        $nullCtorParamObj = $injector->make('Atreyu\Test\ProvTestNoDefinitionNullDefaultClass');
        $this->assertEquals(new ProvTestNoDefinitionNullDefaultClass, $nullCtorParamObj);
        $this->assertEquals(null, $nullCtorParamObj->arg);
    }

    public function testMakeInstanceReturnsSharedInstanceIfAvailable()
    {
        $injector = new Injector;
        $injector->define('Atreyu\Test\RequiresInterface', ['dep' => 'Atreyu\Test\DepImplementation']);
        $injector->share('Atreyu\Test\RequiresInterface');
        $injected = $injector->make('Atreyu\Test\RequiresInterface');

        $this->assertEquals('something', $injected->testDep->testProp);
        $injected->testDep->testProp = 'something else';

        $injected2 = $injector->make('Atreyu\Test\RequiresInterface');
        $this->assertEquals('something else', $injected2->testDep->testProp);
    }

    /**
     * @expectedException \Atreyu\InjectorException
     */
    public function testMakeInstanceThrowsExceptionOnClassLoadFailure()
    {
        $injector = new Injector;
        $injector->make('ClassThatDoesntExist');
    }

    public function testMakeInstanceUsesCustomDefinitionIfSpecified()
    {
        $injector = new Injector;
        $injector->define('Atreyu\Test\TestNeedsDep', ['testDep' =>'Atreyu\Test\TestDependency']);
        $injected = $injector->make('Atreyu\Test\TestNeedsDep', ['testDep' =>'Atreyu\Test\TestDependency2']);
        $this->assertEquals('testVal2', $injected->testDep->testProp);
    }

    public function testMakeInstanceCustomDefinitionOverridesExistingDefinitions()
    {
        $injector = new Injector;
        $injector->define('Atreyu\Test\InjectorTestChildClass', [':arg1' =>'First argument', ':arg2' =>'Second argument']);
        $injected = $injector->make('Atreyu\Test\InjectorTestChildClass', [':arg1' =>'Override']);
        $this->assertEquals('Override', $injected->arg1);
        $this->assertEquals('Second argument', $injected->arg2);
    }

    public function testMakeInstanceBasedOnTypeHintingWithArgumentDefinition()
    {
        $injector = new Injector;
        $injected = $injector->make('Atreyu\Test\TestMultiDepsNeeded', [new TestDependency3()]);

        $this->assertInstanceOf('Atreyu\Test\TestDependency', $injected->testDep);
        $this->assertInstanceOf('Atreyu\Test\TestDependency3', $injected->testDep2);
        $this->assertInstanceOf('Atreyu\Test\DepInterface', $injected->testDep2);
        $this->assertEquals('testVal', $injected->testDep->testProp);
        $this->assertEquals('testVal3', $injected->testDep2->testProp);
    }

    public function testMakeInstanceBasedOnTypeHintingWithAliasDefinition()
    {
        $injector = new Injector;
        $injector->alias('Atreyu\Test\DepInterface', 'Atreyu\Test\TestDependency3');
        $injected = $injector->make('Atreyu\Test\TestMultiDepsNeeded');

        $this->assertInstanceOf('Atreyu\Test\TestDependency', $injected->testDep);
        $this->assertInstanceOf('Atreyu\Test\TestDependency3', $injected->testDep2);
        $this->assertInstanceOf('Atreyu\Test\DepInterface', $injected->testDep2);
        $this->assertEquals('testVal', $injected->testDep->testProp);
        $this->assertEquals('testVal3', $injected->testDep2->testProp);
    }

    public function testMakeInstanceBasedOnDocBlockHintingWithArgumentDefinition()
    {
        /**
         * the first argument of TestMultiDepsNeeded2::__construct() can be an instance of two classes
         */

        // first test, test is with class TestDependency
        $injector = new Injector;
        $injected = $injector->make('Atreyu\Test\TestMultiDepsNeeded2', [new TestDependency(), new TestDependency3()]);

        $this->assertInstanceOf('Atreyu\Test\TestDependency', $injected->testDep);
        $this->assertInstanceOf('Atreyu\Test\TestDependency3', $injected->testDep2);
        $this->assertInstanceOf('Atreyu\Test\DepInterface', $injected->testDep2);
        $this->assertEquals('testVal', $injected->testDep->testProp);
        $this->assertEquals('testVal3', $injected->testDep2->testProp);

        // second test, test is with class TestDependency4
        $injector = new Injector;
        // please note that the arguments are actually in reversed order
        $injected = $injector->make('Atreyu\Test\TestMultiDepsNeeded2', [new TestDependency4(), new TestDependency3()]);

        $this->assertInstanceOf('Atreyu\Test\TestDependency4', $injected->testDep);
        $this->assertInstanceOf('Atreyu\Test\TestDependency3', $injected->testDep2);
        $this->assertInstanceOf('Atreyu\Test\DepInterface', $injected->testDep2);
        $this->assertEquals('testVal4', $injected->testDep->testProp);
        $this->assertEquals('testVal3', $injected->testDep2->testProp);
    }

    public function testMakeInstanceBasedOnDocBlockHintingWithArgumentDefinitionInWrongOrder()
    {
        /**
         * the first argument of TestMultiDepsNeeded2::__construct() can be an instance of two classes
         */

        // first test, test is with class TestDependency
        $injector = new Injector;
        // please note that the arguments are actually in reversed order
        $injected = $injector->make('Atreyu\Test\TestMultiDepsNeeded2', [new TestDependency3(), new TestDependency()]);

        $this->assertInstanceOf('Atreyu\Test\TestDependency', $injected->testDep);
        $this->assertInstanceOf('Atreyu\Test\TestDependency3', $injected->testDep2);
        $this->assertInstanceOf('Atreyu\Test\DepInterface', $injected->testDep2);
        $this->assertEquals('testVal', $injected->testDep->testProp);
        $this->assertEquals('testVal3', $injected->testDep2->testProp);

        // second test, test is with class TestDependency4
        $injector = new Injector;
        // please note that the arguments are actually in reversed order
        $injected = $injector->make('Atreyu\Test\TestMultiDepsNeeded2', [new TestDependency3(), new TestDependency4()]);

        $this->assertInstanceOf('Atreyu\Test\TestDependency4', $injected->testDep);
        $this->assertInstanceOf('Atreyu\Test\TestDependency3', $injected->testDep2);
        $this->assertInstanceOf('Atreyu\Test\DepInterface', $injected->testDep2);
        $this->assertEquals('testVal4', $injected->testDep->testProp);
        $this->assertEquals('testVal3', $injected->testDep2->testProp);
    }

    public function testMakeInstanceBasedOnDocBlockHintingWithAliasDefinition()
    {
        /**
         * the first argument of TestMultiDepsNeeded2::__construct() can be an instance of two classes
         * But no argument of params are defined so it will fallback to the first Doc block param definition
         */

        // first test, test is with class TestDependency
        $injector = new Injector;
        $injector->alias('Atreyu\Test\DepInterface', 'Atreyu\Test\TestDependency3');
        $injected = $injector->make('Atreyu\Test\TestMultiDepsNeeded2');

        // checking for class TestDependency::class and not TestDependency4::class because class TestDependency::class is the first Doc block param definition
        $this->assertInstanceOf('Atreyu\Test\TestDependency', $injected->testDep);
        $this->assertInstanceOf('Atreyu\Test\TestDependency3', $injected->testDep2);
        $this->assertInstanceOf('Atreyu\Test\DepInterface', $injected->testDep2);
        $this->assertEquals('testVal', $injected->testDep->testProp);
        $this->assertEquals('testVal3', $injected->testDep2->testProp);
    }

    public function testMakeInstanceBasedOnDocBlockHintingWithAliasDefinitionAndDefinedParams()
    {
        /**
         * the first argument of TestMultiDepsNeeded2::__construct() can be an instance of two classes
         */

        // first test, test is with class TestDependency
        $injector = new Injector;

        // distraction definings,
        // please note that the value of key 'non-existent-2' is also valid in TestMultiDepsNeeded2::class context
        // the key is also very important, just wanted to point that out :)
        $injector->defineParam('non-existent', new \stdClass());
        $injector->defineParam('non-existent-2', $injector->make('Atreyu\Test\TestDependency4'));

        // make
        $injector->alias('Atreyu\Test\DepInterface', 'Atreyu\Test\TestDependency3');
        $injector->defineParam('val1', $injector->make('Atreyu\Test\TestDependency'));
        $injected = $injector->make('Atreyu\Test\TestMultiDepsNeeded2');

        $this->assertInstanceOf('Atreyu\Test\TestDependency', $injected->testDep);
        $this->assertInstanceOf('Atreyu\Test\TestDependency3', $injected->testDep2);
        $this->assertInstanceOf('Atreyu\Test\DepInterface', $injected->testDep2);
        $this->assertEquals('testVal', $injected->testDep->testProp);
        $this->assertEquals('testVal3', $injected->testDep2->testProp);

        // second test, test is with class TestDependency2
        $injector = new Injector;

        // distraction definings
        // please note that the value of key 'non-existent-2' is also valid in TestMultiDepsNeeded2::class context
        // the key is also very important, just wanted to point that out :)
        $injector->defineParam('non-existent', new \stdClass());
        $injector->defineParam('non-existent-2', $injector->make('Atreyu\Test\TestDependency'));

        // make
        $injector->alias('Atreyu\Test\DepInterface', 'Atreyu\Test\TestDependency3');
        $injector->defineParam('val1', $injector->make('Atreyu\Test\TestDependency4'));
        $injected = $injector->make('Atreyu\Test\TestMultiDepsNeeded2');

        $this->assertInstanceOf('Atreyu\Test\TestDependency4', $injected->testDep);
        $this->assertInstanceOf('Atreyu\Test\TestDependency3', $injected->testDep2);
        $this->assertInstanceOf('Atreyu\Test\DepInterface', $injected->testDep2);
        $this->assertEquals('testVal4', $injected->testDep->testProp);
        $this->assertEquals('testVal3', $injected->testDep2->testProp);
    }

    public function testMakeInstanceBasedOnDocBlockHintingWithAliasDefinitionAndArgumentDefinitions()
    {
        /**
         * the first argument of TestMultiDepsNeeded2::__construct() can be an instance of two classes
         */

        // first test, test is with class TestDependency
        $injector = new Injector;
        $injector->alias('Atreyu\Test\DepInterface', 'Atreyu\Test\TestDependency3');
        // please note the first item of $arguments has no meaning and is for test purposes only
        $injected = $injector->make('Atreyu\Test\TestMultiDepsNeeded2', [new \stdClass(), $injector->make('Atreyu\Test\TestDependency')]);

        $this->assertInstanceOf('Atreyu\Test\TestDependency', $injected->testDep);
        $this->assertInstanceOf('Atreyu\Test\TestDependency3', $injected->testDep2);
        $this->assertInstanceOf('Atreyu\Test\DepInterface', $injected->testDep2);
        $this->assertEquals('testVal', $injected->testDep->testProp);
        $this->assertEquals('testVal3', $injected->testDep2->testProp);

        // second test, test is with class TestDependency4
        $injector = new Injector;
        $injector->alias('Atreyu\Test\DepInterface', 'Atreyu\Test\TestDependency3');
        // please note the first item of $arguments has no meaning and is for test purposes only
        $injected = $injector->make('Atreyu\Test\TestMultiDepsNeeded2', [new \stdClass(), $injector->make('Atreyu\Test\TestDependency4')]);

        $this->assertInstanceOf('Atreyu\Test\TestDependency4', $injected->testDep);
        $this->assertInstanceOf('Atreyu\Test\TestDependency3', $injected->testDep2);
        $this->assertInstanceOf('Atreyu\Test\DepInterface', $injected->testDep2);
        $this->assertEquals('testVal4', $injected->testDep->testProp);
        $this->assertEquals('testVal3', $injected->testDep2->testProp);
    }

    public function testMakeInstanceBasedOnDocBlockHintingWithInstanceAsSharedItem()
    {
        $injector = new Injector;
        $injector->share('Atreyu\Test\TestDependency');
        $injector->share('Atreyu\Test\TestDependency2');
        $injected = $injector->make('Atreyu\Test\TestMakeInstanceFromSharedItem', ['var1Value', 'var2Value']);

        $this->assertInstanceOf('Atreyu\Test\TestDependency', $injected->testDep);
        $this->assertEquals('var1Value', $injected->var1);
        $this->assertEquals('var2Value', $injected->var2);

        $injector = new Injector;
        $injector->share($injector->make('Atreyu\Test\TestDependency'));
        $injector->share($injector->make('Atreyu\Test\TestDependency2'));
        $injected = $injector->make('Atreyu\Test\TestMakeInstanceFromSharedItem', ['var1Value', 'var2Value']);

        $this->assertInstanceOf('Atreyu\Test\TestDependency', $injected->testDep);
        $this->assertEquals('var1Value', $injected->var1);
        $this->assertEquals('var2Value', $injected->var2);

        $injector = new Injector;
        $injector->share('Atreyu\Test\TestDependency');
        $injector->share('Atreyu\Test\TestDependency2');
        $injected = $injector->make('Atreyu\Test\TestMakeInstanceFromSharedItem', [':var1' => 'var1Value', 'var2Value']);

        $this->assertInstanceOf('Atreyu\Test\TestDependency', $injected->testDep);
        $this->assertEquals('var1Value', $injected->var1);
        $this->assertEquals('var2Value', $injected->var2);

        $injector = new Injector;
        $injector->share('Atreyu\Test\TestDependency');
        $injector->share('Atreyu\Test\TestDependency2');
        $injected = $injector->make('Atreyu\Test\TestMakeInstanceFromSharedItem', [':var1' => 'var1Value', ':var2' => 'var2Value']);

        $this->assertInstanceOf('Atreyu\Test\TestDependency', $injected->testDep);
        $this->assertEquals('var1Value', $injected->var1);
        $this->assertEquals('var2Value', $injected->var2);
    }

    public function testMakeInstanceStoresShareIfMarkedWithNullInstance()
    {
        $injector = new Injector;
        $injector->share('Atreyu\Test\TestDependency');
        $injector->make('Atreyu\Test\TestDependency');
    }

    public function testMakeInstanceUsesReflectionForUnknownParamsInMultiBuildWithDeps()
    {
        $injector = new Injector;
        $obj = $injector->make('Atreyu\Test\TestMultiDepsWithCtor', ['val1' =>'Atreyu\Test\TestDependency']);
        $this->assertInstanceOf('Atreyu\Test\TestMultiDepsWithCtor', $obj);

        $obj = $injector->make('Atreyu\Test\NoTypehintNoDefaultConstructorClass',
                               ['val1' =>'Atreyu\Test\TestDependency']
        );
        $this->assertInstanceOf('Atreyu\Test\NoTypehintNoDefaultConstructorClass', $obj);
        $this->assertEquals(null, $obj->testParam);
    }

    /**
     * @expectedException \Atreyu\InjectionException
     * @expectedExceptionCode \Atreyu\Injector::E_UNDEFINED_PARAM
     */
    public function testMakeInstanceThrowsExceptionOnUntypehintedParameterWithoutDefinitionOrDefault()
    {
        $injector = new Injector;
        $obj = $injector->make('Atreyu\Test\InjectorTestCtorParamWithNoTypehintOrDefault');
        $this->assertNull($obj->val);
    }

    /**
     * @expectedException \Atreyu\InjectionException
     * @expectedExceptionCode \Atreyu\Injector::E_UNDEFINED_PARAM
     */
    public function testMakeInstanceThrowsExceptionOnUntypehintedParameterWithoutDefinitionOrDefaultThroughAliasedTypehint()
    {
        $injector = new Injector;
        $injector->alias('Atreyu\Test\TestNoExplicitDefine', 'Atreyu\Test\InjectorTestCtorParamWithNoTypehintOrDefault');
        $injector->make('Atreyu\Test\InjectorTestCtorParamWithNoTypehintOrDefaultDependent');
    }

    /**
     * @TODO
     * @expectedException \Atreyu\InjectorException
     */
    public function testMakeInstanceThrowsExceptionOnUninstantiableTypehintWithoutDefinition()
    {
        $injector = new Injector;
        $obj = $injector->make('Atreyu\Test\RequiresInterface');
    }

    public function testTypelessDefineForDependency()
    {
        $thumbnailSize = 128;
        $injector = new Injector;
        $injector->defineParam('thumbnailSize', $thumbnailSize);
        $testClass = $injector->make('Atreyu\Test\RequiresDependencyWithTypelessParameters');
        $this->assertEquals($thumbnailSize, $testClass->getThumbnailSize(), 'Typeless define was not injected correctly.');
    }

    public function testTypelessDefineForAliasedDependency()
    {
        $injector = new Injector;
        $injector->defineParam('val', 42);

        $injector->alias('Atreyu\Test\TestNoExplicitDefine', 'Atreyu\Test\ProviderTestCtorParamWithNoTypehintOrDefault');
        $obj = $injector->make('Atreyu\Test\ProviderTestCtorParamWithNoTypehintOrDefaultDependent');
    }

    public function testMakeInstanceInjectsRawParametersDirectly()
    {
        $injector = new Injector;
        $injector->define('Atreyu\Test\InjectorTestRawCtorParams', [
            ':string' => 'string',
            ':obj' => new \StdClass,
            ':int' => 42,
            ':array' => [],
            ':float' => 9.3,
            ':bool' => true,
            ':null' => null,
        ]);

        $obj = $injector->make('Atreyu\Test\InjectorTestRawCtorParams');
        $this->assertInternalType('string', $obj->string);
        $this->assertInstanceOf('StdClass', $obj->obj);
        $this->assertInternalType('int', $obj->int);
        $this->assertInternalType('array', $obj->array);
        $this->assertInternalType('float', $obj->float);
        $this->assertInternalType('bool', $obj->bool);
        $this->assertNull($obj->null);
    }

    /**
     * @TODO
     * @expectedException \Exception
     */
    public function testMakeInstanceThrowsExceptionWhenDelegateDoes()
    {
        $injector= new Injector;

        $callable = $this->getMock(
            'CallableMock',
            ['__invoke']
        );

        $injector->delegate('TestDependency', $callable);

        $callable->expects($this->once())
            ->method('__invoke')
            ->will($this->throwException(new \Exception()));

        $injector->make('TestDependency');
    }

    public function testMakeInstanceHandlesNamespacedClasses()
    {
        $injector = new Injector;
        $injector->make('Atreyu\Test\SomeClassName');
    }

    public function testMakeInstanceDelegate()
    {
        $injector= new Injector;

        $callable = $this->getMock(
            'CallableMock',
            ['__invoke']
        );

        $callable->expects($this->once())
            ->method('__invoke')
            ->will($this->returnValue(new TestDependency()));

        $injector->delegate('Atreyu\Test\TestDependency', $callable);

        $obj = $injector->make('Atreyu\Test\TestDependency');

        $this->assertInstanceOf('Atreyu\Test\TestDependency', $obj);
    }

    public function testMakeInstanceWithStringDelegate()
    {
        $injector= new Injector;
        $injector->delegate('StdClass', 'Atreyu\Test\StringStdClassDelegateMock');
        $obj = $injector->make('StdClass');
        $this->assertEquals(42, $obj->test);
    }

    /**
     * @expectedException \Atreyu\ConfigException
     */
    public function testMakeInstanceThrowsExceptionIfStringDelegateClassHasNoInvokeMethod()
    {
        $injector= new Injector;
        $injector->delegate('StdClass', 'StringDelegateWithNoInvokeMethod');
    }

    /**
     * @expectedException \Atreyu\ConfigException
     */
    public function testMakeInstanceThrowsExceptionIfStringDelegateClassInstantiationFails()
    {
        $injector= new Injector;
        $injector->delegate('StdClass', 'SomeClassThatDefinitelyDoesNotExistForReal');
    }

    /**
     * @expectedException \Atreyu\InjectionException
     */
    public function testMakeInstanceThrowsExceptionOnUntypehintedParameterWithNoDefinition()
    {
        $injector = new Injector;
        $obj = $injector->make('Atreyu\Test\RequiresInterface');
    }

    public function testDefineAssignsPassedDefinition()
    {
        $injector = new Injector;
        $definition = ['dep' => 'Atreyu\Test\DepImplementation'];
        $injector->define('Atreyu\Test\RequiresInterface', $definition);
        $this->assertInstanceOf('Atreyu\Test\RequiresInterface', $injector->make('Atreyu\Test\RequiresInterface'));
    }

    public function testShareStoresSharedInstanceAndReturnsCurrentInstance()
    {
        $injector = new Injector;
        $testShare = new \StdClass;
        $testShare->test = 42;

        $this->assertInstanceOf('Atreyu\Injector', $injector->share($testShare));
        $testShare->test = 'test';
        $this->assertEquals('test', $injector->make('stdclass')->test);
    }

    public function testShareMarksClassSharedOnNullObjectParameter()
    {
        $injector = new Injector;
        $this->assertInstanceOf('Atreyu\Injector', $injector->share('SomeClass'));
    }

    /**
     * @expectedException \Atreyu\ConfigException
     */
    public function testShareThrowsExceptionOnInvalidArgument()
    {
        $injector = new Injector;
        $injector->share(42);
    }

    public function testAliasAssignsValueAndReturnsCurrentInstance()
    {
        $injector = new Injector;
        $this->assertInstanceOf('Atreyu\Injector', $injector->alias('DepInterface', 'Atreyu\Test\DepImplementation'));
    }

    public function provideInvalidDelegates()
    {
        return [
            [new \StdClass],
            [42],
            [true]
        ];
    }

    /**
     * @dataProvider provideInvalidDelegates
     * @expectedException \Atreyu\ConfigException
     */
    public function testDelegateThrowsExceptionIfDelegateIsNotCallableOrString($badDelegate)
    {
        $injector = new Injector;
        $injector->delegate('Atreyu\Test\TestDependency', $badDelegate);
    }

    public function testDelegateInstantiatesCallableClassString()
    {
        $injector = new Injector;
        $injector->delegate('Atreyu\Test\MadeByDelegate', 'Atreyu\Test\CallableDelegateClassTest');
        $this->assertInstanceof('Atreyu\Test\MadeByDelegate', $injector->make('Atreyu\Test\MadeByDelegate'));
    }

    public function testDelegateInstantiatesCallableClassArray()
    {
        $injector = new Injector;
        $injector->delegate('Atreyu\Test\MadeByDelegate', ['Atreyu\Test\CallableDelegateClassTest', '__invoke']);
        $this->assertInstanceof('Atreyu\Test\MadeByDelegate', $injector->make('Atreyu\Test\MadeByDelegate'));
    }

    public function testUnknownDelegationFunction()
    {
        $injector = new Injector;
        try {
            $injector->delegate('Atreyu\Test\DelegatableInterface', 'FunctionWhichDoesNotExist');
            $this->fail("Delegation was supposed to fail.");
        } catch (InjectorException $ie) {
            $this->assertContains('FunctionWhichDoesNotExist', $ie->getMessage());
            $this->assertEquals(Injector::E_DELEGATE_ARGUMENT, $ie->getCode());
        }
    }

    public function testUnknownDelegationMethod()
    {
        $injector = new Injector;
        try {
            $injector->delegate('Atreyu\Test\DelegatableInterface', ['stdClass', 'methodWhichDoesNotExist']);
            $this->fail("Delegation was supposed to fail.");
        } catch (InjectorException $ie) {
            $this->assertContains('stdClass', $ie->getMessage());
            $this->assertContains('methodWhichDoesNotExist', $ie->getMessage());
            $this->assertEquals(Injector::E_DELEGATE_ARGUMENT, $ie->getCode());
        }
    }

    /**
     * @dataProvider provideExecutionExpectations
     */
    public function testProvisionedInvokables($toInvoke, $definition, $expectedResult)
    {
        $injector = new Injector;
        $this->assertEquals($expectedResult, $injector->execute($toInvoke, $definition));
    }

    public function provideExecutionExpectations()
    {
        $return = [];

        // 0 -------------------------------------------------------------------------------------->

        $toInvoke = ['Atreyu\Test\ExecuteClassNoDeps', 'execute'];
        $args = [];
        $expectedResult = 42;
        $return[] = [$toInvoke, $args, $expectedResult];

        // 1 -------------------------------------------------------------------------------------->

        $toInvoke = [new ExecuteClassNoDeps, 'execute'];
        $args = [];
        $expectedResult = 42;
        $return[] = [$toInvoke, $args, $expectedResult];

        // 2 -------------------------------------------------------------------------------------->

        $toInvoke = ['Atreyu\Test\ExecuteClassDeps', 'execute'];
        $args = [];
        $expectedResult = 42;
        $return[] = [$toInvoke, $args, $expectedResult];

        // 3 -------------------------------------------------------------------------------------->

        $toInvoke = [new ExecuteClassDeps(new TestDependency), 'execute'];
        $args = [];
        $expectedResult = 42;
        $return[] = [$toInvoke, $args, $expectedResult];

        // 4 -------------------------------------------------------------------------------------->

        $toInvoke = ['Atreyu\Test\ExecuteClassDepsWithMethodDeps', 'execute'];
        $args = [':arg' => 9382];
        $expectedResult = 9382;
        $return[] = [$toInvoke, $args, $expectedResult];

        // 5 -------------------------------------------------------------------------------------->

        $toInvoke = ['Atreyu\Test\ExecuteClassStaticMethod', 'execute'];
        $args = [];
        $expectedResult = 42;
        $return[] = [$toInvoke, $args, $expectedResult];

        // 6 -------------------------------------------------------------------------------------->

        $toInvoke = [new ExecuteClassStaticMethod, 'execute'];
        $args = [];
        $expectedResult = 42;
        $return[] = [$toInvoke, $args, $expectedResult];

        // 7 -------------------------------------------------------------------------------------->

        $toInvoke = 'Atreyu\Test\ExecuteClassStaticMethod::execute';
        $args = [];
        $expectedResult = 42;
        $return[] = [$toInvoke, $args, $expectedResult];

        // 8 -------------------------------------------------------------------------------------->

        $toInvoke = ['Atreyu\Test\ExecuteClassRelativeStaticMethod', 'parent::execute'];
        $args = [];
        $expectedResult = 42;
        $return[] = [$toInvoke, $args, $expectedResult];

        // 9 -------------------------------------------------------------------------------------->

        $toInvoke = 'Atreyu\Test\testExecuteFunction';
        $args = [];
        $expectedResult = 42;
        $return[] = [$toInvoke, $args, $expectedResult];

        // 10 ------------------------------------------------------------------------------------->

        $toInvoke = function () { return 42; };
        $args = [];
        $expectedResult = 42;
        $return[] = [$toInvoke, $args, $expectedResult];

        // 11 ------------------------------------------------------------------------------------->

        $toInvoke = new ExecuteClassInvokable;
        $args = [];
        $expectedResult = 42;
        $return[] = [$toInvoke, $args, $expectedResult];

        // 12 ------------------------------------------------------------------------------------->

        $toInvoke = 'Atreyu\Test\ExecuteClassInvokable';
        $args = [];
        $expectedResult = 42;
        $return[] = [$toInvoke, $args, $expectedResult];

        // 13 ------------------------------------------------------------------------------------->

        $toInvoke = 'Atreyu\Test\ExecuteClassNoDeps::execute';
        $args = [];
        $expectedResult = 42;
        $return[] = [$toInvoke, $args, $expectedResult];

        // 14 ------------------------------------------------------------------------------------->

        $toInvoke = 'Atreyu\Test\ExecuteClassDeps::execute';
        $args = [];
        $expectedResult = 42;
        $return[] = [$toInvoke, $args, $expectedResult];

        // 15 ------------------------------------------------------------------------------------->

        $toInvoke = 'Atreyu\Test\ExecuteClassStaticMethod::execute';
        $args = [];
        $expectedResult = 42;
        $return[] = [$toInvoke, $args, $expectedResult];

        // 16 ------------------------------------------------------------------------------------->

        $toInvoke = 'Atreyu\Test\ExecuteClassRelativeStaticMethod::parent::execute';
        $args = [];
        $expectedResult = 42;
        $return[] = [$toInvoke, $args, $expectedResult];

        // 17 ------------------------------------------------------------------------------------->

        $toInvoke = 'Atreyu\Test\testExecuteFunctionWithArg';
        $args = [];
        $expectedResult = 42;
        $return[] = [$toInvoke, $args, $expectedResult];

        // 18 ------------------------------------------------------------------------------------->

        $toInvoke = function () {
            return 42;
        };
        $args = [];
        $expectedResult = 42;
        $return[] = [$toInvoke, $args, $expectedResult];


        if (PHP_VERSION_ID > 50400) {
            // 19 ------------------------------------------------------------------------------------->

            $object = new \Atreyu\Test\ReturnsCallable('new value');
            $args = [];
            $toInvoke = $object->getCallable();
            $expectedResult = 'new value';
            $return[] = [$toInvoke, $args, $expectedResult];
        }
        // x -------------------------------------------------------------------------------------->

        return $return;
    }

    public function testStaticStringInvokableWithArgument()
    {
        $injector = new Injector;
        $invokable = $injector->buildExecutable('Atreyu\Test\ClassWithStaticMethodThatTakesArg::doSomething');
        $this->assertEquals(42, $invokable(41));
    }

    public function testInterfaceFactoryDelegation()
    {
        $injector = new Injector;
        $injector->delegate('Atreyu\Test\DelegatableInterface', 'Atreyu\Test\ImplementsInterfaceFactory');
        $requiresDelegatedInterface = $injector->make('Atreyu\Test\RequiresDelegatedInterface');
        $requiresDelegatedInterface->foo();
    }

    /**
     * @expectedException \Atreyu\InjectorException
     */
    public function testMissingAlias()
    {
        $injector = new Injector;
        $testClass = $injector->make('Atreyu\Test\TestMissingDependency');
    }

    public function testAliasingConcreteClasses()
    {
        $injector = new Injector;
        $injector->alias('Atreyu\Test\ConcreteClass1', 'Atreyu\Test\ConcreteClass2');
        $obj = $injector->make('Atreyu\Test\ConcreteClass1');
        $this->assertInstanceOf('Atreyu\Test\ConcreteClass2', $obj);
    }

    public function testSharedByAliasedInterfaceName()
    {
        $injector = new Injector;
        $injector->alias('Atreyu\Test\SharedAliasedInterface', 'Atreyu\Test\SharedClass');
        $injector->share('Atreyu\Test\SharedAliasedInterface');
        $class = $injector->make('Atreyu\Test\SharedAliasedInterface');
        $class2 = $injector->make('Atreyu\Test\SharedAliasedInterface');
        $this->assertSame($class, $class2);
    }

    public function testNotSharedByAliasedInterfaceName()
    {
        $injector = new Injector;
        $injector->alias('Atreyu\Test\SharedAliasedInterface', 'Atreyu\Test\SharedClass');
        $injector->alias('Atreyu\Test\SharedAliasedInterface', 'Atreyu\Test\NotSharedClass');
        $injector->share('Atreyu\Test\SharedClass');
        $class = $injector->make('Atreyu\Test\SharedAliasedInterface');
        $class2 = $injector->make('Atreyu\Test\SharedAliasedInterface');

        $this->assertNotSame($class, $class2);
    }

    public function testSharedByAliasedInterfaceNameReversedOrder()
    {
        $injector = new Injector;
        $injector->share('Atreyu\Test\SharedAliasedInterface');
        $injector->alias('Atreyu\Test\SharedAliasedInterface', 'Atreyu\Test\SharedClass');
        $class = $injector->make('Atreyu\Test\SharedAliasedInterface');
        $class2 = $injector->make('Atreyu\Test\SharedAliasedInterface');
        $this->assertSame($class, $class2);
    }

    public function testSharedByAliasedInterfaceNameWithParameter()
    {
        $injector = new Injector;
        $injector->alias('Atreyu\Test\SharedAliasedInterface', 'Atreyu\Test\SharedClass');
        $injector->share('Atreyu\Test\SharedAliasedInterface');
        $sharedClass = $injector->make('Atreyu\Test\SharedAliasedInterface');
        $childClass = $injector->make('Atreyu\Test\ClassWithAliasAsParameter');
        $this->assertSame($sharedClass, $childClass->sharedClass);
    }

    public function testSharedByAliasedInstance()
    {
        $injector = new Injector;
        $injector->alias('Atreyu\Test\SharedAliasedInterface', 'Atreyu\Test\SharedClass');
        $sharedClass = $injector->make('Atreyu\Test\SharedAliasedInterface');
        $injector->share($sharedClass);
        $childClass = $injector->make('Atreyu\Test\ClassWithAliasAsParameter');
        $this->assertSame($sharedClass, $childClass->sharedClass);
    }

    public function testMultipleShareCallsDontOverrideTheOriginalSharedInstance()
    {
        $injector = new Injector;
        $injector->share('StdClass');
        $stdClass1 = $injector->make('StdClass');
        $injector->share('StdClass');
        $stdClass2 = $injector->make('StdClass');
        $this->assertSame($stdClass1, $stdClass2);
    }

    public function testDependencyWhereSharedWithProtectedConstructor()
    {
        $injector = new Injector;

        $inner = TestDependencyWithProtectedConstructor::create();
        $injector->share($inner);

        $outer = $injector->make('Atreyu\Test\TestNeedsDepWithProtCons');

        $this->assertSame($inner, $outer->dep);
    }

    public function testDependencyWhereShared()
    {
        $injector = new Injector;
        $injector->share('Atreyu\Test\ClassInnerB');
        $innerDep = $injector->make('Atreyu\Test\ClassInnerB');
        $inner = $injector->make('Atreyu\Test\ClassInnerA');
        $this->assertSame($innerDep, $inner->dep);
        $outer = $injector->make('Atreyu\Test\ClassOuter');
        $this->assertSame($innerDep, $outer->dep->dep);
    }

    public function testBugWithReflectionPoolIncorrectlyReturningBadInfo()
    {
        $injector = new Injector;
        $obj = $injector->make('Atreyu\Test\ClassOuter');
        $this->assertInstanceOf('Atreyu\Test\ClassOuter', $obj);
        $this->assertInstanceOf('Atreyu\Test\ClassInnerA', $obj->dep);
        $this->assertInstanceOf('Atreyu\Test\ClassInnerB', $obj->dep->dep);
    }

    public function provideCyclicDependencies()
    {
        return [
            'Atreyu\Test\RecursiveClassA' => ['Atreyu\Test\RecursiveClassA'],
            'Atreyu\Test\RecursiveClassB' => ['Atreyu\Test\RecursiveClassB'],
            'Atreyu\Test\RecursiveClassC' => ['Atreyu\Test\RecursiveClassC'],
            'Atreyu\Test\RecursiveClass1' => ['Atreyu\Test\RecursiveClass1'],
            'Atreyu\Test\RecursiveClass2' => ['Atreyu\Test\RecursiveClass2'],
            'Atreyu\Test\DependsOnCyclic' => ['Atreyu\Test\DependsOnCyclic'],
        ];
    }

     /**
     * @dataProvider provideCyclicDependencies
     * @expectedException \Atreyu\InjectionException
     * @expectedExceptionCode \Atreyu\Injector::E_CYCLIC_DEPENDENCY
     */
    public function testCyclicDependencies($class)
    {
        $injector = new Injector;
        $injector->make($class);
    }

    public function testNonConcreteDependencyWithDefault()
    {
        $injector = new Injector;
        $class = $injector->make('Atreyu\Test\NonConcreteDependencyWithDefaultValue');
        $this->assertInstanceOf('Atreyu\Test\NonConcreteDependencyWithDefaultValue', $class);
        $this->assertNull($class->interface);
    }

    public function testNonConcreteDependencyWithDefaultValueThroughAlias()
    {
        $injector = new Injector;
        $injector->alias(
            'Atreyu\Test\DelegatableInterface',
            'Atreyu\Test\ImplementsInterface'
        );
        $class = $injector->make('Atreyu\Test\NonConcreteDependencyWithDefaultValue');
        $this->assertInstanceOf('Atreyu\Test\NonConcreteDependencyWithDefaultValue', $class);
        $this->assertInstanceOf('Atreyu\Test\ImplementsInterface', $class->interface);
    }

    public function testNonConcreteDependencyWithDefaultValueThroughDelegation()
    {
        $injector = new Injector;
        $injector->delegate('Atreyu\Test\DelegatableInterface', 'Atreyu\Test\ImplementsInterfaceFactory');
        $class = $injector->make('Atreyu\Test\NonConcreteDependencyWithDefaultValue');
        $this->assertInstanceOf('Atreyu\Test\NonConcreteDependencyWithDefaultValue', $class);
        $this->assertInstanceOf('Atreyu\Test\ImplementsInterface', $class->interface);
    }

    public function testDependencyWithDefaultValueThroughShare()
    {
        $injector = new Injector;
        //Instance is not shared, null default is used for dependency
        $instance = $injector->make('Atreyu\Test\ConcreteDependencyWithDefaultValue');
        $this->assertNull($instance->dependency);

        //Instance is explicitly shared, $instance is used for dependency
        $instance = new \StdClass();
        $injector->share($instance);
        $instance = $injector->make('Atreyu\Test\ConcreteDependencyWithDefaultValue');
        $this->assertInstanceOf('StdClass', $instance->dependency);
    }

    /**
     * @expectedException \Atreyu\ConfigException
     * @expectedExceptionCode \Atreyu\Injector::E_ALIASED_CANNOT_SHARE
     */
    public function testShareAfterAliasException()
    {
        $injector = new Injector();
        $testClass = new \StdClass();
        $injector->alias('StdClass', 'Atreyu\Test\SomeOtherClass');
        $injector->share($testClass);
    }

    public function testShareAfterAliasAliasedClassAllowed()
    {
        $injector = new Injector();
        $testClass = new DepImplementation();
        $injector->alias('Atreyu\Test\DepInterface', 'Atreyu\Test\DepImplementation');
        $injector->share($testClass);
        $obj = $injector->make('Atreyu\Test\DepInterface');
        $this->assertInstanceOf('Atreyu\Test\DepImplementation', $obj);
    }

    public function testAliasAfterShareByStringAllowed()
    {
        $injector = new Injector();
        $injector->share('Atreyu\Test\DepInterface');
        $injector->alias('Atreyu\Test\DepInterface', 'Atreyu\Test\DepImplementation');
        $obj = $injector->make('Atreyu\Test\DepInterface');
        $obj2 = $injector->make('Atreyu\Test\DepInterface');
        $this->assertInstanceOf('Atreyu\Test\DepImplementation', $obj);
        $this->assertEquals($obj, $obj2);
    }

    public function testAliasAfterShareBySharingAliasAllowed()
    {
        $injector = new Injector();
        $injector->share('Atreyu\Test\DepImplementation');
        $injector->alias('Atreyu\Test\DepInterface', 'Atreyu\Test\DepImplementation');
        $obj = $injector->make('Atreyu\Test\DepInterface');
        $obj2 = $injector->make('Atreyu\Test\DepInterface');
        $this->assertInstanceOf('Atreyu\Test\DepImplementation', $obj);
        $this->assertEquals($obj, $obj2);
    }

    /**
     * @expectedException \Atreyu\ConfigException
     * @expectedExceptionCode \Atreyu\Injector::E_SHARED_CANNOT_ALIAS
     */
    public function testAliasAfterShareException()
    {
        $injector = new Injector();
        $testClass = new \StdClass();
        $injector->share($testClass);
        $injector->alias('StdClass', 'Atreyu\Test\SomeOtherClass');
    }

    /**
     * @expectedException \Atreyu\InjectionException
     * @expectedExceptionCode \Atreyu\Injector::E_NON_PUBLIC_CONSTRUCTOR
     */
    public function testAppropriateExceptionThrownOnNonPublicConstructor()
    {
        $injector = new Injector();
        $injector->make('Atreyu\Test\HasNonPublicConstructor');
    }

    /**
     * @expectedException \Atreyu\InjectionException
     * @expectedExceptionCode \Atreyu\Injector::E_NON_PUBLIC_CONSTRUCTOR
     */
    public function testAppropriateExceptionThrownOnNonPublicConstructorWithArgs()
    {
        $injector = new Injector();
        $injector->make('Atreyu\Test\HasNonPublicConstructorWithArgs');
    }

    public function testMakeExecutableFailsOnNonExistentFunction()
    {
        $injector = new Injector();
        $this->setExpectedException(
            'Atreyu\InjectionException',
            'nonExistentFunction',
            Injector::E_INVOKABLE
        );
        $injector->buildExecutable('nonExistentFunction');
    }

    public function testMakeExecutableFailsOnNonExistentInstanceMethod()
    {
        $injector = new Injector();
        $object = new \StdClass();
        $this->setExpectedException(
            'Atreyu\InjectionException',
            "[object(stdClass), 'nonExistentMethod']",
            Injector::E_INVOKABLE
        );
        $injector->buildExecutable([$object, 'nonExistentMethod']);
    }

    public function testMakeExecutableFailsOnNonExistentStaticMethod()
    {
        $injector = new Injector();
        $this->setExpectedException(
            'Atreyu\InjectionException',
            "StdClass::nonExistentMethod",
            Injector::E_INVOKABLE
        );
        $injector->buildExecutable(['StdClass', 'nonExistentMethod']);
    }

    /**
     * @expectedException \Atreyu\InjectionException
     * @expectedExceptionCode \Atreyu\Injector::E_INVOKABLE
     */
    public function testMakeExecutableFailsOnClassWithoutInvoke()
    {
        $injector = new Injector();
        $object = new \StdClass();
        $injector->buildExecutable($object);
    }

    /**
     * @expectedException \Atreyu\ConfigException
     * @expectedExceptionCode \Atreyu\Injector::E_NON_EMPTY_STRING_ALIAS
     */
    public function testBadAlias()
    {
        $injector = new Injector();
        $injector->share('Atreyu\Test\DepInterface');
        $injector->alias('Atreyu\Test\DepInterface', '');
    }

    public function testShareNewAlias()
    {
        $injector = new Injector();
        $injector->share('Atreyu\Test\DepImplementation');
        $injector->alias('Atreyu\Test\DepInterface', 'Atreyu\Test\DepImplementation');
    }

    public function testDefineWithBackslashAndMakeWithoutBackslash()
    {
        $injector = new Injector();
        $injector->define('Atreyu\Test\SimpleNoTypehintClass', [':arg' => 'tested']);
        $testClass = $injector->make('Atreyu\Test\SimpleNoTypehintClass');
        $this->assertEquals('tested', $testClass->testParam);
    }

    public function testShareWithBackslashAndMakeWithoutBackslash()
    {
        $injector = new Injector();
        $injector->share('\StdClass');
        $classA = $injector->make('StdClass');
        $classA->tested = false;
        $classB = $injector->make('\StdClass');
        $classB->tested = true;

        $this->assertEquals($classA->tested, $classB->tested);
    }

    public function testInstanceMutate()
    {
        $injector = new Injector();
        $injector->prepare('\StdClass', function ($obj, $injector) {
            $obj->testval = 42;
        });
        $obj = $injector->make('StdClass');

        $this->assertSame(42, $obj->testval);
    }

    public function testInterfaceMutate()
    {
        $injector = new Injector();
        $injector->prepare('Atreyu\Test\SomeInterface', function ($obj, $injector) {
            $obj->testProp = 42;
        });
        $obj = $injector->make('Atreyu\Test\PreparesImplementationTest');

        $this->assertSame(42, $obj->testProp);
    }

    /**
     * Test that custom definitions are not passed through to dependencies.
     * Surprising things would happen if this did occur.
     * @expectedException \Atreyu\InjectionException
     * @expectedExceptionCode \Atreyu\Injector::E_UNDEFINED_PARAM
     */
    public function testCustomDefinitionNotPassedThrough()
    {
        $injector = new Injector();
        $injector->share('Atreyu\Test\DependencyWithDefinedParam');
        $injector->make('Atreyu\Test\RequiresDependencyWithDefinedParam', [':foo' => 5]);
    }

    public function testDelegationFunction()
    {
        $injector = new Injector();
        $injector->delegate('Atreyu\Test\TestDelegationSimple', 'Atreyu\Test\createTestDelegationSimple');
        $obj = $injector->make('Atreyu\Test\TestDelegationSimple');
        $this->assertInstanceOf('Atreyu\Test\TestDelegationSimple', $obj);
        $this->assertTrue($obj->delegateCalled);
    }

    public function testDelegationDependency()
    {
        $injector = new Injector();
        $injector->delegate(
            'Atreyu\Test\TestDelegationDependency',
            'Atreyu\Test\createTestDelegationDependency'
        );
        $obj = $injector->make('Atreyu\Test\TestDelegationDependency');
        $this->assertInstanceOf('Atreyu\Test\TestDelegationDependency', $obj);
        $this->assertTrue($obj->delegateCalled);
    }

    public function testExecutableAliasing()
    {
        $injector = new Injector();
        $injector->alias('Atreyu\Test\BaseExecutableClass', 'Atreyu\Test\ExtendsExecutableClass');
        $result = $injector->execute(['Atreyu\Test\BaseExecutableClass', 'foo']);
        $this->assertEquals('This is the ExtendsExecutableClass', $result);
    }

    public function testExecutableAliasingStatic()
    {
        $injector = new Injector();
        $injector->alias('Atreyu\Test\BaseExecutableClass', 'Atreyu\Test\ExtendsExecutableClass');
        $result = $injector->execute(['Atreyu\Test\BaseExecutableClass', 'bar']);
        $this->assertEquals('This is the ExtendsExecutableClass', $result);
    }

    /**
     * Test coverage for delegate closures that are defined outside
     * of a class.ph
     * @throws \Atreyu\ConfigException
     */
    public function testDelegateClosure()
    {
        $delegateClosure = \Atreyu\Test\getDelegateClosureInGlobalScope();
        $injector = new Injector();
        $injector->delegate('Atreyu\Test\DelegateClosureInGlobalScope', $delegateClosure);
        $injector->make('Atreyu\Test\DelegateClosureInGlobalScope');
    }

    public function testCloningWithServiceLocator()
    {
        $injector = new Injector();
        $injector->share($injector);
        $instance = $injector->make('Atreyu\Test\CloneTest');
        $newInjector = $instance->injector;
        $newInstance = $newInjector->make('Atreyu\Test\CloneTest');
    }

    public function testAbstractExecute()
    {
        $injector = new Injector();

        $fn = function () {
            return new \Atreyu\Test\ConcreteExexcuteTest();
        };

        $injector->delegate('Atreyu\Test\AbstractExecuteTest', $fn);
        $result = $injector->execute(['Atreyu\Test\AbstractExecuteTest', 'process']);

        $this->assertEquals('Concrete', $result);
    }

    public function testDebugMake()
    {
        $injector = new Injector();
        try {
            $injector->make('Atreyu\Test\DependencyChainTest');
        } catch (\Atreyu\InjectionException $ie) {
            $chain = $ie->getDependencyChain();
            $this->assertCount(2, $chain);

            $this->assertEquals('atreyu\test\dependencychaintest', $chain[0]);
            $this->assertEquals('atreyu\test\depinterface', $chain[1]);
        }
    }

    public function testInspectShares()
    {
        $injector = new Injector();
        $injector->share('Atreyu\Test\SomeClassName');

        $inspection = $injector->inspect('Atreyu\Test\SomeClassName', Injector::I_SHARES);
        $this->assertArrayHasKey('atreyu\test\someclassname', $inspection[Injector::I_SHARES]);
    }

    /**
     * @expectedException \Atreyu\InjectionException
     * @expectedExceptionCode \Atreyu\Injector::E_MAKING_FAILED
     */
    public function testDelegationDoesntMakeObject()
    {
        $delegate = function () {
            return null;
        };
        $injector = new Injector();
        $injector->delegate('Atreyu\Test\SomeClassName', $delegate);
        $injector->make('Atreyu\Test\SomeClassName');
    }

    /**
     * @expectedException \Atreyu\InjectionException
     * @expectedExceptionCode \Atreyu\Injector::E_MAKING_FAILED
     */
    public function testDelegationDoesntMakeObjectMakesString()
    {
        $delegate = function () {
            return 'ThisIsNotAClass';
        };
        $injector = new Injector();
        $injector->delegate('Atreyu\Test\SomeClassName', $delegate);
        $injector->make('Atreyu\Test\SomeClassName');
    }

    public function testPrepareCallableReplacesObjectWithReturnValueOfSameInterfaceType()
    {
        $injector = new Injector;
        $expected = new SomeImplementation; // <-- implements SomeInterface
        $injector->prepare("Atreyu\Test\SomeInterface", function ($impl) use ($expected) {
            return $expected;
        });
        $actual = $injector->make("Atreyu\Test\SomeImplementation");
        $this->assertSame($expected, $actual);
    }

    public function testPrepareCallableReplacesObjectWithReturnValueOfSameClassType()
    {
        $injector = new Injector;
        $expected = new SomeImplementation; // <-- implements SomeInterface
        $injector->prepare("Atreyu\Test\SomeImplementation", function ($impl) use ($expected) {
            return $expected;
        });
        $actual = $injector->make("Atreyu\Test\SomeImplementation");
        $this->assertSame($expected, $actual);
    }

    public function testChildWithoutConstructorWorks() {

        $injector = new Injector;
        try {
            $injector->define('Atreyu\Test\ParentWithConstructor', [':foo' => 'parent']);
            $injector->define('Atreyu\Test\ChildWithoutConstructor', [':foo' => 'child']);
            $injector->share('Atreyu\Test\ParentWithConstructor');
            $injector->share('Atreyu\Test\ChildWithoutConstructor');

            $child = $injector->make('Atreyu\Test\ChildWithoutConstructor');
            $this->assertEquals('child', $child->foo);

            $parent = $injector->make('Atreyu\Test\ParentWithConstructor');
            $this->assertEquals('parent', $parent->foo);
        }
        catch (\Atreyu\InjectionException $ie) {
            echo $ie->getMessage();
            $this->fail("Atreyu failed to locate the ");
        }
    }
    
    /**
     * @expectedException \Atreyu\InjectionException
     * @expectedExceptionCode \Atreyu\Injector::E_UNDEFINED_PARAM
     */
    public function testChildWithoutConstructorMissingParam() {
        $injector = new Injector;
        $injector->define('Atreyu\Test\ParentWithConstructor', [':foo' => 'parent']);
        $injector->make('Atreyu\Test\ChildWithoutConstructor');
    }
}

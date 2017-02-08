## Atreyu

### Todo
- nothing

### Changelog [1.6.0] – 08/02/2017  
- fast forwarded 5 commits Auryn:master > Atreyu:master

- Add example for [#78](https://github.com/rdlowrey/auryn/issues/78) [54b2fed](https://github.com/cmpayments/atreyu/commit/54b2fedff1c760879cfe3ab1baea9dcb7239f07e)
- Merge pull request [#150](https://github.com/rdlowrey/auryn/pull/150) from kelunik/phpstorm-meta [a61e78c](https://github.com/cmpayments/atreyu/commit/a61e78cc3f964b0ab5d170aae19f54af5609e9dc)
- Merge pull request [#137](https://github.com/rdlowrey/auryn/pull/137) from Danack/prepare_exception [924d0cd](https://github.com/cmpayments/atreyu/commit/924d0cdf80f227c91fc87a5212ed4677b22e7fb8)
- Add example to delegate parameters[3f65078](https://github.com/cmpayments/atreyu/commit/3f6507851e6197eec6c1ce87de35b0a92dfa10be)
- Add PHPStorm hints [5bce335](https://github.com/cmpayments/atreyu/commit/5bce335ea7eda86dc98f4369af81077f5cde738a)

### Changelog [1.5.0] – 08/02/2017  
Injector:

- added method hasShare($nameOrInstance) functionality, it is now possible to request if a class name (string) or instance has previously been shared with the container

Tests:

- Added tests accordingly

### Changelog [1.4.1] – 16/12/2016  
Injector:

- Fixed bug for error message; Atreyu\InjectionException: <className>: Class mixed does not exist
- minor indentation issue in file fixtures.php

Tests:

- Added tests accordingly

### Changelog [1.4.0] – 02/12/2016   
Injector:

- when named scalar variables are given as the second argument of the make() method it would cause the Injector to ignore the named parameter due to overcomplicating in method Injector::provisionFuncArgs(). Method has been drastically simplified as a result
- Fixed known issue ‘when more than two (or more) function arguments are type hinted which are defined by the same alias’ due to the optimisation of method Injector::provisionFuncArgs()
- when both named and unnamed scalar variables are given as the second argument of the make() method it would cause the Injector to fault. The thing here was the unnamed parameters should be provisioned in the correct method parameter order. This has been fixed (this was especially a problem when less scalar type definitions than method arguments were provided)
- Injector would fault when scalar types were provided where a method argument was nullable or a constant was defined as argument default

Tests:

- added tests with different definition ordering than the method definition itself, different default values for method parameters (string, null or a constant) and the use of mixed named and unnamed definitions (please have a look at the newly added tests)
- added new tests for the above-mentioned use case described
- added a new fixture for the above-mentioned tests

### Changelog [1.3.0] – 29/11/2016   
Injector:

- fixed: when scalar types are given as the second argument of the make() method they are provisioned in the same order as they were supplied. This is an issue in cases where more function arguments need to be provisioned than the actual number argument (scalar type) provided.

Tests:

- fixed a repeated mistake where the two mandatory values of method assertEquals() were switched around. (this would only be noticeable in case of a assertEqual mismatch.
- added new tests for the above-mentioned use case described
- added a new fixture for the above-mentioned tests

### Changelog [1.2.1] – 25/11/2016   
Injector:

- the previous added method getReflector() on class Injector is not really in line with the DIC philosophy, therefor the Reflector class is now available via the make() method and method getClass is removed. Since no one is using version v1.2 I do not consider this to be a backwards incompatible change.

### Changelog [1.2.0] – 24/11/2016   
Injector:

- added method getReflector() because doing inspection by reflection is expensive. It is now possible to get this kind of information from the Reflector cache.

### Changelog [1.1.0] – 24/11/2016   
- *improved the code quality (scrutinizer) from 6.94 (satisfactory) to 7.13 (good)*
- package fabpot/php-cs-fixer is abandoned, started using friendsofphp/php-cs-fixer instead. Please note release v2.0 is currently out as a RC
- as of - I don’t know when - Travis in combination with PHP7.0 uses PHPUnit 5.6.5 by default. This is not what should be happening. The composered version of PHPUnit should be used.

Injector

- cleaned up code in method 'provisionFuncArgs()’ for supporting unordered arguments for $injector->make() method
- moved and refactored Doc Block type hinting code to the Reflector classes because that’s where it actually belongs (next to the method argument reflection inspection)
- improved the Doc Block type hinting so that aliased Interfaces get handled correctly
- fixed bug in Doc Block type hinting because it is possible for Doc Block annotations to contain multiple type hints (where type hinting can only have one type hint)
- fixed bug where previously defined parameters would not work in Doc Block type hinting context
- modified the Reflector Interface method 'getParamTypeHint()’ in order to support the issue where ‘Previously defined parameters would not work in Doc Block type hinting context'

Tests

- rewrote test 'testMakeInstanceBasedOnTypeHintingWithArgumentDefinition()’
- rewrote test 'testMakeInstanceBasedOnTypeHintingWithAliasDefinition()'
- added new tests for the undermentioned improvements
- rewrite some fixtures
- code improvements in terms of; removed/add new lines, properly refactored PHPUnit annotations versus code namespaces usages

### changelog [1.0] – 24/03/2016
Injector:

- forked from https://github.com/cmpayments/atreyu/commit/2fdd89dfbbb9a98de7361981df6d802a9a38e0a5
- removed PHP 5.3 dependency
- renamed namespace from 'Auryn' to 'Atreyu'
- updated README.md with differences between 'Auryn' and 'Atreyu'
- added this CHANGELOG.md

# Weaver


An experiment in compositional programming.  Inspired by https://github.com/Ocramius/ProxyManager and https://github.com/ejsmont-artur/phpProxyBuilder

    
[![Build Status](https://travis-ci.org/Danack/Weaver.svg?branch=master)](https://travis-ci.org/Danack/Weaver)


## Why?


The two projects listed above are both good ideas, but have serious limitations. The phpProxyBuilder is does not generate correct type-hinting information, which completely stops me from being able to use it. 

The ProxyManager is nice, but has issues with how it interacts with other code and seems to make debugging code incredibly hard. I also couldn't see how to implement a caching proxy.

This project is an attempt to allow generating various types of decorated versions of classes with a goal of:

* Great flexibility on how they're used.

* Retaining ability to debug code.

* Low overhead both mentally and by code weight.

* Keep type-hinting intact to allow [Auryn DI](https://github.com/rdlowrey/Auryn) to work correctly.


## Example

We have a class and we want to be able to time the calls to 'executeQuery'

```

class TestClass {

    function __construct($statement, $createLine){
        $this->statement = $statement;
        $this->createLine = $createLine;
    }

    function executeQuery($queryString, $foo2) {
        echo "executing query!";
        
        return 5;
    }
}

```


Weaved with a 'class' to that holds a timer:

```
<?php


namespace Weaver\Weave;

use Intahwebz\Timer;


class TimerProxy {

    private $timer;

    function __construct(Timer $timer) {
        $this->timer = $timer;
    }

    function reportTimings() {
        $this->timer->dumpTime();
    }
}

```

And with a tiny bit of glue to bind the two:

```
$timerWeaving = array(
    'executeQuery' => array(
        '$this->timer->startTimer($queryString);', 
        '$this->timer->stopTimer();'
    ),
);
```



Produces a decorated class:


```
<?php
namespace Example;

use Intahwebz\Timer;

class TimerProxyXTestClass extends \Example\TestClass
{

    private $timer = null;

    public function executeQuery($queryString, $foo2)
    {
        $this->timer->startTimer($queryString);
        $result = parent::executeQuery($queryString, $foo2);
        $this->timer->stopTimer();

        return $result;
    }

    public function reportTimings()
    {
        $this->timer->dumpTime();
    }

    public function __construct($statement, $createLine, \Intahwebz\Timer $timer)
    {
        parent::__construct($statement, $createLine);
                $this->timer = $timer;
    }

}

```


Because I use a real DI, I can now change my config to include:

```
$injector->alias(TestClass::class, TimerProxyXTestClass::class);
```

And the Proxied version of the class with the timer attached will be used everywhere that the original class was used.



## TODO


* Figure out what to do about factories, because having to make a new factory for every combination of thing sucks. e.g. CachedTimedStatementWrapperFactory to make a cached, timed, statementWrapper factory.



* Write about difference between this and monkey patching http://en.wikipedia.org/wiki/Monkey_patch Short version Monkey patching runtime only, impossible to debug, not much type safety.


## Terms

* Source class - the original class that needs to have it's behaviour modified.

* Decorator class - the class that will be used to decorate the source class.

* Decorated class - the result of the weaving.


## Notes





List of examples that I should implement from Ocramius/ProxyManager

Lazy Loading Value Holders (Virtual Proxy)
Access Interceptor Value Holder
Null Objects
Ghost Objects - for lazy loading
Lazy References - wat
Remote Object


Protection Proxy
class APIProtectionProxy extends API {
    protected $count = 0;
    public function __construct(API $api, $limit) {
        $this->api = $api; $this->limit = $limit;
    }

    public function doStuff() {
        $this->count();
        return $this->api->doStuff();
    }

    private function count() {
        if (++$this->count > $this->limit) {
            throw new RemoteApiLimit('STAHP!');
        }
    }
}
<?php


require_once "bootstrap.php";


\Example\ClosureFactory::load();





$provider = createProvider();

$provider->delegate('\Example\TestClassFactory', 'createCacheProxyXTimerProxyXTestClassFactory');

$testClassFactory = $provider->make('\Example\TestClassFactory');

$query = $testClassFactory->create("select * from foo;");

//First call is obviously not in cache
$query->executeQuery(array());

//Second call should hit cache
$query->executeQuery(array());


//This function was not proxied, so is not affected.
$query->anotherFunction("apples");

$timer = $provider->make('Intahwebz\Timer');

$timer->dumpTime();


//Output will look something like:
//
//executing query: select * from foo;
//Result was not in cache
//Result is in cache.
//Executing anotherFunction
//Timer results
//"select * from foo;", called 1 time, total time:0.000449

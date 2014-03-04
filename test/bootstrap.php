<?php


use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FingersCrossedHandler;
use Monolog\Handler\FingersCrossed\ErrorLevelActivationStrategy;
use Auryn\Provider;

error_reporting(E_ALL);

$autoloader = require_once(realpath(dirname(__FILE__).'/../vendor/autoload.php'));


$autoloader->add(
    'Example', 
    array(
        realpath('./').'/generated/',
        realpath('./').'/test/'
    )
);

//apc_store('foo12345', 'BAR');
//if (apc_fetch('foo12345') == false) {
//    echo "APC appears to be failing.";
//    exit(0);
//}

//require_once('../vendor/autoload.php');

function createStandardLogger($logChannelName = 'logChannelName') {
    $logger = new Logger($logChannelName);
    $pid = getmypid();

    $standardFormat = "[%datetime%] $pid %channel%.%level_name%: %message% %context% %extra%\n";
    $formatter = new \Monolog\Formatter\LineFormatter($standardFormat);

    $streamInfoHandler = new StreamHandler('./var/log/mono.log', Logger::INFO);
    $streamInfoHandler->setFormatter($formatter);
    //wrap the stream handler with a fingersCrossedHandler.
    $fingersCrossedHandler = new FingersCrossedHandler(
        $streamInfoHandler,
        new ErrorLevelActivationStrategy(Logger::WARNING),
        $bufferSize = 0,
        $bubble = true,
        $stopBuffering = true
    );

    $logger->pushHandler($fingersCrossedHandler);    //Push the handler to the logger.

    return $logger;
}



function createProvider($implementations = array(), $shareClasses = array()) {

    \Intahwebz\Functions::load();
    $provider = new Provider();

    $standardLogger = createStandardLogger();
    
    $standardImplementations = [
        'Intahwebz\ObjectCache' => 'Intahwebz\Cache\APCObjectCache',
        'Psr\Log\LoggerInterface' => $standardLogger
    ];

    $standardShares = [
        'Intahwebz\Timer' => 'Intahwebz\Timer',
        'Monolog\Logger' => $standardLogger,
    ];

    foreach ($standardImplementations as $interface => $implementation) {
        if (array_key_exists($interface, $implementations)) {
            if (is_object($implementations[$interface]) == true) {
                $provider->alias($interface, get_class($implementations[$interface]));
                $provider->share($implementations[$interface]);
            }
            else {
                $provider->alias($interface, $implementations[$interface]);
            }
            unset($implementations[$interface]);
        }
        else {
            if (is_object($implementation)) {
                $implementation = get_class($implementation);
            }
            $provider->alias($interface, $implementation);
        }
    }

    foreach ($implementations as $class => $implementation) {
        if (is_object($implementation) == true) {
            $provider->alias($class, get_class($implementation));
            $provider->share($implementation);
        }
        else {
            $provider->alias($class, $implementation);
        }
    }

    foreach ($standardShares as $class => $share) {
        if (array_key_exists($class, $shareClasses)) {
            $provider->share($shareClasses[$class]);
            unset($shareClasses[$class]);
        }
        else {
            $provider->share($share);
        }
    }

    foreach ($shareClasses as $class => $share) {
        $provider->share($share);
    }

    $provider->share($provider); //YOLO

    return $provider;
}

 
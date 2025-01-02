<?php

use Tracy\Debugger;
use Bilbofox\TimerPanel\TimerPanel;

require_once __DIR__ . '/../vendor/autoload.php';

Debugger::enable(Debugger::Development);
$timerPanel = TimerPanel::register();

// Default

$timerPanel->start('foo');
usleep(rand(50,100) * 1000);
$timerPanel->stop();

$timerPanel->start('bar', 'My flowers are beautiful');
usleep(rand(50,100) * 1000);
$timerPanel->stop('bar');

$timerPanel->start();
usleep(rand(50,100) * 1000);
$timerPanel->stop();

$timerPanel->start();
usleep(rand(50,100) * 1000);
$timerPanel->stop();

// Sum

for ($i = 0; $i <= 3; $i++) {
    $timerPanel->start('sum', 'Sum of multiple timers', TimerPanel::MODE_SUM);
    usleep(rand(50,100) * 1000);
    $timerPanel->stop('sum');
}

// Stack

for ($i = 0; $i <= 3; $i++) {
    $timerPanel->start('stack', 'Stack of multiple timers #' . $i, TimerPanel::MODE_STACK);

//    usleep(rand(50,100) * 1000);
    $timerPanel->stop('stack');
}


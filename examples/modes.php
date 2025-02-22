<?php

use Tracy\Debugger;
use Bilbofox\TimerPanel\TimerPanel;

require_once __DIR__ . '/../vendor/autoload.php';

Debugger::enable(Debugger::DEVELOPMENT);
$timerPanel = TimerPanel::register();

// Sum
for ($i = 0; $i <= 3; $i++) {
    // Auto key
    $timerPanel->startSum();
    usleep(rand(50, 100) * 1000);
    $timerPanel->stop();

    // Explicit key
    $timerPanel->startSum('mysum');
    usleep(rand(50, 100) * 1000);
    $timerPanel->stop('mysum');
}

// Stack
for ($i = 0; $i <= 3; $i++) {
    // Auto key
    $timerPanel->startStack();
    usleep(rand(50, 100) * 1000);
    $timerPanel->stop();
}

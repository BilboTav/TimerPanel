<?php

use Tracy\Debugger;
use Bilbofox\TimerPanel\TimerPanel;

require_once __DIR__ . '/../vendor/autoload.php';

Debugger::enable(Debugger::Development);
$timerPanel = TimerPanel::register();

// Sum
for ($i = 0; $i <= 3; $i++) {
    $timerPanel->start('sum', 'Sum of multiple timers', TimerPanel::MODE_SUM);
    usleep(rand(50, 100) * 1000);
    $timerPanel->stop('sum');
}

// Stack
for ($i = 0; $i <= 3; $i++) {
    $_t = $timerPanel->start('stack', 'Stack of multiple timers', TimerPanel::MODE_STACK);
    usleep(rand(50, 100) * 1000);
    $timerPanel->stop($_t);
}

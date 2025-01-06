<?php

use Tracy\Debugger;
use Bilbofox\TimerPanel\TimerPanel;

require_once __DIR__ . '/../vendor/autoload.php';

Debugger::enable(Debugger::Development);
$timerPanel = TimerPanel::register();

// auto key
$timerPanel->start();
usleep(rand(50,100) * 1000);
$timerPanel->stop();

// manual key
$timerPanel->start('mytimer', 'My flowers are beautiful');
usleep(rand(50,100) * 1000);
$timerPanel->stop('mytimer');

// nested
$t = $timerPanel->start();
usleep(rand(50,100) * 1000);

$t = $timerPanel->start('test');
usleep(rand(50,100) * 1000);
$timerPanel->stop($t);

$timerPanel->stop();

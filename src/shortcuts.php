<?php declare(strict_types=1);

use Bilbofox\TimerPanel\TimerPanel;

// Functions shortcuts for public interface methods

if (!function_exists('startTimer')) {
    function startTimer(?string $key = null, ?string $title = null): string
    {
        return TimerPanel::instance()->start($key, $title);
    }
}

if (!function_exists('startTimerSum')) {
    function startTimerSum(?string $key = null, ?string $title = null): string
    {
        return TimerPanel::instance()->startSum($key, $title);
    }
}

if (!function_exists('startTimerStack')) {
    function startTimerStack(?string $key = null, ?string $title = null): string
    {
        return TimerPanel::instance()->startStack($key, $title);
    }
}

if (!function_exists('stopTimer')) {
    function stopTimer(?string $key = null): string
    {
        return TimerPanel::instance()->stop($key);
    }
}

if (!function_exists('getLastStartedTimer')) {
    function getLastStartedTimer(): ?string
    {
        return TimerPanel::instance()->getLastStarted();
    }
}

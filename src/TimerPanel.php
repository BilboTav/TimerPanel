<?php declare(strict_types=1);

namespace Bilbofox\TimerPanel;

use Tracy;
use InvalidArgumentException;
use RuntimeException;

/**
 * Tracy panel for displaying of measuring time of snippets in code
 */
class TimerPanel implements Tracy\IBarPanel
{
    const MODE_DEFAULT = 0;
    const MODE_STACK = 1;
    const MODE_SUM = 2;

    /**
     * @var int
     * @internal
     */
    private $counter;

    /** @var array */
    private $timers = [];

    /** @var callable|null */
    private $formatter;

    // -----------------------------------------------------------------------------
    // Tracy integration

    /**
     * Adds panel to global Tracy Debugger
     *
     * @return self
     */
    public static function register(): self
    {
        $panel = self::instance();
        if ($panel === null) {
            $panel = new self();
            Tracy\Debugger::getBar()->addPanel($panel);
        }

        return $panel;
    }

    /**
     * Return panel from global Tracy Debugger
     *
     * @return self|null
     */
    public static function instance(): ?self
    {
        return Tracy\Debugger::getBar()->getPanel(__CLASS__);
    }

    // -----------------------------------------------------------------------------
    // get/set

    public function setFormatter(callable $formatter): self
    {
        $this->formatter = $formatter;
        return $this;
    }

    public function getFormatter(): callable
    {
        return $this->formatter ?? __CLASS__ . '::defaultFormatter';
    }

    // -----------------------------------------------------------------------------
    // public interface

    /**
     * Starts timer of given key - default mode overwrites already existing timers
     *
     * @param string|null $key Key is autogenerated if null
     * @param string|null $title
     * @param int $mode
     *
     * @return string Key of timer used
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function start(?string $key = null, ?string $title = null, int $mode = self::MODE_DEFAULT): string
    {
        if (!in_array($mode, [self::MODE_DEFAULT, self::MODE_STACK, self::MODE_SUM], true)) {
            throw new InvalidArgumentException('Unknown mode, please use one of MODE_* constants');
        }

        if ($key === null) {
            if ($mode === self::MODE_STACK) {
                $key = 'stack';
            } elseif ($mode === self::MODE_SUM) {
                $key = 'sum';
            } else {
                // On default mode - default key is suffixed by counter each time...
                $key = 'timer_' . sprintf('%03d', ++$this->counter);
            }
        }

        // Stack adds always new times OR when timer is missing from sum
        if ($mode === self::MODE_STACK || !isset($this->timers[$key])) {
            $timer = (object)[];
        } else {
            $timer = $this->timers[$key];
        }

        $trace = self::getTrace();

        $timer->mode = $mode;
        $timer->start = self::time();
        $timer->stop = null;
        $timer->title = $title;
        $timer->file = $trace !== null ? $trace->file : null;
        $timer->line = $trace !== null ? $trace->line : null;
        $timer->counter = isset($timer->counter) ? $timer->counter + 1 : 1;

        if ($mode === self::MODE_STACK) {
            if (isset($this->timers[$key]) && !is_array($this->timers[$key])) {
                throw new RuntimeException(
                    sprintf('Can not use key "%s" for stack timer - already used in another non-stacked timer', $key)
                );
            }

            $this->timers[$key][] = $timer;
        } else {
            $this->timers[$key] = $timer;
        }

        return $key;
    }

    /**
     * Starts timer of given key in sum mode - timers of same key are calculated into final sum
     *
     * @param string|null $key Key is autogenerated if null
     * @param string|null $title
     * @return string Key of timer used
     */
    public function startSum(?string $key = null, ?string $title = null): string
    {
        return $this->start($key, $title, self::MODE_SUM);
    }

    /**
     * Starts timer of given key in stack mode - timers are stacked into subsection
     *
     * @param string|null $key Key is autogenerated if null
     * @param string|null $title
     * @return string Key of timer used
     */
    public function startStack(?string $key = null, ?string $title = null): string
    {
        return $this->start($key, $title, self::MODE_STACK);
    }

    /**
     * Returns key of last started timer or null if no timer has been started
     *
     * @return string|null
     */
    public function getLastStarted(): ?string
    {
        $timersReverse = array_reverse($this->timers, true);
        foreach ($timersReverse as $timerKey => $timer) {
            if (!isset($timer->stop)) {
                return $timerKey;
            }
        }

        return null;
    }

    /**
     * Stops timer of given key or last started if null is passed
     *
     * @param string|null $key If null (default) - last started timer is stopped
     * @return string Key of timer used
     */
    public function stop(?string $key = null): string
    {
        $key = $key ?? $this->getLastStarted();
        if ($key === null) {
            throw new RuntimeException('No timer was started');
        }

        $timer = $this->timers[$key] ?? null;
        // Stack timers...
        if (is_array($timer)) {
            foreach ($timer as $timerStack) {
                if (!isset($timerStack->stop)) {
                    $this->stopTimer($timerStack);
                }
            }
        } else {
            if ($timer === null) {
                throw new RuntimeException(sprintf('Timer "%s" not found', $key));
            }

            $this->stopTimer($timer);
        }

        return $key;
    }

    /**
     *
     * @param object $timer
     * @return void
     */
    private function stopTimer(/*object*/ $timer): void
    {
        if (isset($timer->stop)) {
            throw new RuntimeException(sprintf('Timer "%s" was already stopped', $key));
        }

        $timer->stop = self::time();
        $time = $timer->stop - $timer->start;

        if ($timer->mode === self::MODE_SUM) {
            if (!isset($timer->time)) {
                $timer->time = 0;
            }
            $timer->time += $time;
        } else {
            $timer->time = $time;
        }
    }

    /**
     * Stops all running timers
     *
     * @return void
     */
    public function stopAll(): void
    {
        foreach ($this->timers as $timerKey => $timer) {
            if (!isset($this->timers[$timerKey]->stop)) {
                $this->stop($timerKey);
            }
        }
    }

    // -----------------------------------------------------------------------------
    // helpers

    final protected static function getTrace()/*: ?object*/
    {
        $traceStack = debug_backtrace();
        while (($trace = array_shift($traceStack)) !== null) {
            if ($trace['file'] !== __FILE__ && $trace['file'] !== __DIR__ . '/shortcuts.php') {
                return (object)$trace;
            }
        }

        return null;
    }

    protected static function time(): float
    {
        // hrtime is supported >= PHP 7.3 - https://www.php.net/manual/en/function.hrtime.php
        return function_exists('hrtime') ? hrtime(true) / 1e9 : microtime(true);
    }

    public static function defaultFormatter(float $time, int $precision): string
    {
        $unit = 's';
        if ($time < 1) {
            $time *= 1000;
            $unit = 'ms';
        }

        if ($unit === 's') {
            $color = 'red';
        } elseif ($unit === 'ms' && $time >= 500) {
            $color = 'brown';
        }

        $output = '';
        if (isset($color)) {
            $output .= '<span style="color: ' . $color . ';">';
        }
        $output .= round($time, $precision) . ' ' . $unit;
        if (isset($color)) {
            $output .= '</span>';
        }

        return $output;
    }

    // -----------------------------------------------------------------------------
    // ~Tracy\IBarPanel

    public function getTab()
    {
        $this->stopAll();

        return Tracy\Helpers::capture(function (): void {
            if (!$this->timers) {
                return;
            }

            $formatter = $this->getFormatter();
            $sum = 0;
            foreach ($this->timers as $timer) {
                if (is_array($timer)) {
                    foreach ($timer as $stackTimer) {
                        $sum += $stackTimer->time;
                    }
                } else {
                    $sum += $timer->time;
                }
            }
            require __DIR__ . '/templates/TimerPanel.tab.phtml';
        });
    }


    /**
     * Renders panel.
     */
    public function getPanel()
    {
        return Tracy\Helpers::capture(function (): void {
            $formatter = $this->getFormatter();
            $timers = $this->timers;
            require __DIR__ . '/templates/TimerPanel.panel.phtml';
        });
    }
}
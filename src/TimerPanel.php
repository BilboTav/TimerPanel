<?php declare(strict_types=1);

namespace Bilbofox\TimerPanel;

use Tracy;
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

    public static function register(): self
    {
        $panel = self::instance();
        if ($panel === null) {
            $panel = new self();
            Tracy\Debugger::getBar()->addPanel($panel);
        }

        return $panel;
    }

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
     * Starts timer of given key
     *
     * @param string|null $key
     * @param string|null $title
     * @return string Key of timer used
     */
    public function start(?string $key = null, ?string $title = null, int $mode = self::MODE_DEFAULT): string
    {
        $key = $key ?? 'timer_' . sprintf('%03d', ++$this->counter);

        if (!in_array($mode, [self::MODE_DEFAULT, self::MODE_STACK, self::MODE_SUM], true)) {
            throw new RuntimeException('Unknown mode, please use one of MODE_* constants');
        }

        // Default cannot restart already existing timer
        if ($mode === self::MODE_DEFAULT && isset($this->timers[$key])) {
            throw new RuntimeException(sprintf('Timer "%s" already exists - please use stack or sum mode', $key));
        }

        // Stack adds always new times OR when timer is missing from sum
        if ($mode === self::MODE_STACK || !isset($this->timers[$key])) {
            $timer = (object)[];
        } else {
            $timer = $this->timers[$key];
        }

        $trace = debug_backtrace(0, 1);
        $traceLast = array_shift($trace);
        $timer->mode = $mode;
        $timer->start = self::time();
        $timer->stop = null;
        $timer->title = $title;
        $timer->file = $traceLast !== null ? $traceLast['file'] : null;
        $timer->line = $traceLast !== null ? $traceLast['line'] : null;
        $timer->counter = isset($timer->counter) ? $timer->counter + 1 : 1;

        if ($mode === self::MODE_STACK) {
//            throw new RuntimeException('Stack mode not supported yet');
            $this->timers[$key][] = $timer;
        } else {
            $this->timers[$key] = $timer;
        }

        return $key;
    }

    public function startSum(?string $key = null, ?string $title = null): string
    {
        return $this->start($key, $title, self::MODE_SUM);
    }

    public function startStack(?string $key = null, ?string $title = null): string
    {
        return $this->start($key, $title, self::MODE_STACK);
    }

    /**
     * Stops timer of given key
     *
     * @param string|null $key If null (default) - last started timer is stopped
     * @return string Key of timer used
     */
    public function stop(?string $key = null): string
    {
        if ($key === null) {
            // Looking for last started timer...
            $timersReverse = array_reverse($this->timers, true);
            foreach ($timersReverse as $timerKey => $timer) {
                if (!isset($timer->stop)) {
                    $key = $timerKey;
                    break;
                }
            }
        }

        $timer = $this->timers[$key] ?? null;
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

    private function stopTimer(object $timer): void
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

    protected static function time(): float
    {
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
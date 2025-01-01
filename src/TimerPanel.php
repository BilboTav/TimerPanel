<?php declare(strict_types=1);

namespace Bilbofox\TimerPanel;

use Tracy;
use RuntimeException;

class TimerPanel implements Tracy\IBarPanel
{
    const MODE_DEFAULT = 0;
    const MODE_STACK = 1;
    const MODE_SUM = 2;

    /**
     * @var int
     * @internal
     */
    private $counter = 0;

    /** @var array */
    private $timers = [];

    /** @var callable|null */
    private $formatter;

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

    public function setFormatter(callable $formatter): self
    {
        $this->formatter = $formatter;
        return $this;
    }

    public function getFormatter(): callable
    {
        return $this->formatter ?? __CLASS__ . '::defaultFormatter';
    }

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

        if ($mode === self::MODE_DEFAULT && isset($this->timers[$key])) {
            throw new RuntimeException(sprintf('Timer "%s" already exists - please use stack or sum mode', $key));
        }

        $timer = $this->timers[$key] ?? (object)[];

        $trace = debug_backtrace(0, 1);
        $traceLast = array_shift($trace);
        $timer->mode = $mode;
        $timer->start = self::time();
        $timer->stop = null;
        $timer->title = $title;
        $timer->file = $traceLast !== null ? $traceLast['file'] : null;
        $timer->line = $traceLast !== null ? $traceLast['line'] : null;
        $timer->counter = isset($timer->counter) ? $timer->counter + 1 : 0;

        if ($mode === self::MODE_STACK) {
            $this->timers[$key][] = $timer;
        } else {
            $this->timers[$key] = $timer;
        }

        return $key;
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

        if (!isset($this->timers[$key])) {
            throw new RuntimeException(sprintf('Timer "%s" not found', $key));
        }
        $timer = $this->timers[$key];

        if (isset($timer->stop)) {
            throw new RuntimeException(sprintf('Timer "%s" was already stopped', $key));
        }

        $timer->stop = self::time();
        $time = $timer->stop - $timer->start;

        if ($timer->mode === self::MODE_SUM) {
            $timer->time += $time;
        } else {
            $timer->time = $time;
        }

        return $key;
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

    protected static function time(): float
    {
        return microtime(true);
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
                $sum += $timer->time;
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
<?php declare(strict_types=1);

namespace Bilbofox\TimerPanel;

use Tracy;

class TimerPanel implements Tracy\IBarPanel
{
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

    public function start(string $key, ?string $title = null)
    {
        $this->timers[$key] = (object)[
            'start' => microtime(true),
            'title' => $title,
        ];
    }

    public function stop(string $key)
    {
        $this->timers[$key]->stop = microtime(true);
        $this->timers[$key]->time = $this->timers[$key]->stop - $this->timers[$key]->start;
    }

    public static function defaultFormatter(float $time): string
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
        $output .= round($time, 4) . ' ' . $unit;
        if (isset($color)) {
            $output .= '</span>';
        }

        return $output;
    }

    // -----------------------------------------------------------------------------
    // ~Tracy\IBarPanel

    public function getTab()
    {
        return Tracy\Helpers::capture(function (): void {
            require __DIR__ . '/templates/TimerPanel.tab.phtml';
        });
    }


    /**
     * Renders panel.
     */
    public function getPanel()
    {
        return Tracy\Helpers::capture(function (): void {
            $formatter = $this->formatter ?? __CLASS__ . '::defaultFormatter';
            $timers = $this->timers;
            require __DIR__ . '/templates/TimerPanel.panel.phtml';
        });
    }
}
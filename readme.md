# TimerPanel

![logo](logo.png)

Tracy debugger panel for measuring runtime of code snippets

## Quickstart

Registering panel into Tracy bar can be done simply by calling prepared static method:

```php
// Register panel to global Tracy instance...
$tracyPanel = Bilbofox\TimerPanel\TimerPanel::register();
// ...
```

```php
// ...
// Retrieve instance of panel back
$tracyPanel = Bilbofox\TimerPanel\TimerPanel::instance();
// ...

$tracyPanel->start();
// HEAVY LIFTING...
// 
// A
// ||
// ||
// ==============================
$tracyPanel->stop();
```

## Public methods

Timer panel instance has methods for public usage - starting / stopping timers etc. Each such
method also has global function equvialent shortcut:

| Method                                                                                                  | Global shortcut      | Description                                                                                                         |
|---------------------------------------------------------------------------------------------------------|----------------------|---------------------------------------------------------------------------------------------------------------------|
| `TimerPanel::start(?string $key = null, ?string $title = null, int $mode = self::MODE_DEFAULT): string` | `startTimer()`       | Starts timer of given key - default mode overwrites already existing timers, returns key of timer used              |
| `TimerPanel::startSum(?string $key = null, ?string $title = null): string`                              | `startTimerSum()`    | Starts timer of given key in sum mode - timers of same key are calculated into final sum, returns key of timer used |
| `TimerPanel::startStack(?string $key = null, ?string $title = null): string`                            | `startTimerStack()`  | Starts timer of given key in stack mode - timers are stacked into subsection, returns key of timer used             |
| `TimerPanel::getLastStarted(): ?string`                                                                 | `getLastStartedTimer()` | Returns key of last started timer or null if no timer has been started                                              |
| `TimerPanel::stop(?string $key = null): string`                                                         | `stopTimer()`        | Stops timer of given key or last started if null is passed                                                          |
| `TimerPanel::stopAll(): void`                                                                           | `stopAllTimers()`    | Stops all running timers                                                                                            |

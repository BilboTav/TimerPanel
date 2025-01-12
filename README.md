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
// heavy lifting...
$tracyPanel->stop();
```

Timer panel instance has methods for public usage - starting / stopping timerc etc. Each such
method also has global function shortcut:

| method                         | shortcut                | description              |
|--------------------------------|-------------------------|--------------------------|
| `TimerPanel::start()`          | `startTimer()`          | Starts timer in default mode |
| `TimerPanel::startSum()`       | `startTimerSum()`       | Starts timer in sum mode |
| `TimerPanel::startStack()`     | `startTimerStack()`     |                          |
| `TimerPanel::getLastStarted()` | `getLastStartedTimer()` |                          |
| `TimerPanel::stop()`           | `stopTimer()`           |                          |
| `TimerPanel::stopAll()`        | `stopAllTimers()`       |                          |

For the purpose of examples in next sections - global shortcuts will be used.

## Basic usage

```
startTimer();
stopTimer();
```


For [basic usage](examples/basic.php) we simply call 

## Modes

modes TODO...
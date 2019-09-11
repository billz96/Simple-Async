<?php
    class EventEmitter {
        private $events;

        function __construct() {
            $this->events = [];
        }

        function on(string $eventName, callable $listener) {
            if (!isset($this->events[$eventName])) {
                $this->events[$eventName] = [];
            }
            $this->events[$eventName]['always'][] = $listener;
        }

        function once(string $eventName, callable $listener) {
            if (!isset($this->events[$eventName])) {
                $this->events[$eventName] = [];
            }
            $this->events[$eventName]['once'][] = $listener;
        }

        function off(string $eventName) {
           unset($this->events[$eventName]);
        }

        function removeListener(string $eventName, callable $listener = null) {
            if ($listener !== null) {
                if ($index = array_search($listener, $this->events[$eventName]['always']) !== null) {
                    unset($this->events[$eventName]['always'][$index]);
                }
                if ($index = array_search($listener, $this->events[$eventName]['once']) !== null) {
                    unset($this->events[$eventName]['once'][$index]);
                }
            } else {
                $this->removeListeners($eventName);
            }
        }

        function removeListeners(string $eventName) {
            $this->events[$eventName]['always'] = [];
            $this->events[$eventName]['once'] = [];
        }

        function emitt(string $eventName, ...$args) {
            foreach ($this->events[$eventName]['always'] as $listener) {
                $listener(...$args);
            }

            foreach ($this->events[$eventName]['once'] as $listener) {
                $listener(...$args);
                $index = array_search($listener, $this->events[$eventName]['once']);
                unset($this->events[$eventName]['once'][$index]);
            }
        }
    }
?>
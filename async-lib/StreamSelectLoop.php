<?php
    /** Converts milliseconds to seconds */
    function millisecs($x) {
        return 0.001 * $x;
    }

    /** Converts microseconds to seconds */
    function microsecs($x) {
        return 1.0 * (10 ** -6) * $x;
    }

    /** A simple stream_select() based event-loop. */
    final class StreamSelectLoop {
        private $readStreams = [];
        private $readHandlers = [];
        private $writeStreams = [];
        private $writeHandlers = [];
        private $nextTicks = [];
        private $timeouts = [];
        private $intervals = [];
        private $running = true;

        static $currentIntervalID = 0;

        static function instance(StreamSelectLoop $newLoop = null) : StreamSelectLoop {
            static $loop;

            if ($newLoop !== null) {
                $loop = $newLoop;
            } else {
                $loop = new StreamSelectLoop();
            }

            return $loop;
        }

        function addReadStream($stream, callable $handler) {
            if (empty($this->readStreams[(int) $stream])) {
                $this->readStreams[(int) $stream] = $stream;
                $this->readHandlers[(int) $stream] = $handler;
            }
        }

        function addWriteStream($stream, callable $handler) {
            if (empty($this->writeStreams[(int) $stream])) {
                $this->writeStreams[(int) $stream] = $stream;
                $this->writeHandlers[(int) $stream] = $handler;
            }
        }

        function removeReadStream($stream) {
            unset($this->readStreams[(int) $stream]);
        }

        function removeWriteStream($stream) {
            unset($this->writeStreams[(int) $stream]);
        }

        function removeStream($stream) {
            $this->removeReadStream($stream);
            $this->removeWriteStream($stream);
        }

        function setTimeout($microsecs, callable $func) {
            // timestamp in microsecs
            $currentTime = microtime(true); //round(microtime(true) * 1000);
            $timeout = ['func' => $func, 'microsecs' => $currentTime + $microsecs];
            array_push($this->timeouts, $timeout);
        }

        function setInterval($microsecs, callable $func) {
            // timestamp in microsecs
            $currentTime = microtime(true); //round(microtime(true) * 1000);

            // generate id
            $id = StreamSelectLoop::$currentIntervalID;
            StreamSelectLoop::$currentIntervalID += 1;

            $interval = [
                'id' => $id,
                'func' => $func, 
                'lastTimestamp' => $currentTime, 
                'interval' => $microsecs,
                'active' => true
            ];
            
            array_push($this->intervals, $interval);

            return $id;
        }

        function nextTick(callable $func) {
            array_push($this->nextTicks, $func);
        }

        function clearInterval($id) {
            foreach ($this->intervals as $interval) {  
                if ($interval['id'] == $id) {
                    $key = \array_search($interval, $this->intervals);
                    $this->intervals[$key]['active'] = false;
                }
            }
        }

        function stop() {
            $this->running = false;
        }

        /**
         * Runs the event loop, which blocks the current process. Make sure you do
         * any necessary setup before running this.
         */
        function run() {
            while ($this->running) {
                // timestamp in microsecs
                $currentTime = microtime(true); //round(microtime(true) * 1000);
                $timeoutSecs = 0;
                $nextTicks = $this->nextTicks;
                $timeouts = $this->timeouts;
                $intervals = $this->intervals;

                $read = $this->readStreams;
                $write = $this->writeStreams;
                $except = null;


                if (count($timeouts) > 0 || count($intervals) > 0) {
                    $timeoutSecs = 1;
                }

                // check for read-streams and write-streams
                if ($read || $write) {
                    @stream_select($read, $write, $except, $timeoutSecs, 100);

                    foreach ($read as $stream) {
                        $this->readHandlers[(int) $stream]($stream);
                    }

                    foreach ($write as $stream) {
                        $this->writeHandlers[(int) $stream]($stream);
                    }

                    // check for timeouts
                    if (count($timeouts) > 0) {
                        foreach ($timeouts as $timer) {
                            $microsecs = $timer['microsecs'];
                            if ($currentTime >= $microsecs) {
                                // execute timer
                                $func = $timer['func'];
                                $func();
                                // remove current timer
                                $key = \array_search($timer, $this->timeouts);
                                unset($this->timeouts[$key]);
                            }
                        }
                    }

                    // check for intervals
                    if (count($intervals) > 0) {
                        foreach ($intervals as $interval) {
                            if ($interval['active']) {
                                $lastTimestamp = $interval['lastTimestamp'];
                                $microsecs = $interval['interval'];

                                if ($currentTime - $lastTimestamp >= $microsecs) {
                                    // execute func if the interval has passed
                                    $func = $interval['func'];
                                    $func();
                                    // update timestamp
                                    $key = array_search($interval, $intervals);
                                    $this->intervals[$key]['lastTimestamp'] = $currentTime;
                                }
                            } else {
                                // remove current interval
                                $key = array_search($interval, $intervals);
                                unset($this->intervals[$key]);
                            }
                        }
                    }

                    // check for next ticks
                    if (count($nextTicks) > 0) {
                        $this->nextTicks = [];
                        $timeoutSecs = 0;
                        foreach ($nextTicks as $tick) {
                            $tick();
                        }
                    }
                } else {
                    // check for timeouts
                    if (count($timeouts) > 0) {
                        foreach ($timeouts as $timer) {
                            $microsecs = $timer['microsecs'];
                            if ($currentTime >= $microsecs) {
                                // execute timer
                                $func = $timer['func'];
                                $func();
                                // remove current timer
                                $key = \array_search($timer, $this->timeouts);
                                unset($this->timeouts[$key]);
                            }
                        }
                    }

                    // check for intervals
                    if (count($intervals) > 0) {
                        foreach ($intervals as $interval) {
                            if ($interval['active']) {
                                $lastTimestamp = $interval['lastTimestamp'];
                                $microsecs = $interval['interval'];

                                if ($currentTime - $lastTimestamp >= $microsecs) {
                                    // execute func if the interval has passed
                                    $func = $interval['func'];
                                    $func();
                                    // update timestamp
                                    $key = array_search($interval, $intervals);
                                    $this->intervals[$key]['lastTimestamp'] = $currentTime;
                                }
                            } else {
                                // remove current interval
                                $key = array_search($interval, $intervals);
                                unset($this->intervals[$key]);
                            }
                        }
                    }

                    // check for next ticks
                    if (count($nextTicks) > 0) {
                        $this->nextTicks = [];
                        $timeoutSecs = 0;
                        foreach ($nextTicks as $tick) {
                            $tick();
                        }
                    }

                    usleep(100);
                }
            }
        }
    }
?>
<?php
    require_once('StreamSelectLoop.php');
    
    /** Simple EchoServer class */
    class EchoServer {
        private $loop;
        private $host;
        private $port;
        private $type;

        public function __construct(StreamSelectLoop $loop, array $options) {
            $this->loop = $loop;
            $this->host = $options['host'] ? $options['host'] : 'localhost';
            $this->port = $options['port'] ? $options['port'] : '8080';
            $this->type = $options['type'] ? $options['type'] : 'tcp';
        }

        public function getPort() {
            return $this->port;
        }

        public function setPort(string $number) {
            $this->port = $number;
        }

        public function getHost() {
            return $this->host;
        }

        public function setHost(string $name) {
            $this->host = $name;
        }

        public function getType() {
            return $this->type;
        }

        public function setType(string $type) {
            $this->type = $type;
        }

        /** Adds a server socket to the stored loop object */
        public function create() {
            $loop = $this->loop;
            $server = @stream_socket_server(
                $this->type."://".$this->host.":".$this->port, 
                $errno, 
                $errstr
            );

            if ($server === false) {
                // Write error message to STDERR and exit, just like UNIX programs usually do
                fprintf(STDERR, "Error connecting to socket: %d %s\n", $errno, $errstr);
                exit(1);
            }
            
            // Make sure calling stream_socket_accept can't block when called on this server stream,
            // in case someone wants to add another server stream to the reactor
            // (maybe listening on another port, implementing another protocol ;))
            stream_set_blocking($server, 0);

            // This code runs when the socket has a connection ready for accepting
            $loop->addReadStream($server, function ($server) use ($loop) {
                $conn = @stream_socket_accept($server, -1, $peer);
                $buf = '';

                // This runs when a read can be made without blocking(get request's data?):
                $loop->addReadStream($conn, function ($conn) use ($loop, &$buf) {
                    $buf = @fread($conn, 4096) ?: ''; // read client's data
                    if (@feof($conn)) {
                        $loop->removeStream($conn);
                        fclose($conn);
                    }
                });

                // This runs when a write can be made without blocking(send a responce?):
                $loop->addWriteStream($conn, function ($conn) use ($loop, &$buf) {
                    if (strlen($buf) > 0) {
                        @fwrite($conn, $buf); // send client's data as a response
                        $buf = '';
                    }
                    if (@feof($conn)) {
                        $loop->removeStream($conn);
                        fclose($conn);
                    }
                });
            });
        }
    }
?>
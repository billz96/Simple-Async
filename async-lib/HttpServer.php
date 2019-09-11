<?php
    require_once('StreamSelectLoop.php');
    
    /** Simple HttpServer class */
    class HttpServer {
        private $loop;
        private $host;
        private $port;

        public function __construct(StreamSelectLoop $loop, array $options) {
            $this->loop = $loop;
            $this->host = $options['host'] ? $options['host'] : 'localhost';
            $this->port = $options['port'] ? $options['port'] : '3000';
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
    
        public function create() {
            $server = @stream_socket_server(
                "tcp://".$this->host.":".$this->port, 
                $errno, 
                $errstr
            );

            if ($server === false) {
                // Write error message to STDERR and exit, just like UNIX programs usually do
                fprintf(STDERR, "Error connecting to socket: %d %s\n", $errno, $errstr);
                exit(1);
            }

            stream_set_blocking($server, 0);
            printf("Listening on port %d...\n", $port);

            $loop = $this->loop;
            $loop->addReadStream($server, function ($server) use ($loop) {
                $conn = @stream_socket_accept($server);
                if (!is_resource($conn)) {
                    return;
                }

                $loop->addWriteStream($conn, function ($conn) use ($loop) {
                    $content = "<h1>Hello World</h1>"; // create a responce
                    $length = strlen($content);
                    fwrite($conn, "HTTP/1.1 200 OK\r\n");
                    fwrite($conn, "Content-Type: text/html\r\n");
                    fwrite($conn, "Content-Length: $length\r\n");
                    fwrite($conn, "\r\n");
                    fwrite($conn, $content); // send a responce
                    fclose($conn); // close connection?
                    $loop->removeStream($conn);
                });
            });
        }
    }
?>
<?php 

    class Request {
        
        protected $method = null;
        protected $uri = null;
        protected $parameters = [];
        protected $headers = [];
        
        public static function withHeaderString( $header )
        {
            $lines = explode( "\n", $header );
            
            // method and uri
            list( $method, $uri ) = explode( ' ', array_shift( $lines ) );
            
            $headers = [];
            
            foreach( $lines as $line )
            {
                // clean the line
                $line = trim( $line );
                
                if ( strpos( $line, ': ' ) !== false )
                {
                    list( $key, $value ) = explode( ': ', $line );
                    $headers[$key] = $value;
                }
            }	
            
            // create new request object
            return new static( $method, $uri, $headers );
        }
        
        public function __construct( $method, $uri, $headers = [] ) 
        {
            $this->headers = $headers;
            $this->method = strtoupper( $method );
            
            // split uri and parameters string
            @list( $this->uri, $params ) = explode( '?', $uri );
            // parse the parmeters
            parse_str( $params, $this->parameters );
        }
        
        public function method()
        {
            return $this->method;
        }
        
        public function uri()
        {
            return $this->uri;
        }
        
        public function header( $key, $default = null )
        {
            if ( !isset( $this->headers[$key] ) )
            {
                return $default;
            }
            
            return $this->headers[$key];
        }
        
        public function param( $key, $default = null )
        {
            if ( !isset( $this->parameters[$key] ) )
            {
                return $default;
            }
            
            return $this->parameters[$key];
        }
    }

?>
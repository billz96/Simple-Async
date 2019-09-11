<?php
    require_once('EchoServer.php');

    $loop = new StreamSelectLoop();

    $server = new EchoServer($loop, ['port' => 8080]);
    $server->create();

    $loop->nextTick(
        function () {
            echo "tick.";
        }
    );
    
    $loop->run();
?>
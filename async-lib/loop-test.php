<?php
    require_once('StreamSelectLoop.php');

    $loop = new StreamSelectLoop();
    
    $loop->setTimeout(
        3,
        function () {
           echo "timeout-1\n";
        }
    );

    $count = 0;
    $id = $loop->setInterval(
        4, 
        function () use (&$loop, &$count, &$id) {
            if ($count < 5) {
                echo "count = ${count}\n";
                $count+=1;
            } else {
                echo "done.\n";
                $loop->clearInterval($id);
            }
        }
    );

    $loop->setTimeout(
        3,
        function () {
           echo "timeout-2\n";
        }
    );

    $loop->nextTick(
        function () {
           echo "nextTick\n";
        }
    );

    $loop->setInterval(
        1,
        function () {
           echo "tick\n";
        }
    );

    $loop->run();
?>
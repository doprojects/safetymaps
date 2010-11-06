<?php

    require_once 'config.php';
    require_once 'lib.php';

    $ctx = default_context();

    $ctx->close();
    
    header("Content-Type: text/html; charset=UTF-8");
    print $ctx->sm->fetch('scenarios.html.tpl');

?>

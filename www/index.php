<?php

    require_once 'config.php';
    require_once 'lib.php';

    $ctx = default_context();

    $count = 10;
    $offset = 0;
    $where = null;
    
    $response = get_maps($ctx, compact('count', 'offset', 'where'));
    $ctx->sm->assign('maps', $response);
    
    $ctx->close();
    
    header("Content-Type: text/html; charset=UTF-8");
    print $ctx->sm->fetch('index.html.tpl');

?>

<?php

    ini_set('include_path', ini_get('include_path').PATH_SEPARATOR.'../lib');

    require_once 'config.php';
    require_once 'lib.php';

    $ctx = default_context();

    $maps = get_maps($ctx, array('count' => 10));
    $ctx->sm->assign('maps', $maps);

    $ctx->close();
    
    header("Content-Type: text/html; charset=UTF-8");
    print $ctx->sm->fetch('index.html.tpl');

?>

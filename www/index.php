<?php

    ini_set('include_path', ini_get('include_path').PATH_SEPARATOR.'../lib');

    require_once 'config.php';
    require_once 'lib.php';

    $ctx = default_context();

    $count = 12;
    $maps = get_maps($ctx, array('count' => $count+1));

    $ctx->sm->assign('more_older_maps', count($maps) > $count);
    $ctx->sm->assign('older_maps_offset', $count);

    $maps = array_slice($maps, 0, $count);
    $ctx->sm->assign('maps_count', count($maps));
    $ctx->sm->assign('count', $count);
    $ctx->sm->assign('maps', $maps);

    $ctx->close();
    
    header("Content-Type: text/html; charset=UTF-8");
    print $ctx->sm->fetch('index.html.tpl');

?>

<?php

    ini_set('include_path', ini_get('include_path').PATH_SEPARATOR.'../lib');

    require_once 'config.php';
    require_once 'lib.php';

    $ctx = default_context();

    $ctx->close();
    
    $ctx->sm->assign('recipient', array('name' => 'Biff'));
    $ctx->sm->assign('sender', array('name' => 'Duff'));
    $ctx->sm->assign('map_href', 'http://example.com/your-map');
    
    header("Content-Type: text/html; charset=UTF-8");
    print $ctx->sm->fetch('recipient-mail.html.tpl');

?>

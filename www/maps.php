<?php

    require_once 'lib.php';

    $db = mysql_connect('localhost', 'safetymaps', 's4f3tym4ps');
    mysql_select_db('safetymaps', $db);
    
    $ctx = new Context($db);

    if($_POST['sender'] && $_POST['place'] && $_POST['map'] && $_POST['recipients'])
    {
        $attempted_add = true;
        $added_map_id = add_map($ctx, $_POST);
    }
    
    $format = empty($_GET['format']) ? 'json' : $_GET['format'];
    $count = is_numeric($_GET['count']) ? intval($_GET['count']) : 10;
    $offset = is_numeric($_GET['offset']) ? intval($_GET['offset']) : 0;
    
    $maps = get_maps($ctx, compact('count', 'offset'));
    
    $ctx->close();
    
    if($format == 'json') {
        header('Content-Type: text/json');
        header('Access-Control-Allow-Origin: *');
        echo json_encode($maps)."\n";
    
    } elseif($format == 'text') {
        header('Content-Type: text/plain');
        header('Access-Control-Allow-Origin: *');
        print_r($maps);
    
    } else {
        header('HTTP/1.1 400');
        header('Content-Type: text/plain');
        echo "Unknown format: {$format}\n";
    
    }

?>

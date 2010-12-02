<?php

    require_once 'config.php';
    require_once 'lib.php';
    header('Access-Control-Allow-Origin: *');

    $ctx = default_context();

    $format = empty($_GET['format']) ? 'html' : $_GET['format'];
    $count = is_numeric($_GET['count']) ? intval($_GET['count']) : 10;
    $offset = is_numeric($_GET['offset']) ? intval($_GET['offset']) : 0;

    $where = preg_match('/^bbox:-?\d+(\.\d+)?(,-?\d+(\.\d+)?){3}$/', $_GET['where'])
                ? array_map('floatval', explode(',', substr($_GET['where'], 5)))
                : null;
    
    $map_id = false;
    
    if(preg_match('#^/#', $_SERVER['PATH_INFO']))
    {
        $_GET['id'] = substr($_SERVER['PATH_INFO'], 1);
    }
    
    if(preg_match('#^(\w+)(/(\w+))?$#', $_GET['id'], $m))
    {
        $map_id = $m[1];
        $recipient_id = $m[3];
    }
    
    if($_SERVER['REQUEST_METHOD'] == 'POST')
    {
        $sender = is_array($_POST['sender']) ? $_POST['sender'] : null;
        $place = is_array($_POST['place']) ? $_POST['place'] : null;
        $map = is_array($_POST['map']) ? $_POST['map'] : null;
        $recipients = is_array($_POST['recipients']) ? $_POST['recipients'] : null;
        
        if($sender && $place && $map && $recipients)
        {
            mysql_query('BEGIN', $ctx->db);

            $add_args = compact('sender', 'place', 'map', 'recipients');
            $map_id = add_map($ctx, $add_args);
            
            if($map_id === false) {
                header('HTTP/1.1 500');
                header('Content-Type: text/plain');
                mysql_query('ROLLBACK', $ctx->db);
                echo "Couldn't make your map, not sure why.\n";

            } else {
                $href = 'http://'.get_domain_name().get_base_dir().'/maps.php?id='.urlencode($map_id);
                $href .= ($format != 'html' ? "&format={$format}" : '');
            
                header('HTTP/1.1 303');
                header("Location: {$href}");
                header('Content-Type: text/plain');
                mysql_query('COMMIT', $ctx->db);
                echo "Made you a map.\n";
            }

            $ctx->close();
            exit();
        }
        
        header('HTTP/1.1 400');
        header('Content-Type: text/plain');
        echo "Please post a sender, place, map, and recipients.\n";

        $ctx->close();
        exit();
    }
    
    if($map_id === false) {
        $maps = get_maps($ctx, compact('count', 'offset', 'where'));
        
    } else {
        $map = get_map($ctx, $map_id);
        $ctx->sm->assign('map', $map);
        
        $maps = array($map);
    }
    
    if($recipient_id)
    {
        $recipient = get_recipient($ctx, $recipient_id);
        $ctx->sm->assign('recipient', $recipient);
    }

    $ctx->sm->assign('maps', $maps);
    
    $ctx->close();
    
    if($format == 'json') {
        header('Content-Type: text/json');
        echo json_encode(map_rows2collection($maps))."\n";
    
    } elseif($format == 'text') {
        header('Content-Type: text/plain');
        print_r($maps);
    
    } elseif($format == 'html') {
        header("Content-Type: text/html; charset=UTF-8");
        print $ctx->sm->fetch('maps.html.tpl');
    
    } else {
        header('HTTP/1.1 400');
        header('Content-Type: text/plain');
        echo "Unknown format: {$format}\n";
    }

?>

<?php

    require_once 'lib.php';
    header('Access-Control-Allow-Origin: *');

    $db = mysql_connect('localhost', 'safetymaps', 's4f3tym4ps');
    mysql_select_db('safetymaps', $db);
    
    $format = empty($_GET['format']) ? 'json' : $_GET['format'];
    $count = is_numeric($_GET['count']) ? intval($_GET['count']) : 10;
    $offset = is_numeric($_GET['offset']) ? intval($_GET['offset']) : 0;

    $where = preg_match('/^bbox:-?\d+(\.\d+)?(,-?\d+(\.\d+)?){3}$/', $_GET['where'])
                ? array_map('floatval', explode(',', substr($_GET['where'], 5)))
                : null;
    
    $map_id = is_numeric($_GET['id']) ? intval($_GET['id']) : false;
    
    $ctx = new Context($db);

    if($_SERVER['REQUEST_METHOD'] == 'POST')
    {
        $sender = is_array($_POST['sender']) ? $_POST['sender'] : null;
        $place = is_array($_POST['place']) ? $_POST['place'] : null;
        $map = is_array($_POST['map']) ? $_POST['map'] : null;
        $recipients = is_array($_POST['recipients']) ? $_POST['recipients'] : null;
        
        if($sender && $place && $map && $recipients)
        {
            $add_args = compact('sender', 'place', 'map', 'recipients');
            $map_id = add_map($ctx, $add_args);
            
            if($map_id) {
                header('HTTP/1.1 303');
                header("Location: {$_SERVER['SCRIPT_NAME']}?id={$map_id}&format={$format}");
                echo "Made you a map.\n";

            } else {
                header('HTTP/1.1 500');
                header('Content-Type: text/plain');
                echo "Couldn't make your map, not sure why.\n";
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
        $response = get_maps($ctx, compact('count', 'offset', 'where'));
        
    } else {
        $response = get_map($ctx, $map_id);
    }
    
    $ctx->close();
    
    if($format == 'json') {
        header('Content-Type: text/json');
        echo json_encode($response)."\n";
    
    } elseif($format == 'text') {
        header('Content-Type: text/plain');
        print_r($response);
    
    } else {
        header('HTTP/1.1 400');
        header('Content-Type: text/plain');
        echo "Unknown format: {$format}\n";
    }

?>

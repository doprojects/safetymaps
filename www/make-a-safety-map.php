<?php

    ini_set('include_path', ini_get('include_path').PATH_SEPARATOR.'../lib');

    require_once 'config.php';
    require_once 'lib.php';

    $ctx = default_context();

    if($_SERVER['REQUEST_METHOD'] == 'POST')
    {
        $sender = is_array($_POST['sender']) ? $_POST['sender'] : null;
        $place = is_array($_POST['place']) ? $_POST['place'] : null;
        $map = is_array($_POST['map']) ? $_POST['map'] : null;
        $recipients = is_array($_POST['recipients']) ? $_POST['recipients'] : null;
        
        // check each form input
        $is_complete = true;

        $is_complete = empty($sender['name']) ? false : $is_complete;
        $is_complete = empty($sender['email']) ? false : $is_complete;
        $is_complete = empty($place['emergency']) ? false : $is_complete;
        $is_complete = empty($place['name']) ? false : $is_complete;
        $is_complete = empty($place['location']) ? false : $is_complete;
        $is_complete = empty($map['bounds']) ? false : $is_complete;
        
        foreach($recipients as $recipient)
        {
            $is_complete = empty($recipient['name']) ? false : $is_complete;
            $is_complete = empty($recipient['email']) ? false : $is_complete;
        }
        
        if($is_complete)
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
                $href = 'http://'.get_domain_name().get_base_dir().'/maps.php/'.urlencode($map_id);
                $href .= ($format != 'html' ? "?format={$format}" : '');
            
                header('HTTP/1.1 303');
                header("Location: {$href}");
                header('Content-Type: text/plain');
                mysql_query('COMMIT', $ctx->db);
                echo "Made you a map.\n";
            }

            $ctx->close();
            exit();
        }
    }
    
    $ctx->close();
    
    header("Content-Type: text/html; charset=UTF-8");
    print $ctx->sm->fetch('make-a-safety-map.html.tpl');

?>

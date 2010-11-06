<?php

    ini_set('include_path', ini_get('include_path').PATH_SEPARATOR.'..');
    require_once 'config.php';
    require_once 'lib.php';

    $ctx = default_context();
    
    mysql_query('BEGIN', $ctx->db);
    
    $q = "SELECT id, map_id, user_id, name
          FROM recipients
          WHERE sent IS NULL
            AND queued < NOW()
          ORDER BY queued ASC
          LIMIT 1";
    
    if($res = mysql_query($q, $ctx->db))
    {
        if($row = mysql_fetch_assoc($res))
        {
            $_id = sprintf('%d', $row['id']);

            $recipient_name = $row['name'];
            $recipient_id = $row['id'];
        
            $q = "UPDATE recipients
                  SET queued = NOW() + INTERVAL 30 SECOND
                  WHERE id = {$_id}";

            $res = mysql_query($q, $ctx->db);
            
            $map = get_map($ctx, $row['map_id']);
            
            $job = array(
                'sender' => array('name' => $map['user']['name']),
                'place' => array(
                    'name' => $map['place_name'],
                    'location' => array($map['place_lat'], $map['place_lon']),
                    'emergency' => $map['emergency'],
                    'full-note' => $map['note_full'],
                    'short-note' => $map['note_short']
                ),
                'map' => array(
                    'bounds' => array(
                        $map['bbox_north'], $map['bbox_east'],
                        $map['bbox_south'], $map['bbox_west']
                    ),
                    'paper' => $map['paper'],
                    'format' => $map['format']
                ),
                'recipient' => array('name' => $recipient_name),

                'post-back' => array(
                    'pdf' => sprintf('%s/pdf.php?id=%s', str_replace(' ', '%20', dirname($_SERVER['SCRIPT_NAME'])), urlencode($recipient_id))
                )
            );
            
            mysql_query('COMMIT', $ctx->db);
            $ctx->close();
            
            header('Content-Type: text/json');
            echo json_encode($job)."\n";
            exit();
        }
    }
    
    mysql_query('ROLLBACK', $ctx->db);
    $ctx->close();
    
    header('HTTP/1.1 404');
    header('Content-Type: text/plain');
    echo "No jobs.\n";

?>

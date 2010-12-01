<?php

    ini_set('include_path', ini_get('include_path').PATH_SEPARATOR.'..');
    require_once 'config.php';
    require_once 'lib.php';

    $ctx = default_context();
    
    mysql_query('BEGIN', $ctx->db);
    
    $q = "SELECT id
          FROM recipients
          WHERE sent IS NULL
            AND failed IS NULL
            AND queued < NOW()
          ORDER BY queued ASC
          LIMIT 1";
    
    if($res = mysql_query($q, $ctx->db))
    {
        if($row = mysql_fetch_assoc($res))
        {
            $recipient = get_recipient($ctx, $row['id']);
            $_recipient_id = sprintf('%d', $recipient['id']);
        
            $q = "UPDATE recipients
                  SET queued = NOW() + INTERVAL 20 SECOND
                  WHERE id = {$_recipient_id}";

            $res = mysql_query($q, $ctx->db);
            
            $map = get_map($ctx, $recipient['map_id']);
            $sender = $map['user'];
            
            $job = array(
                'sender' => array('name' => $sender['name']),
                'place' => array(
                    'name' => $map['place_name'],
                    'location' => array($map['place_lat'], $map['place_lon']),
                    'emergency' => $map['emergency'],
                    'full-note' => $map['note_full']
                ),
                'formats' => $ctx->formats,
                'papers' => $ctx->papers,
                'map' => array(
                    'bounds' => array(
                        $map['bbox_north'], $map['bbox_east'],
                        $map['bbox_south'], $map['bbox_west']
                    )
                ),
                'recipient' => array('name' => $recipient['name']),
                'sender-is-recipient' => ($sender['email'] == $recipient['email']),

                'post-back' => array(
                    'pdf' => sprintf('%s/compose/pdf.php?id=%s', get_base_dir(), urlencode($recipient['id'])),
                    'error' => sprintf('%s/compose/error.php?id=%s', get_base_dir(), urlencode($recipient['id']))
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

<?php

    ini_set('include_path', ini_get('include_path').PATH_SEPARATOR.'..');
    require_once 'config.php';
    require_once 'lib.php';

    $db = mysql_connect(MYSQL_HOSTNAME, MYSQL_USERNAME, MYSQL_PASSWORD);
    mysql_select_db(MYSQL_DATABASE, $db);
    $ctx = new Context($db);
    
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
            
            $map = get_map($ctx, $row['map_id'], false);
            list($lon, $lat) = $map['geometry']['coordinates'];
            $properties = $map['properties'];
            
            $job = array(
                'sender' => array('name' => $properties['user']['name']),
                'place' => array(
                    'name' => $properties['place_name'],
                    'location' => array($lat, $lon),
                    'emergency' => $properties['emergency'],
                    'full-note' => $properties['note_full'],
                    'short-note' => $properties['note_short']
                ),
                'map' => array(
                    'bounds' => array(
                        floatval($properties['bbox_north']),
                        floatval($properties['bbox_east']),
                        floatval($properties['bbox_south']),
                        floatval($properties['bbox_west'])
                    ),
                    'paper' => $properties['paper'],
                    'format' => $properties['format']
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

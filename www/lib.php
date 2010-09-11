<?php

    class Context
    {
        var $db;
        
        function Context(&$db_link)
        {
            $this->db =& $db_link;
        }
        
        function close()
        {
            mysql_close($this->db);
        }
    }

   /**
    * id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    * 
    * name    TINYTEXT,
    * email   TINYTEXT
    */
    function getset_user($ctx, $args)
    {
        $_name = mysql_real_escape_string($args['name'], $ctx->db);
        $_email = mysql_real_escape_string($args['email'], $ctx->db);
    
        $q = "SELECT id FROM users
              WHERE name  = '{$_name}'
                AND email = '{$_email}'";

        if($res = mysql_query($q, $ctx->db))
        {
            $row = mysql_fetch_assoc($res);
            
            if($row) {
                return $row['id'];
            
            } else {
                $q = "INSERT INTO users
                      SET name  = '{$_name}',
                          email = '{$_email}'";

                if($res = mysql_query($q, $ctx->db))
                    return mysql_insert_id($ctx->db);
            }
        }
        
        return null;
    }
    
   /**
    * id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    * user_id     INT UNSIGNED NOT NULL,
    * 
    * place_name  TINYTEXT,
    * place_lat   DOUBLE,
    * place_lon   DOUBLE,
    * 
    * emergency   TINYTEXT,
    * note_full   TEXT,
    * note_short  TEXT,
    * 
    * paper       ENUM('a4', 'letter') DEFAULT 'letter',
    * format      ENUM('4up', '2up-fridge', 'poster') DEFAULT '2up-fridge',
    * 
    * bbox_north  DOUBLE,
    * bbox_south  DOUBLE,
    * bbox_east   DOUBLE,
    * bbox_west   DOUBLE,
    * 
    * created     DATETIME,
    * privacy     ENUM('public', 'unlisted') DEFAULT 'public',
    */
    function set_map($ctx, $args)
    {
        $_user_id = sprintf('%d', $args['user_id']);
        
        $_place_name = mysql_real_escape_string($args['place_name'], $ctx->db);
        $_place_lat = sprintf('%.6f', $args['place_lat']);
        $_place_lon = sprintf('%.6f', $args['place_lon']);

        $_emergency = mysql_real_escape_string($args['emergency'], $ctx->db);
        $_note_full = mysql_real_escape_string($args['note_full'], $ctx->db);
        $_note_short = mysql_real_escape_string($args['note_short'], $ctx->db);

        $_paper = mysql_real_escape_string($args['paper'], $ctx->db);
        $_format = mysql_real_escape_string($args['format'], $ctx->db);

        $_bbox_north = sprintf('%.6f', $args['bbox_north']);
        $_bbox_south = sprintf('%.6f', $args['bbox_south']);
        $_bbox_east = sprintf('%.6f', $args['bbox_east']);
        $_bbox_west = sprintf('%.6f', $args['bbox_west']);

        $_privacy = mysql_real_escape_string($args['privacy'], $ctx->db);
        
        $q = "INSERT INTO maps
              SET user_id    = {$_user_id},
                  place_name = '{$_place_name}',
                  place_lat  = {$_place_lat},
                  place_lon  = {$_place_lon},
                  emergency  = '{$_emergency}',
                  note_full  = '{$_note_full}',
                  note_short = '{$_note_short}',
                  paper      = '{$_paper}',
                  format     = '{$_format}',
                  bbox_north = {$_bbox_north},
                  bbox_south = {$_bbox_south},
                  bbox_east  = {$_bbox_east},
                  bbox_west  = {$_bbox_west},
                  created    = NOW(),
                  privacy    = '{$_privacy}'";

        if($res = mysql_query($q, $ctx->db))
            return mysql_insert_id($ctx->db);

        return null;
    }

   /**
    * id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    * user_id INT UNSIGNED NOT NULL,
    * map_id  INT UNSIGNED NOT NULL,
    * 
    * name    TINYTEXT,
    * email   TINYTEXT,
    * 
    * sent    DATETIME,
    */
    function set_recipient($ctx, $args)
    {
        $_user_id = sprintf('%d', $args['user_id']);
        $_map_id = sprintf('%d', $args['map_id']);
    
        $_name = mysql_real_escape_string($args['name'], $ctx->db);
        $_email = mysql_real_escape_string($args['email'], $ctx->db);
    
        $q = "INSERT INTO recipients
              SET user_id = {$_user_id},
                  map_id  = {$_map_id},
                  name    = '{$_name}',
                  email   = '{$_email}',
                  sent    = NULL";

        if($res = mysql_query($q, $ctx->db))
            return mysql_insert_id($ctx->db);
        
        return null;
    }
    
   /**
    * {
    *   sender: { name: ___, email: ___ }
    *   place:
    *   {
    *     name: ___,
    *     location: [ ___, ___ ],
    *     emergency: ___,
    *     full-note: ___,
    *     short-note: ___
    *   },
    *   map:
    *   {
    *     privacy: ___,
    *     bounds: [ ___, ___, ___, ___ ],
    *     paper: ___,
    *     format: ___
    *   },
    *   recipients:
    *   [
    *     { name: ___, email: ___ },
    *     { name: ___, email: ___ },
    *     ...
    *   ]
    * }
    */
    function add_map($ctx, $args)
    {
        $commit_ok = false;
        mysql_query('BEGIN', $ctx->db);

        $user_id = getset_user($ctx, $args['sender']);
        
        if($user_id)
        {
            $map_args = array(
                'user_id' => $user_id,
    
                'place_name' => $args['place']['name'],
                'place_lat' => $args['place']['location'][0],
                'place_lon' => $args['place']['location'][1],
    
                'emergency' => $args['place']['emergency'],
                'note_full' => $args['place']['full-note'],
                'note_short' => $args['place']['short-note'],
    
                'paper' => $args['map']['paper'],
                'format' => $args['map']['format'],
    
                'bbox_north' => $args['map']['bounds'][0],
                'bbox_south' => $args['map']['bounds'][2],
                'bbox_east' => $args['map']['bounds'][1],
                'bbox_west' => $args['map']['bounds'][3],
    
                'privacy' => $args['map']['privacy']
            );
            
            $map_id = set_map($ctx, $map_args);
            
            if($map_id)
            {
                $commit_ok = true;
            
                foreach($args['recipients'] as $r => $recipient)
                {
                    $recipient['user_id'] = $user_id;
                    $recipient['map_id'] = $map_id;
                    $recipient_id = set_recipient($ctx, $recipient);
                    
                    if(!$recipient_id)
                        $commit_ok = false;
                }
            }
        }
        
        mysql_query($commit_ok ? 'COMMIT' : 'ROLLBACK');
        return $commit_ok ? $map_id : null;
    }
    
   /**
    *
    */
    function map_row2feature($map_row)
    {
        $id = intval($map_row['id']);
        $lon = floatval($map_row['place_lon']);
        $lat = floatval($map_row['place_lat']);

        unset($map_row['id']);
        unset($map_row['place_lon']);
        unset($map_row['place_lat']);
        
        $feature = array(
            'id' => $id,
            'type' => 'Feature',
            'geometry' => array(
                'type' => 'Point',
                'coordinates' => array($lon, $lat)
            ),
            'properties' => array()
        );
        
        $feature['properties'] = $map_row;
        return $feature;
    }
    
   /**
    *
    */
    function get_map(&$ctx, $id)
    {
        $_id = sprintf('%d', $id);
        
        $q = "SELECT id,
                     paper, format,
                     place_lat, place_lon,
                     emergency, place_name,
                     note_full, note_short,
                     created, privacy
              FROM maps
              WHERE id = {$_id}";

        if($res = mysql_query($q, $ctx->db))
        {
            if($row = mysql_fetch_assoc($res))
            {
                return array(
                    'type' => 'FeatureCollection',
                    'features' => array(map_row2feature($row)),
                );
            }
        }
        
        return null;
    }
    
   /**
    *
    */
    function get_maps(&$ctx, $args)
    {
        $_count = sprintf('%d', $args['count']);
        $_offset = sprintf('%d', $args['offset']);
        
        $q = "SELECT id,
                     paper, format,
                     place_lat, place_lon,
                     emergency, place_name,
                     note_full, note_short,
                     created, privacy
              FROM maps
              WHERE privacy = 'public'
              ORDER BY created DESC
              LIMIT {$_count} OFFSET {$_offset}";

        if($res = mysql_query($q, $ctx->db))
        {
            $features = array();
            $bbox = array(180, 90, -180, -90);
        
            while($row = mysql_fetch_assoc($res))
            {
                $feature = map_row2feature($row);
                $features[] = $feature;
                
                list($lon, $lat) = $feature['geometry']['coordinates'];
                
                $bbox[0] = min($bbox[0], $lon);
                $bbox[1] = min($bbox[1], $lat);
                $bbox[2] = max($bbox[2], $lon);
                $bbox[3] = max($bbox[3], $lat);
            }
            
            return array(
                'type' => 'FeatureCollection',
                'bbox' => count($features) ? $bbox : array(),
                'features' => $features,
            );
        }
        
        return null;
    }

?>

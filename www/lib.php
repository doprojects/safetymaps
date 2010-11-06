<?php

    require_once 'smarty/Smarty.class.php';

    class Context
    {
        // Database connection
        var $db;

        // Smarty instance
        var $sm;
        
        // List of available print formats
        var $formats = array('4up', '2up-fridge', 'poster');
        
        // List of available paper sizes
        var $papers = array('a4', 'letter');
        
        function Context(&$db_link, &$smarty)
        {
            $this->db =& $db_link;
            $this->sm =& $smarty;

            $this->sm->assign('paper_formats', $this->paper_formats());
        }
        
        function close()
        {
            mysql_close($this->db);
        }
        
        function paper_formats()
        {
            $pfs = array();
            
            foreach($this->papers as $paper)
                foreach($this->formats as $format)
                    $pfs[] = array($paper, $format);

            return $pfs;
        }
    }
    
    function &default_context()
    {
        $db = mysql_connect(MYSQL_HOSTNAME, MYSQL_USERNAME, MYSQL_PASSWORD);
        mysql_select_db(MYSQL_DATABASE, $db);
        
        $sm = new Smarty();

        $sm->compile_dir = join(DIRECTORY_SEPARATOR, array(dirname(__FILE__), 'templates', 'cache'));
        $sm->cache_dir = join(DIRECTORY_SEPARATOR, array(dirname(__FILE__), 'templates', 'cache'));

        $sm->template_dir = join(DIRECTORY_SEPARATOR, array(dirname(__FILE__), 'templates'));
        $sm->config_dir = join(DIRECTORY_SEPARATOR, array(dirname(__FILE__), 'templates'));
        
       /*
        // later perhaps
        $sm->assign('domain', get_domain_name());
        $sm->assign('base_href', get_base_href());
        */
        $sm->assign('base_dir', get_base_dir());
        $sm->register_modifier('nice_date', 'nice_date');

        $sm->assign('constants', get_defined_constants());
        $sm->assign('request', array('get' => $_GET, 'uri' => $_SERVER['REQUEST_URI']));
        
        $ctx = new Context($db, $sm);
        
        return $ctx;
    }
    
   /*
    // later perhaps
    function get_domain_name()
    {
        if(php_sapi_name() == 'cli')
            return CLI_DOMAIN_NAME;
        
        return $_SERVER['SERVER_NAME'];
    }
    
    function get_base_href()
    {
        if(php_sapi_name() == 'cli')
            return '';
        
        $query_pos = strpos($_SERVER['REQUEST_URI'], '?');
        
        return ($query_pos === false) ? $_SERVER['REQUEST_URI']
                                      : substr($_SERVER['REQUEST_URI'], 0, $query_pos);
    }
    */
    
    function get_base_dir()
    {
        if(php_sapi_name() == 'cli')
            return CLI_BASE_DIRECTORY;
        
        return rtrim(str_replace(' ', '%20', dirname($_SERVER['SCRIPT_NAME'])), DIRECTORY_SEPARATOR);
    }
    
    function nice_date($ts)
    {
        return date('j M Y', $ts);
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
                  waiting    = 0,
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
    * waiting TEXT,
    * 
    * queued  DATETIME,
    * sent    DATETIME,
    */
    function add_recipient(&$ctx, $args)
    {
        $_user_id = sprintf('%d', $args['user_id']);
        $_map_id = sprintf('%d', $args['map_id']);
    
        $_name = mysql_real_escape_string($args['name'], $ctx->db);
        $_email = mysql_real_escape_string($args['email'], $ctx->db);
        
        $waiting = array();
        
        foreach($ctx->paper_formats() as $paper_format)
            $waiting[] = join('-', $paper_format);
        
        $_waiting = mysql_real_escape_string(join(' ', $waiting), $ctx->db);
    
        $q0 = "UPDATE maps
               SET waiting = waiting + 1
               WHERE id = {$_map_id}";
        
        $q1 = "INSERT INTO recipients
               SET user_id = {$_user_id},
                   map_id  = {$_map_id},
                   name    = '{$_name}',
                   email   = '{$_email}',
                   waiting = '{$_waiting}',
                   queued  = NOW(),
                   sent    = NULL";

        if(mysql_query($q0, $ctx->db) && mysql_query($q1, $ctx->db))
            return mysql_insert_id($ctx->db);
        
        return null;
    }
    
   /**
    */
    function advance_recipient(&$ctx, $id, $paper, $format)
    {
        $_recipient_id = sprintf('%d', $id);
    
        $q = "SELECT map_id, waiting
              FROM recipients
              WHERE id = {$_recipient_id}";
        
        $res = mysql_query($q, $ctx->db);
        
        if(!$res)
            return false;
        
        $row = mysql_fetch_assoc($res);
        
        if(!$row)
            return false;
        
        $_map_id = sprintf('%d', $row['map_id']);
        
        // now we know it's a real recipient.
        
        $token = "{$paper}-{$format}";
        $tokens = preg_split('/\s+/', $row['waiting']);
        
        if(!in_array($token, $tokens))
            return false;
        
        $o = array_search($token, $tokens);
        array_splice($tokens, $o, 1);
    
        // found the right paper/format combination in recipient.waiting, removed it.
        
        $_waiting = mysql_real_escape_string(join(' ', $tokens), $ctx->db);
        
        $q = "UPDATE recipients
              SET waiting = '{$_waiting}'
              WHERE id = {$_recipient_id}";
        
        $res = mysql_query($q, $ctx->db);
        
        if(!$res)
            return false;
    
        // updated recipient.waiting with the new list of paper/format combos.
        
        if(count($tokens) == 0)
        {
            $q0 = "UPDATE maps
                   SET waiting = waiting - 1
                   WHERE id = {$_map_id}";
            
            $q1 = "UPDATE recipients
                   SET waiting = NULL, sent = NOW()
                   WHERE id = {$_recipient_id}";
    
            // decrementing maps.waiting because this recipient is all finished.
            
            $r0 = mysql_query($q0, $ctx->db);
            $r1 = mysql_query($q1, $ctx->db);
            
            if(!$r0 || !$r1)
                return false;
        }
        
        return true;
    }
    
   /**
    * Add a new map and return its ID if everything worked out.
    *
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
                    if(empty($recipient['name']) || empty($recipient['email']))
                        continue;

                    $recipient['user_id'] = $user_id;
                    $recipient['map_id'] = $map_id;
                    $recipient_id = add_recipient($ctx, $recipient);
                    
                    if(!$recipient_id)
                        $commit_ok = false;
                }
            }
        }
        
        if(!$commit_ok)
        {
            mysql_query('ROLLBACK');
            return null;
        }

        mysql_query('COMMIT');
        return $map_id;
    }
    
   /**
    * Convert a list of map row as from a database query to a GeoJSON feature collection.
    */
    function map_rows2collection($map_rows)
    {
        $features = array();
        $bbox = array(180, 90, -180, -90);
    
        foreach($map_rows as $row)
        {
            $feature = map_row2feature($row);
            $features[] = $feature;
            
            $bbox[0] = min($bbox[0], $row['place_lon']);
            $bbox[1] = min($bbox[1], $row['place_lat']);
            $bbox[2] = max($bbox[2], $row['place_lon']);
            $bbox[3] = max($bbox[3], $row['place_lat']);
        }
        
        return array(
            'type' => 'FeatureCollection',
            'bbox' => count($features) ? $bbox : array(),
            'features' => $features,
        );
    }
    
   /**
    * Convert a map row as from a database query to a GeoJSON feature.
    */
    function map_row2feature($map_row)
    {
        $id = $map_row['id'];
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
            'properties' => null
        );
        
        $feature['properties'] = $map_row;
        return $feature;
    }
    
   /**
    * Get a map by ID, return a GeoJSON feature collection array.
    */
    function get_map(&$ctx, $id)
    {
        $_id = sprintf('%d', $id);
        
        $q = "SELECT id, user_id,
                     paper, format,
                     place_lat, place_lon,
                     emergency, place_name,
                     note_full, note_short,
                     bbox_west, bbox_south, bbox_east, bbox_north,
                     UNIX_TIMESTAMP(created) AS created_unixtime,
                     created, privacy
              FROM maps
              WHERE id = {$_id}";

        if($res = mysql_query($q, $ctx->db))
        {
            if($row = mysql_fetch_assoc($res))
            {
                $row['user'] = get_user($ctx, $row['user_id']);
                $row['recipients'] = get_recipients($ctx, array('map_id' => $row['id']));
                
                $row['place_lat'] = floatval($row['place_lat']);
                $row['place_lon'] = floatval($row['place_lon']);
                $row['bbox_west'] = floatval($row['bbox_west']);
                $row['bbox_east'] = floatval($row['bbox_east']);
                $row['bbox_south'] = floatval($row['bbox_south']);
                $row['bbox_north'] = floatval($row['bbox_north']);
            
                unset($row['user_id']);
                
                return $row;
            }
        }
        
        return null;
    }
    
   /**
    * Get a list of maps, return a GeoJSON feature collection array.
    */
    function get_maps(&$ctx, $args)
    {
        $_count = sprintf('%d', $args['count']);
        $_offset = sprintf('%d', $args['offset']);
        
        $where_clauses = array("privacy = 'public'");
        
        if(is_array($args['where']))
        {
            list($lon1, $lat1, $lon2, $lat2) = $args['where'];

            $_minlon = sprintf('%.6f', min($lon1, $lon2));
            $_minlat = sprintf('%.6f', min($lat1, $lat2));
            $_maxlon = sprintf('%.6f', max($lon1, $lon2));
            $_maxlat = sprintf('%.6f', max($lat1, $lat2));
            
            $where_clauses[] = "(place_lon BETWEEN {$_minlon} AND {$_maxlon})";
            $where_clauses[] = "(place_lat BETWEEN {$_minlat} AND {$_maxlat})";
        }
        
        $_where_clause = join(' AND ', $where_clauses);
        
        $q = "SELECT id, user_id,
                     place_lat, place_lon,
                     emergency, place_name,
                     UNIX_TIMESTAMP(created) AS created_unixtime,
                     note_short, created
              FROM maps
              WHERE {$_where_clause}
              ORDER BY created DESC
              LIMIT {$_count} OFFSET {$_offset}";

        if($res = mysql_query($q, $ctx->db))
        {
            $rows = array();
            
            while($row = mysql_fetch_assoc($res))
            {
                $row['user'] = get_user($ctx, $row['user_id']);
                
                $row['place_lat'] = floatval($row['place_lat']);
                $row['place_lon'] = floatval($row['place_lon']);

                unset($row['user_id']);
            
                $rows[] = $row;
            }
            
            return $rows;
        }
        
        return null;
    }
    
   /**
    * Get a single user by ID, return a simple assoc. array.
    */
    function get_user(&$ctx, $id, $include_email=false)
    {
        $_id = sprintf('%d', $id);
        $_columns = $include_email ? 'id, name, email' : 'id, name';
        
        $q = "SELECT {$_columns}
              FROM users
              WHERE id = {$_id}";

        if($res = mysql_query($q, $ctx->db))
        {
            if($row = mysql_fetch_assoc($res))
                return $row;
        }
        
        return null;
    }
    
   /**
    * Get a list of recipients, return a simple array.
    */
    function get_recipients(&$ctx, $args)
    {
        $_map_id = $args['map_id'] ? sprintf('%d', $args['map_id']) : null;

        $q = "SELECT id, name, sent
              FROM recipients
              WHERE map_id = {$_map_id}
              ORDER BY id";

        if($res = mysql_query($q, $ctx->db))
        {
            $recipients = array();
            
            while($row = mysql_fetch_assoc($res))
                $recipients[] = $row;
            
            return $recipients;
        }
        
        return null;
    }
    
   /**
    * Get a single recipient, return a simple assoc. array.
    */
    function get_recipient(&$ctx, $id, $include_email=false)
    {
        $_id = sprintf('%d', $id);
        $_columns = $include_email ? 'id, name, email, sent, map_id' : 'id, name, sent, map_id';
        
        $q = "SELECT {$_columns}
              FROM recipients
              WHERE id = {$_id}";

        if($res = mysql_query($q, $ctx->db))
            if($row = mysql_fetch_assoc($res))
                return $row;
        
        return null;
    }
    
   /**
    * Save a PDF and return its complete local filename, or null in case of failure.
    */
    function save_pdf($map_id, $recipient_id, $paper, $format, $src_filename, $dest_dirname)
    {
        $map_dirname = "{$dest_dirname}/{$map_id}";
        @mkdir($map_dirname);
        @chmod($map_dirname, 0775);
        
        $pdf_dirname = "{$map_dirname}/{$recipient_id}";
        @mkdir($pdf_dirname);
        @chmod($pdf_dirname, 0775);
        
        $pdf_filename = "{$pdf_dirname}/{$paper}-{$format}.pdf";
        $pdf_content = file_get_contents($src_filename);
    
        $fp = fopen($pdf_filename, 'w');
        fwrite($fp, $pdf_content);
        fclose($fp);
        chmod($pdf_filename, 0664);
        
        return file_exists($pdf_filename) ? realpath($pdf_filename) : null;
    }
    
   /**
    * Send email to a recipient to notify them that a PDF file is available.
    */
    function send_mail(&$ctx, $recipient_id, $pdf_href)
    {
        $recipient = get_recipient($ctx, $recipient_id, true);
        $map = get_map($ctx, $recipient['map_id']);
        $user = get_user($ctx, $map['user']['id'], true);
        
        $mm = new Mail_mime("\n");
    
        $mm->setFrom("{$user['name']} <info@safety-maps.org>");
        $mm->setSubject("Safety Maps Test");
    
        $mm->setTXTBody("Made new map for {$recipient['name']} <{$recipient['email']}>: {$pdf_href}");
        $mm->setHTMLBody("Made new map for {$recipient['name']} ({$recipient['email']}): {$pdf_href}");
    
        $body = $mm->get();
        $head = $mm->headers(array('To' => $recipient['email'],
                                   'Reply-To' => $user['email']));
    
        $m =& Mail::factory('smtp', array('auth' => true,
                                          'host' => SMTP_HOST,
                                          'port' => SMTP_PORT,
                                          'username' => SMTP_USER,
                                          'password' => SMTP_PASS));
        
        return $m->send($recipient['email'], $head, $body);
    }

?>

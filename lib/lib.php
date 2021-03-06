<?php

    require_once 'smarty/Smarty.class.php';

    require_once 'PEAR.php';
    require_once 'Mail/mime.php';
    require_once 'Net/SMTP.php';
    
    define('MYSQL_ER_DUP_ENTRY', 1062);
    define('SMTP_BAD_REPLY', 500);

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
        
        function Context(&$db_link, &$smarty, $is_admin)
        {
            $this->db =& $db_link;
            $this->sm =& $smarty;
            $this->admin = $is_admin;

            $this->sm->assign('paper_formats', $this->paper_formats());
            $this->sm->assign('formats', $this->formats);
            $this->sm->assign('papers', $this->papers);
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
        
        list($is_admin) = isset($_COOKIE['userdata']) ? read_userdata($_COOKIE['userdata']) : false;
        
        $sm = new Smarty();

        $sm->compile_dir = join(DIRECTORY_SEPARATOR, array(dirname(__FILE__), '..', 'templates', 'cache'));
        $sm->cache_dir = join(DIRECTORY_SEPARATOR, array(dirname(__FILE__), '..', 'templates', 'cache'));

        $sm->template_dir = join(DIRECTORY_SEPARATOR, array(dirname(__FILE__), '..', 'templates'));
        $sm->config_dir = join(DIRECTORY_SEPARATOR, array(dirname(__FILE__), '..', 'templates'));
        
       /*
        // later perhaps
        $sm->assign('base_href', get_base_href());
        */
        $sm->assign('base_dir', get_base_dir());
        $sm->assign('domain', get_domain_name());
        $sm->register_modifier('value_or_unacceptable_attr', 'value_or_unacceptable_attr');
        $sm->register_modifier('nice_date', 'nice_date');
        $sm->register_modifier('nice_relativetime', 'nice_relativetime');

        $sm->assign('constants', get_defined_constants());
        $sm->assign('request', array('method' => $_SERVER['REQUEST_METHOD'], 'get' => $_GET, 'post' => $_POST, 'uri' => $_SERVER['REQUEST_URI']));
        $sm->assign('is_admin', $is_admin);
        
        $ctx = new Context($db, $sm, $is_admin);
        
        return $ctx;
    }
    
   /*
    // later perhaps
    function get_base_href()
    {
        if(php_sapi_name() == 'cli')
            return '';
        
        $query_pos = strpos($_SERVER['REQUEST_URI'], '?');
        
        return ($query_pos === false) ? $_SERVER['REQUEST_URI']
                                      : substr($_SERVER['REQUEST_URI'], 0, $query_pos);
    }
    */
    
     function get_domain_name()
    {
        if(php_sapi_name() == 'cli')
            return CLI_DOMAIN_NAME;
        
        return $_SERVER['SERVER_NAME'];
    }
    
   function get_base_dir()
    {
        if(php_sapi_name() == 'cli')
            return CLI_BASE_DIRECTORY;
        
        #
        # Naive truth here.
        #
        $abs_root_url = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    
        #
        # Use __FILE__ and SCRIPT_FILENAME to figure out where we currently
        # are in relation to the installed root of Dotspotting. We know lib.php
        # is in www/, we know stuff is in www/, work from there.
        #
        # If this was Python we'd just us built-ins from os.path.
        #
        $root_dirname = dirname(dirname(__FILE__));
        $script_dirname = dirname($_SERVER['SCRIPT_FILENAME']);
        
        $abs_root_url = substr($abs_root_url, 0, strlen($abs_root_url) - strlen(substr($script_dirname, strlen("{$root_dirname}/www"))));
    
        return $abs_root_url;
    }
    
    function write_userdata($is_admin)
    {
        $userdata = array('is_admin' => (bool) $is_admin);
        $encoded_value = json_encode($userdata);
        $signed_string = $encoded_value.' '.md5($encoded_value.ADMIN_SECRET);
        
        return $signed_string;
    }
    
   /**
    * Return userdata (is_admin) based on a signed string.
    * @param    string  $signed_string  JSON string, generally from a cookie, signed with an MD5 hash
    * @return   string  Array with is_admin.
    */
    function read_userdata($signed_string)
    {
        if(preg_match('/^(.+) (\w{32})$/', $signed_string, $m))
        {
            list($encoded_value, $found_signature) = array($m[1], $m[2]);
            $expected_signature = md5($encoded_value.ADMIN_SECRET);
            
            if($expected_signature == $found_signature)
            {
                // signature checks out
                $userdata = json_decode($encoded_value, true);
                $is_admin = empty($userdata['is_admin']) ? false : $userdata['is_admin'];
                return array($is_admin);
            }
        }

        //error_log("found no userdata in: {$signed_string}\n", 3, dirname(__FILE__).'/../tmp/log.txt');
        return array(false);
    }

    function nice_date($ts)
    {
        return date('j M Y', $ts);
    }
    
    function nice_relativetime($seconds)
    {
        switch(true)
        {
            case abs($seconds) <= 90:
                return 'moments ago';

            case abs($seconds) <= 90 * 60:
                return round(abs($seconds) / 60).' minutes ago';

            case abs($seconds) <= 36 * 60 * 60:
                return round(abs($seconds) / (60 * 60)).' hours ago';

            default:
                return round(abs($seconds) / (24 * 60 * 60)).' days ago';
        }
    }
    
    function value_or_unacceptable_attr($value, $method)
    {
        if($method != 'POST')
            return 'class="required"';

        return $value
            ? sprintf('value="%s"', htmlspecialchars($value))
            : 'class="required unacceptable"';
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
    
    function generate_id($len)
    {
        $chars = 'qwrtpsdfghklzxcvbnm23456789';
        $id = '';
        
        while(strlen($id) < $len)
            $id .= substr($chars, rand(0, strlen($chars) - 1), 1);

        return $id;
    }
    
   /**
    * id          VARCHAR(16) PRIMARY KEY,
    * user_id     INT UNSIGNED NOT NULL,
    * 
    * place_name  TINYTEXT,
    * place_lat   DOUBLE,
    * place_lon   DOUBLE,
    * 
    * emergency   TINYTEXT,
    * note_full   TEXT,
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

        $_bbox_north = sprintf('%.6f', $args['bbox_north']);
        $_bbox_south = sprintf('%.6f', $args['bbox_south']);
        $_bbox_east = sprintf('%.6f', $args['bbox_east']);
        $_bbox_west = sprintf('%.6f', $args['bbox_west']);

        $_privacy = mysql_real_escape_string($args['privacy'], $ctx->db);
        
        // try a bunch of possible ids varying in length from 3 to 8 chars
        foreach(range(5*4, 9*4-1) as $len)
        {
            $map_id = generate_id(floor($len / 4));
            $_map_id = mysql_real_escape_string($map_id, $ctx->db);
            
            $q = "INSERT INTO maps
                  SET id         = '{$_map_id}',
                      user_id    = {$_user_id},
                      place_name = '{$_place_name}',
                      place_lat  = {$_place_lat},
                      place_lon  = {$_place_lon},
                      emergency  = '{$_emergency}',
                      note_full  = '{$_note_full}',
                      bbox_north = {$_bbox_north},
                      bbox_south = {$_bbox_south},
                      bbox_east  = {$_bbox_east},
                      bbox_west  = {$_bbox_west},
                      waiting    = 0,
                      created    = NOW(),
                      privacy    = '{$_privacy}'";
    
            // did it work?
            if($res = mysql_query($q, $ctx->db))
                return $map_id;
            
            // did it not work because of a duplicate map_id?
            if(mysql_errno($ctx->db) == MYSQL_ER_DUP_ENTRY)
                continue;

            // yikes, why didn't it work?
            break;
        }

        return null;
    }

   /**
    * id      VARCHAR(16) PRIMARY KEY,
    * user_id INT UNSIGNED NOT NULL,
    * map_id  VARCHAR(16) NOT NULL,
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
        $_map_id = mysql_real_escape_string($args['map_id'], $ctx->db);
    
        $_name = mysql_real_escape_string($args['name'], $ctx->db);
        $_email = mysql_real_escape_string($args['email'], $ctx->db);
        
        $waiting = array();
        
        foreach($ctx->paper_formats() as $paper_format)
            $waiting[] = join('-', $paper_format);
        
        $_waiting = mysql_real_escape_string(join(' ', $waiting), $ctx->db);
        
        // try a bunch of possible ids varying in length from 3 to 8 chars
        foreach(range(5*4, 9*4-1) as $len)
        {
            $recipient_id = generate_id(floor($len / 4));
            $_recipient_id = mysql_real_escape_string($recipient_id, $ctx->db);
            
            $q = "INSERT INTO recipients
                  SET id      = '{$_recipient_id}',
                      user_id = {$_user_id},
                      map_id  = '{$_map_id}',
                      name    = '{$_name}',
                      email   = '{$_email}',
                      waiting = '{$_waiting}',
                      queued  = NOW(),
                      sent    = NULL";
    
            // did it work?
            if($res = mysql_query($q, $ctx->db))
            {
                $q = "UPDATE maps
                      SET waiting = waiting + 1
                      WHERE id = '{$_map_id}'";
                
                // okay how about this one?
                if(mysql_query($q, $ctx->db))
                    return $recipient_id;

                // yikes, why didn't it work?
                break;
            }
            
            // did it not work because of a duplicate recipient_id?
            if(mysql_errno($ctx->db) == MYSQL_ER_DUP_ENTRY)
                continue;

            // yikes, why didn't it work?
            break;
        }
        
        return null;
    }
    
   /**
    * Each recipient must have a full complement of maps generated.
    * This function notes the generation of a single map, eventually
    * decrementing the waiting count on the recipient map.
    *
    * Return true if the recipient was advanced, null if not, and false on error.
    */
    function advance_recipient(&$ctx, $recipient_id, $paper, $format)
    {
        $_recipient_id = mysql_real_escape_string($recipient_id, $ctx->db);
    
        $q = "SELECT map_id, waiting
              FROM recipients
              WHERE id = '{$_recipient_id}'";
        
        $res = mysql_query($q, $ctx->db);
        
        if(!$res)
            return false;
        
        $row = mysql_fetch_assoc($res);
        
        if(!$row)
            return false;
        
        $_map_id = mysql_real_escape_string($row['map_id'], $ctx->db);
        
        // now we know it's a real recipient.
        
        $token = "{$paper}-{$format}";
        $tokens = preg_split('/\s+/', $row['waiting']);
        
        // return null here, because this isn't
        // quite a failure but maybe just a repeat
        if(!in_array($token, $tokens))
            return null;
        
        $o = array_search($token, $tokens);
        array_splice($tokens, $o, 1);
    
        // found the right paper/format combination in recipient.waiting, removed it.
        
        $_waiting = mysql_real_escape_string(join(' ', $tokens), $ctx->db);
        
        $q = "UPDATE recipients
              SET waiting = '{$_waiting}'
              WHERE id = '{$_recipient_id}'";
        
        $res = mysql_query($q, $ctx->db);
        
        if(!$res)
            return false;
    
        // updated recipient.waiting with the new list of paper/format combos.
        
        if(count($tokens) == 0)
        {
            $q0 = "UPDATE maps
                   SET waiting = IF(waiting > 0, waiting - 1, 0)
                   WHERE id = '{$_map_id}'";
            
            $q1 = "UPDATE recipients
                   SET waiting = NULL, sent = NOW()
                   WHERE id = '{$_recipient_id}'";
    
            // decrementing maps.waiting because this recipient is all finished.
            
            $r0 = mysql_query($q0, $ctx->db);
            $r1 = mysql_query($q1, $ctx->db);
            
            if(!$r0 || !$r1)
                return false;
        
            $sentmail = send_mail($ctx, $recipient_id);
            
            if($sentmail === SMTP_BAD_REPLY)
                error_recipient($ctx, $recipient_id, $paper, $format);
            
            if(PEAR::isError($sentmail))
                return false;
        }
        
        return true;
    }
    
   /**
    * Each recipient is given a certain amount of time and a certain number of
    * errors before they're given up on. This function is called after an error
    * has occured, and if it finds that the recipient has seen too many errors
    * for too long, it'll give up on the recipient permanently.
    *
    * Return true if we gave up on the recipient, null if not, and false on error.
    */
    function error_recipient(&$ctx, $recipient_id, $paper, $format)
    {
        $_recipient_id = mysql_real_escape_string($recipient_id, $ctx->db);
    
        $q = "SELECT map_id, errors,
                     UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(created) AS seconds_old
              FROM recipients
              WHERE id = '{$_recipient_id}'";
        
        $res = mysql_query($q, $ctx->db);
        
        if(!$res)
            return false;
        
        $row = mysql_fetch_assoc($res);
        
        if(!$row)
            return false;
        
        $map_id = $row['map_id'];
        $errors = $row['errors'];
        $minutes_old = $row['seconds_old'] / 60;

        $_map_id = mysql_real_escape_string($map_id, $ctx->db);
        
        // now we know it's a real recipient.
        
        $q = "UPDATE recipients
              SET errors = errors + 1
              WHERE id = '{$_recipient_id}'";

        if(!mysql_query($q, $ctx->db))
            return false;

        if($minutes_old <= 10 && $errors <= count($ctx->paper_formats()) * 3)
        {
            // not enough to errors to fail this participant just yet.
            return null;
        }

        error_log(sprintf('Giving up on recipient %s, map %s: %d errors, %d minutes old',
                          $recipient_id, $map_id,
                          $errors, $minutes_old));
        
        $q0 = "UPDATE maps
               SET waiting = IF(waiting > 0, waiting - 1, 0)
               WHERE id = '{$_map_id}'";
        
        $q1 = "UPDATE recipients
               SET failed = NOW()
               WHERE id = '{$_recipient_id}'";

        // decrementing maps.waiting because this recipient won't happen.
        
        $r0 = mysql_query($q0, $ctx->db);
        $r1 = mysql_query($q1, $ctx->db);
        
        if(!$r0 || !$r1)
            return false;
        
        return true;
    }
    
   /**
    * Add a new map and return its ID if everything worked out.
    *
    * Return false if an error occured, requiring a rollback.
    *
    * {
    *   sender: { name: ___, email: ___ }
    *   place:
    *   {
    *     name: ___,
    *     location: [ ___, ___ ],
    *     emergency: ___,
    *     note_full: ___,
    *     short-note: ___
    *   },
    *   map:
    *   {
    *     privacy: ___,
    *     bounds: [ ___, ___, ___, ___ ]
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
        $user_id = getset_user($ctx, $args['sender']);
        $sender = $args['sender'];
        
        if(!$user_id)
            return false;
        
        $map_args = array(
            'user_id' => $user_id,

            'place_name' => $args['place']['name'],
            'place_lat' => $args['place']['location'][0],
            'place_lon' => $args['place']['location'][1],

            'emergency' => $args['place']['emergency'],
            'note_full' => $args['place']['note_full'],

            'bbox_north' => $args['map']['bounds'][0],
            'bbox_south' => $args['map']['bounds'][2],
            'bbox_east' => $args['map']['bounds'][1],
            'bbox_west' => $args['map']['bounds'][3],

            'privacy' => $args['map']['privacy']
        );
        
        $map_id = set_map($ctx, $map_args);
        
        if(!$map_id)
            return false;
        
        $send_to_sender = true;
    
        foreach($args['recipients'] as $r => $recipient)
        {
            if(empty($recipient['name']) || empty($recipient['email']))
                continue;

            $recipient['user_id'] = $user_id;
            $recipient['map_id'] = $map_id;
            $recipient_id = add_recipient($ctx, $recipient);
            
            if(!$recipient_id)
                return false;
            
            if($recipient['email'] == $sender['email'])
                $send_to_sender = false;
        }

        if($send_to_sender)
        {
            $sender['user_id'] = $user_id;
            $sender['map_id'] = $map_id;
        
            // not just the president of the hair club for men
            $recipient_id = add_recipient($ctx, $sender);
            
            if(!$recipient_id)
                return false;
        }
        
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
    function get_map(&$ctx, $id, $expose_email=false)
    {
        $_id = mysql_real_escape_string($id, $ctx->db);
        
        $q = "SELECT id, user_id,
                     place_lat, place_lon,
                     emergency, place_name,
                     note_full,
                     bbox_west, bbox_south, bbox_east, bbox_north,
                     UNIX_TIMESTAMP(created) AS created_unixtime,
                     created, privacy, waiting
              FROM maps
              WHERE id = '{$_id}'";

        if($res = mysql_query($q, $ctx->db))
        {
            if($row = mysql_fetch_assoc($res))
            {
                $row['user'] = get_user($ctx, $row['user_id'], $expose_email);
                $row['recipients'] = get_recipients($ctx, $row['id'], $expose_email);
                
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
        
        $where_clauses = array('1');
        
        if($args['privacy'] == 'any') {
            // do nothing
        
        } elseif($args['privacy']) {
            $_privacy = mysql_real_escape_string($args[']privacy'], $ctx->db);
            $where_clauses[] = "privacy = '{$_privacy}'";
        
        } else {
            $where_clauses[] = "privacy = 'public'";
        }
        
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
                     note_full,
                     bbox_west, bbox_south, bbox_east, bbox_north,
                     UNIX_TIMESTAMP(created) AS created_unixtime,
                     created, privacy, waiting
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
    function get_user(&$ctx, $id, $expose_email=false)
    {
        $_id = sprintf('%d', $id);

        $_columns = $expose_email
            ? 'id, name, email'
            : 'id, name, SHA1(email) AS email';
        
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
    function get_recipients(&$ctx, $map_id, $expose_email=false)
    {
        $_map_id = mysql_real_escape_string($map_id, $ctx->db);

        $_columns = $expose_email
            ? 'id, name, sent, failed, email'
            : 'id, name, sent, failed, SHA1(email) AS email';
        
        $q = "SELECT {$_columns}
              FROM recipients
              WHERE map_id = '{$_map_id}'
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
    function get_recipient(&$ctx, $id, $expose_email=false)
    {
        $_id = mysql_real_escape_string($id, $ctx->db);

        $_columns = $expose_email
            ? 'id, map_id, name, waiting, sent, failed, email'
            : 'id, map_id, name, waiting, sent, failed, SHA1(email) AS email';
        
        $q = "SELECT {$_columns}
              FROM recipients
              WHERE id = '{$_id}'";

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
    function send_mail(&$ctx, $recipient_id)
    {
        $smtp = new Net_SMTP(SMTP_HOST, SMTP_PORT, 'dev.safety-maps.org');
        
        if(PEAR::isError($smtp))
            return $smtp;
    
        $recipient = get_recipient($ctx, $recipient_id, true);
        $map = get_map($ctx, $recipient['map_id'], true);
        $user = get_user($ctx, $map['user']['id'], true);
        
        $sent_to_self = ($user['email'] == $recipient['email']);
        
        $map_href = sprintf('http://%s%s/maps.php/%s/%s',
                            get_domain_name(), get_base_dir(),
                            urlencode($map['id']), urlencode($recipient['id']));
        
        $mm = new Mail_mime(array('text_charset' => 'UTF-8', 'html_charset' => 'UTF-8', 'eol' => "\n"));
        $mm->setFrom("Safety Maps <info@safety-maps.org>");
        
        $ctx->sm->assign('map', $map);
        $ctx->sm->assign('sender', $user);
        $ctx->sm->assign('recipient', $recipient);
        $ctx->sm->assign('map_href', $map_href);
        
        if($sent_to_self)
        {
            $recipient_names = array();
            
            foreach($map['recipients'] as $recp)
                if($recp['email'] != $user['email'])
                    $recipient_names[] = $recp['name'];
            
            $end = array_splice($recipient_names, -2);
            $recipient_names[] = join(' and ', $end);
            $recipient_names = join(', ', $recipient_names);
            
            $ctx->sm->assign('recipient_names', $recipient_names);
        }
        
        $template_name = $sent_to_self ? 'sender-mail' : 'recipient-mail';
        
        $headers = $sent_to_self
            ? array('To' => $recipient['email'])
            : array('To' => $recipient['email'], 'Reply-To' => $user['email']);
        
        $mm->setSubject($sent_to_self
            ? "Your Safety Map is ready"
            : "{$user['name']} made you a Safety Map");
    
        $mm->setTXTBody($ctx->sm->fetch("{$template_name}.text.tpl"));
        $mm->setHTMLBody($ctx->sm->fetch("{$template_name}.html.tpl"));
    
        $body = $mm->get();
        $head = $mm->headers($headers);
        
        $data = 'X-The-City-Is-Here: For You To Use.';
        
        foreach($head as $k => $v)
            $data .= "\r\n{$k}: {$v}";
    
        $data .= "\r\n\r\n{$body}";
        
        $calls = array(array('connect', array(5)),
                       array('auth', array(SMTP_USER, SMTP_PASS, false, false)),
                       array('mailFrom', array('info@safety-maps.org')),
                       array('rcptTo', array($recipient['email'])),
                       array('data', array($data)),
                       array('disconnect', array()));
    
        foreach($calls as $method_args)
        {
            list($method, $args) = $method_args;
            $result = call_user_func_array(array(&$smtp, $method), $args);

            list($code, $explanation) = $smtp->getResponse();
            error_log("Mail map {$map['id']}/{$recipient['id']}, {$method}: {$code}, {$explanation}");
            
           /*
            * 500 errors seem to mean that no good will come of repeating
            * this action, so return a bad-reply code that will cause
            * advance_recipient() to call error_recipient().
            */
            if(500 <= $code && $code <= 599)
            {
                error_log("SMTP error code {$code}");
                return SMTP_BAD_REPLY;
            }
            
            if(PEAR::isError($result))
                break;
        }
        
        return $result;
    }

?>

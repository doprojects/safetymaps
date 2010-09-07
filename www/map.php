<?php

   /**
    * id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    * 
    * name    TINYTEXT,
    * email   TINYTEXT
    */
    function getset_user($db, $args)
    {
        $_name = mysql_real_escape_string($args['name'], $db);
        $_email = mysql_real_escape_string($args['email'], $db);
    
        $q = "SELECT id FROM users
              WHERE name  = '{$_name}'
                AND email = '{$_email}'";

        if($res = mysql_query($q, $db))
        {
            $row = mysql_fetch_assoc($res);
            
            if($row) {
                return $row['id'];
            
            } else {
                $q = "INSERT INTO users
                      SET name  = '{$_name}',
                          email = '{$_email}'";

                if($res = mysql_query($q, $db))
                    return mysql_insert_id($db);
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
    function set_map($db, $args)
    {
        $_user_id = sprintf('%d', $args['user_id']);
        
        $_place_name = mysql_real_escape_string($args['place_name'], $db);
        $_place_lat = sprintf('%.6f', $args['place_lat']);
        $_place_lon = sprintf('%.6f', $args['place_lon']);

        $_emergency = mysql_real_escape_string($args['emergency'], $db);
        $_note_full = mysql_real_escape_string($args['note_full'], $db);
        $_note_short = mysql_real_escape_string($args['note_short'], $db);

        $_paper = mysql_real_escape_string($args['paper'], $db);
        $_format = mysql_real_escape_string($args['format'], $db);

        $_bbox_north = sprintf('%.6f', $args['bbox_north']);
        $_bbox_south = sprintf('%.6f', $args['bbox_south']);
        $_bbox_east = sprintf('%.6f', $args['bbox_east']);
        $_bbox_west = sprintf('%.6f', $args['bbox_west']);

        $_privacy = mysql_real_escape_string($args['privacy'], $db);
        
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

        if($res = mysql_query($q, $db))
            return mysql_insert_id($db);

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
    function set_recipient($db, $args)
    {
        $_user_id = sprintf('%d', $args['user_id']);
        $_map_id = sprintf('%d', $args['map_id']);
    
        $_name = mysql_real_escape_string($args['name'], $db);
        $_email = mysql_real_escape_string($args['email'], $db);
    
        $q = "INSERT INTO recipients
              SET user_id = {$_user_id},
                  map_id  = {$_map_id},
                  name    = '{$_name}',
                  email   = '{$_email}',
                  sent    = NULL";

        if($res = mysql_query($q, $db))
            return mysql_insert_id($db);
        
        return null;
    }

    $db = mysql_connect('localhost', 'safetymaps', 's4f3tym4ps');
    mysql_select_db('safetymaps', $db);
    
    if(is_array($_POST['sender']) && is_array($_POST['place']) && is_array($_POST['map']) && is_array($_POST['recipients']))
    {
        mysql_query('BEGIN');
        $commit_ok = false;

        $user_id = getset_user($db, $_POST['sender']);
        
        if($user_id)
        {
            $map_args = array(
                'user_id' => $user_id,
    
                'place_name' => $_POST['place']['name'],
                'place_lat' => $_POST['place']['location'][0],
                'place_lon' => $_POST['place']['location'][1],
    
                'emergency' => $_POST['place']['emergency'],
                'note_full' => $_POST['place']['full-note'],
                'note_short' => $_POST['place']['short-note'],
    
                'paper' => $_POST['map']['paper'],
                'format' => $_POST['map']['format'],
    
                'bbox_north' => $_POST['map']['bounds'][0],
                'bbox_south' => $_POST['map']['bounds'][1],
                'bbox_east' => $_POST['map']['bounds'][2],
                'bbox_west' => $_POST['map']['bounds'][3],
    
                'privacy' => $_POST['map']['privacy']
            );
            
            $map_id = set_map($db, $map_args);
            
            if($map_id)
            {
                $commit_ok = true;
            
                foreach($_POST['recipients'] as $r => $recipient)
                {
                    $recipient['user_id'] = $user_id;
                    $recipient['map_id'] = $map_id;
                    $recipient_id = set_recipient($db, $recipient);
                    
                    if(!$recipient_id)
                        $commit_ok = false;
                }
            }
        }
                
        mysql_query($commit_ok ? 'COMMIT' : 'ROLLBACK');
    }
    
    mysql_close($db);

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<title>Safety Maps Post Form</title>
    <style type="text/css" title="text/css">
    <!--
        body { font: 12px/1.2 sans-serif; }
    -->
    </style>
</head>
<body>

<form method="POST" action="map.php">
    <p>
        User name <input type="text" name="sender[name]" value="Mike">
        email <input type="text" name="sender[email]" value="mike@teczno.com">
    </p>
    <p>
        Place name <input type="text" name="place[name]" value="Lake Chalet">
    </p>
    <p>
        Location
        <input type="text" name="place[location][0]" value="37.75883">
        <input type="text" name="place[location][1]" value="-122.42689">
    </p>
    <p>
        Emergency <input type="text" name="place[emergency]" value="Earthquake">
    </p>
    <p>
        Full Note
        <input type="text" name="place[full-note]" value="Out in front by the steps">
    </p>
    <p>
        Short Note
        <input type="text" name="place[short-note]" value="Out in front">
    </p>
    <p>
        Privacy
        <select name="map[privacy]">
            <option label="Public" value="public">Public</option>
            <option label="Unlisted" value="unlisted">Unlisted</option>
        </select>
    </p>
    <p>
        Bounds
        <input type="text" name="map[bounds][0]" value="37.7669">
        <input type="text" name="map[bounds][1]" value="-122.4177">
        <input type="text" name="map[bounds][2]" value="37.7565">
        <input type="text" name="map[bounds][3]" value="-122.4302">
    </p>
    <p>
        Paper
        <select name="map[paper]">
            <option label="Letter" value="letter">Letter</option>
            <option label="A4" value="a4">A4</option>
        </select>
    </p>
    <p>
        Format
        <select name="map[format]">
            <option label="Four cards" value="4up">Four cards</option>
            <option label="Two cards, fridge poster" value="2up-fridge">Two cards, fridge poster</option>
            <option label="Single-page poster" value="poster">Single-page poster</option>
        </select>
    </p>
    <p>
        1st Recipient name <input type="text" name="recipients[0][name]" value="Adam">
        email <input type="text" name="recipients[0][email]" value="ag@studies-observations.com">
    </p>
    <p>
        2nd Recipient name <input type="text" name="recipients[1][name]" value="Nurri">
        email <input type="text" name="recipients[1][email]" value="nk@doprojects.org">
    </p>
    <p>
        3rd Recipient name <input type="text" name="recipients[2][name]" value="Tom">
        email <input type="text" name="recipients[2][email]" value="tom@tom-carden.co.uk">
    </p>
    <p>
        <input type="submit">
    </p>
</form>

</body>
</html>

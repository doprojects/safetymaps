<?php

    ini_set('include_path', ini_get('include_path').PATH_SEPARATOR.'../../lib');

    require_once 'config.php';
    require_once 'lib.php';
    
    list($is_admin) = read_userdata($_COOKIE['userdata']);

    if($_SERVER['REQUEST_METHOD'] == 'POST')
    {
        switch($_POST['action'])
        {
            case 'Admin On':
                $is_admin = true;
                setcookie('userdata', write_userdata(true), time()+3600, get_base_dir().'/');
                break;

            case 'Admin Off':
                $is_admin = false;
                setcookie('userdata', write_userdata(false), time()+3600, get_base_dir().'/');
                break;
        }
    }
    
    $ctx = default_context();

    $q = "SELECT privacy, COUNT(id) AS count
          FROM maps
          WHERE created > NOW() - INTERVAL 1 WEEK
          GROUP BY privacy
          ORDER BY privacy ASC";
    
    if($res = mysql_query($q, $ctx->db))
    {
        $values = array();
        $labels = array();
        
        while($row = mysql_fetch_assoc($res))
        {
            $values[] = $row['count'];
            $labels[] = urlencode($row['count'].' '.ucwords($row['privacy']));
        }
        
        $privacy_chart = sprintf('http://chart.apis.google.com/chart?cht=p&chd=t:%s&chs=408x120&chl=%s', join(',', $values), join('|', $labels));
    }
    
    $ctx->close();
    
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<title>Master Control Switch</title>
</head>
<body>

    <p>
        In the past week:<br>
        <img src="<?=htmlspecialchars($privacy_chart)?>">
    </p>

    <p>
        <a href="<?=get_base_dir()?>/maps.php">Latest maps</a>.
    </p>

    <form action="index.php" method="post">
        <? if($is_admin) { ?>
            You are currently in admin mode.
            <button type="submit" name="action" value="Admin Off">Admin Off!</button>

        <? } else { ?>
            You are not in admin mode.
            <button type="submit" name="action" value="Admin On">Admin On!</button>
        <? } ?>
    </form>

</body>
</html>

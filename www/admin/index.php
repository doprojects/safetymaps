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
                setcookie('userdata', write_userdata(true), time()+600, get_base_dir().'/');
                break;

            case 'Admin Off':
                $is_admin = false;
                setcookie('userdata', write_userdata(false), time()+600, get_base_dir().'/');
                break;
        }
    }
    
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<title>Master Control Switch</title>
</head>
<body>

    <p><tt>base_dir</tt>: <?= get_base_dir() ?></p>

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

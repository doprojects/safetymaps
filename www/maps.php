<?php

    require_once 'lib.php';

    $db = mysql_connect('localhost', 'safetymaps', 's4f3tym4ps');
    mysql_select_db('safetymaps', $db);
    
    $ctx = new Context($db);

    if($_POST['sender'] && $_POST['place'] && $_POST['map'] && $_POST['recipients'])
    {
        $attempted_add = true;
        $added_map_id = add_map($ctx, $_POST);
    }
    
    $ctx->close();

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

<? if($added_map_id) { ?>
    <p style="color: white; background: green;">
        Added map #<?=htmlspecialchars($added_map_id)?>
    </p>
<? } elseif($attempted_add) { ?>
    <p style="color: white; background: red;">
        Failed to add map.
    </p>
<? } ?>

<form method="POST" action="maps.php">
    <p>
        User name <input type="text" name="sender[name]" value="Mike">
        email <input type="text" name="sender[email]" value="mike@teczno.com">
    </p>
    <p>
        Place name <input type="text" name="place[name]" value="Dolores Park">
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
        <input type="text" name="place[full-note]" value="By the playground, on the benches">
    </p>
    <p>
        Short Note
        <input type="text" name="place[short-note]" value="By the playground">
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

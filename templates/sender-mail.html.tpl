<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
        "http://www.w3.org/TR/html4/loose.dtd">
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<title>Sender Mail</title>
    <link rel="stylesheet" href="http://{$domain|escape}{$base_dir|escape}/mail-style.css" type="text/css">
</head>
<body>

<h1><img src="http://{$domain|escape}{$base_dir|escape}/images/mail-header.png" alt="Safety Maps" width="600" height="86" border="0"></h1>

<p class="illustration"><img src="http://{$domain|escape}{$base_dir|escape}/images/sender-mail-illustration.gif" width="83" height="81" border="0"></p>

<p>Dear <var>{$recipient.name|escape}</var>:</p>

<p>This is just a note to let you know that the Safety Map you made has successfully been sent to <var>{$recipient_names|escape}</var>. They've been sent a link to a page where they can download the map for free, or print it out at a variety of sizes.</p>

<p>You yourself can always find the Safety Map you made at this URL:</p>

<p><a href="{$map_href|escape}">{$map_href|escape}</a></p>

<p>...or make a new one at <a href="http://{$domain|escape}{$base_dir|escape}/make-a-safety-map.php">http://{$domain|escape}{$base_dir|escape}/make-a-safety-map.php</a></p>

<p>Thanks!<br>
The Safety Maps team</p>

<div id="footer">
    <p>© 2011 Do projects.</p>
    <p><a href="http://doprojects.org"><img src="http://{$domain|escape}{$base_dir|escape}/images/mail-do-logo.png" alt="Do Projects" width="42" height="21" border="0"></a></p>
    <p>Safety Maps and OpenStreetMap data are offered to you under a Creative Commons Attribution-Noncommercial-Share Alike license. See <a href="http://creativecommons.org/licenses/by-nc-sa/3.0">creativecommons.org/licenses/by-nc-sa/3.0</a> for details.</p>
    <p>Safety Maps is an effort of Do projects. <br>Please visit <a href="http://doprojects.org">doprojects.org</a> for further information.</p>
</div>

</body>
</html>

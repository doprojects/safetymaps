<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>Safety Maps: About</title>
        <link rel="stylesheet" type="text/css" href="fonts/stylesheet.css" />
        <link rel="stylesheet" type="text/css" href="style.css" />
    </head>
    <body>
        <div id="header">
            <h1>Safety Maps</h1>
            <h2>
                Where will you meet your loved ones in<br>
                the event of a natural disaster or other<br>
                emergency?
            </h2>
            <img src="logo.png" alt="Safety Maps Logo">
            <div id="questions">
                <p>
                    What happens when disaster strikes?<br>
                    You're separated from those you care about.<br>
                    Your phone doesn't work anymore...<br>
                    Where do you think it's a safe place to gather?<br>
                    Use Safety Maps to choose and share safe<br>
                    meeting points with your familiy and friends.
                </p>
                <p>
                    <a href="#create-your-map">Click here to start a scenario.</a>
                </p>
            </div>
            {include file="nav.htmlf.tpl"}
        </div>
        <div id="main">
            <div id="meeting-points">
                {if $map}
                    <pre>{$map.features.0|@print_r:1|escape:'html'}</pre>
                {elseif $maps}
                    <ul id="maps">
                        {foreach item="map" from=$maps.features}
                            <li class="map">
                                <a class="link place-name" href="maps.php?id={$map.id|escape}">{$map.properties.place_name|escape}</a>
                                from <span class="user-name">{$map.properties.user.name|escape}</span>

                                <span class="geo">
                                    <span class="latitude">{$map.geometry.coordinates.1}</span>
                                    <span class="longitude">{$map.geometry.coordinates.0}</span>
                                </span>

                                {*<pre>{$map|@print_r:1|escape:'html'}</pre>*}
                            </li>
                        {/foreach}
                    </ul>
                {/if}
            </div>
        </div>
        <div id="footer">
            <p>&copy; 2010 Do Projects. Credits, Privacy Policy, etc.</p>
        </div>
    </body>
</html>

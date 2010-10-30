<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>Safety Maps: Meeting Points</title>
        <link rel="stylesheet" type="text/css" href="fonts/stylesheet.css" />
        <link rel="stylesheet" type="text/css" href="style.css" />
    </head>
    <body>

        {include file="header.htmlf.tpl"}

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

        {include file="footer.htmlf.tpl"}

    </body>
</html>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>Safety Maps: Meeting Points</title>
        <link rel="stylesheet" type="text/css" href="fonts/stylesheet.css" />
        <link rel="stylesheet" type="text/css" href="style.css" />
        <link rel="stylesheet" type="text/css" href="maps.css" />
        <script type="text/javascript" src="jquery.min.js"></script>
        <script type="text/javascript" src="modestmaps.js"></script>
        <script type="text/javascript" src="cloudmade.js"></script>
        <script type="text/javascript" src="markerclip.js"></script>
        <script type="text/javascript" src="maps.js"></script>
    </head>
    <body>

        {include file="header.htmlf.tpl" current="maps"}

        <div id="main">
            {if $map}
                <pre>{$map.features.0|@print_r:1|escape:'html'}</pre>
            {elseif $maps}
            
                <div id="map"> </div>
            
                <div id="maps">
                    <ul>
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
                </div>
            {/if}
        </div>

        {include file="footer.htmlf.tpl"}

    </body>
</html>

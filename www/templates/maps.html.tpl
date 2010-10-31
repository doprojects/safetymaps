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
                <pre>{$map|@print_r:1|escape:'html'}</pre>
            {elseif $maps}
            
                <div id="map"> </div>
            
                    <ul id="maps">
                        {foreach item="map" from=$maps}
                            <li class="map">
                                <a class="link place-name" href="maps.php?id={$map.id|escape}">{$map.place_name|escape}</a>
                                from <span class="user-name">{$map.user.name|escape}</span>

                                <span class="geo">
                                    <span class="latitude">{$map.place_lat}</span>
                                    <span class="longitude">{$map.place_lon}</span>
                                </span>

                                {*<pre>{$map|@print_r:1|escape:'html'}</pre>*}
                            </li>
                        {/foreach}
                    </ul>
            {/if}
        </div>

        {include file="footer.htmlf.tpl"}

    </body>
</html>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>Safety Maps: A Do Project</title>
        <link rel="stylesheet" type="text/css" href="fonts/stylesheet.css" />
        <link rel="stylesheet" type="text/css" href="style.css" />
        <script type="text/javascript" src="jquery.min.js"></script>
        <script type="text/javascript" src="modestmaps.js"></script>
        <script type="text/javascript" src="cloudmade.js"></script>
        <script type="text/javascript" src="markerclip.js"></script>
        <script type="text/javascript" src="index.js"></script>
    </head>
    <body>

        {include file="header.htmlf.tpl"}

        <div id="main">
            <h1>Most recent meeting points others have chosen in the <span id="current city">New York City</span> area (<span id="point-count">15</span>)</h1>
            <div id="map">
            </div>
            <ul id="maps">
            </ul>
            <div id="points">
                <ul>
                    {foreach item="map" from=$maps.features}
                        <li><a href="maps.php?id={$map.id|escape}">{$map.properties.place_name|escape}</a>
                            <span class="geo">
                                <span class="latitude">{$map.geometry.coordinates.1}</span>
                                <span class="longitude">{$map.geometry.coordinates.0}</span>
                            </span>
                        </li>
                    {/foreach}
                </ul>
            </div>
        </div>

        {include file="footer.htmlf.tpl"}

    </body>
</html>

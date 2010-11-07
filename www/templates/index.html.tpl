<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>Safety Maps: A Do Project</title>
        <link rel="stylesheet" type="text/css" href="fonts/stylesheet.css" />
        <link rel="stylesheet" type="text/css" href="style.css" />
        <link rel="stylesheet" type="text/css" href="index.css" />
        <script type="text/javascript" src="jquery.min.js"></script>
        <script type="text/javascript" src="modestmaps.js"></script>
        <script type="text/javascript" src="cloudmade.js"></script>
        <script type="text/javascript" src="markerclip.js"></script>
        <script type="text/javascript" src="index.js"></script>
    </head>
    <body>

        {include file="header.htmlf.tpl" current="home"}

        <div id="main">
        
            <div id="about">
                <h2>What is a Safety Map?</h2>
                
                <p>
                    A Safety Map is a map that shows your family, friends and
                    loved ones where to meet in the event of a disaster or
                    other emergency.
                </p>
                    
                <p>
                    You can use the tools on this site to make custom Safety
                    Maps for free, share them with friends, and print them out
                    in a variety of convenient sizes.
                </p>
            </div>
            
            <div id="map">
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
            </div>
            
        </div>

        {include file="footer.htmlf.tpl"}

    </body>
</html>

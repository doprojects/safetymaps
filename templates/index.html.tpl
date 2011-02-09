<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>Safety Maps: A Do Project</title>
        {include file="head-links.htmlf.tpl"}
        <link rel="stylesheet" type="text/css" href="{$base_dir}/index.css" />
        <script type="text/javascript" src="{$base_dir}/jquery.min.js"></script>
        <script type="text/javascript" src="{$base_dir}/modestmaps.js"></script>
        <script type="text/javascript" src="{$base_dir}/cloudmade.js"></script>
        <script type="text/javascript" src="{$base_dir}/round-em-up.js"></script>
        <script type="text/javascript" src="{$base_dir}/markerclip.js"></script>
        <script type="text/javascript" src="{$base_dir}/index.js"></script>
    </head>
    <body>

        {include file="header.htmlf.tpl" current="home"}

        <div id="main">
        
            <div id="about">
                <p>
                    <img src="{$base_dir}/images/sample-example.png" width="480" height="371">
                </p>
                
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
            
            <div id="maps">
                <ul>
                    {foreach item="map" from=$maps}
                        <li class="map-info">
                            <a class="link place-name" href="{$base_dir}/maps.php/{$map.id|escape}">{$map.place_name|escape}</a>
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

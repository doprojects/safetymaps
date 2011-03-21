<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>Safety Maps: A Do Project</title>
        {include file="head-links.htmlf.tpl"}
        <link rel="stylesheet" type="text/css" href="{$base_dir}/index.css" />
        <link rel="stylesheet" type="text/css" href="{$base_dir}/maps.css" />
        <script type="text/javascript" src="{$base_dir}/jquery.min.js"></script>
        <script type="text/javascript" src="{$base_dir}/maps.js"></script>
        <script type="text/javascript" src="{$base_dir}/modestmaps.js"></script>
        <script type="text/javascript" src="{$base_dir}/cloudmade.js"></script>
        <script type="text/javascript" src="{$base_dir}/round-em-up.js"></script>
        <script type="text/javascript" src="{$base_dir}/markerclip.js"></script>
    </head>
    <body>

        {include file="header.htmlf.tpl" current="home"}

        <div id="main">
        
            <div id="about">
                <p id="introduction-animation">
                    <img src="{$base_dir}/images/introduction-animation.gif" width="480" height="371">
                </p>
                
                <h2>What’s a Safety Map?</h2>
                <p>Have you ever thought about how you’d stay in touch with your loved ones if your city experienced a natural disaster or other emergency?</p>
                <p>Safety Maps is a free online tool that helps you plan for this situation. You can use it to choose a safe meeting place, print a customized map that specifies where it is, and share this map with your loved ones.</p>
                <p>You can choose to print Safety Maps in wallet, desk or refrigerator sizes. The above graphic explains how to make a Safety Map, but really, the best way to understand how it works is simply to <a href="{$base_dir}/make-a-safety-map.php">get started making one of your own</a>.</p>
                <p>Other things you can do here:</p>
                <ul>
                    <li>See <a href="{$base_dir}/maps.php">Safety Maps other people have made</a>.</li>
                    {* <li>See a collective map of everyone’s safe places.</li> *}
                    <li><a href="{$base_dir}/about.php">Learn more</a> about the thinking behind Safety Maps.</li>
                    <li><a href="{$base_dir}/links.php">Suggest a link</a> you think we ought to have.</li>
                    <li><a href="http://doprojects.org/">Get in touch!</a></li>
                </ul>
            </div>
            
            <h2>
                Latest Safety Maps
            </h2>
            
            <ul id="maps">
                {foreach item="map" from=$maps}
                    <li class="map-info">
                        <h3>
                            Safety Map
                            {if $recipient and $recipient.email != $map.user.email}
                                for <var>{$recipient.name|escape}</var>
                            {else}
                                by <var>{$map.user.name|escape}</var>
                            {/if}
                        </h3>

                        <span class="geo">
                            <span class="latitude">{$map.place_lat}</span>
                            <span class="longitude">{$map.place_lon}</span>
                            <span class="bbox-west">{$map.bbox_west}</span>
                            <span class="bbox-south">{$map.bbox_south}</span>
                            <span class="bbox-east">{$map.bbox_east}</span>
                            <span class="bbox-north">{$map.bbox_north}</span>
                        </span>
                        
                        <div class="map-area"></div>
                        
                        <p>
                            In case of <var>{$map.emergency|escape}</var>,
                            let’s meet at <var class="place-name">{$map.place_name|escape}</var>.
                            <a class="link" href="{$base_dir}/maps.php/{$map.id|escape}">I’ve marked the spot on this map</a>.
                        </p>

                        {*<pre>{$map|@print_r:1|escape:'html'}</pre>*}
                    </li>
                {/foreach}
            </ul>

            <p class="pagination">
                {if $maps_count > 0}
                    {if $more_older_maps}
                        <span class="older"><a href="{$base_dir}/maps.php?count={$count|escape}&amp;offset={$older_maps_offset|escape}">Older Maps</a> →</span>
                    {/if}
                {else}
                    <span class="newer">← <a href="{$base_dir}/maps.php?count={$count|escape}">Newest Maps</a></span>
                {/if}
            </p>

            <!--
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
            -->
            
        </div>

        {include file="footer.htmlf.tpl"}

    </body>
</html>

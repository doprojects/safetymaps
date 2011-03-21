<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>{strip}
            Safety Map
            {if $map and $recipient and $recipient.email != $map.user.email} for {$recipient.name|escape}{/if}
            {if $map} by {$map.user.name|escape}{/if}
        {/strip}</title>
        {include file="head-links.htmlf.tpl"}
        <script type="text/javascript" src="{$base_dir}/jquery.min.js"></script>
        {if $map}
            <link rel="stylesheet" type="text/css" href="{$base_dir}/map.css" />
            <script type="text/javascript" src="{$base_dir}/map.js"></script>
        {else}
            <link rel="stylesheet" type="text/css" href="{$base_dir}/maps.css" />
            <script type="text/javascript" src="{$base_dir}/maps.js"></script>
        {/if}
        <script type="text/javascript" src="{$base_dir}/modestmaps.js"></script>
        <script type="text/javascript" src="{$base_dir}/cloudmade.js"></script>
        <script type="text/javascript" src="{$base_dir}/round-em-up.js"></script>
        <script type="text/javascript" src="{$base_dir}/markerclip.js"></script>
    </head>
    <body>

        {include file="header.htmlf.tpl" current="maps"}

        <div id="main">
            {if $map}
            
                <h2>
                    Safety Map
                    {if $recipient and $recipient.email != $map.user.email}
                        for <var>{$recipient.name|escape}</var>
                    {else}
                        by <var>{$map.user.name|escape}</var>
                    {/if}
                </h2>
                
                <p>
                    In case of <var>{$map.emergency|escape}</var>,
                    let’s meet at <var>{$map.place_name|escape}</var>.
                    I’ve marked the spot on this map:
                </p>
                
                <div id="preview-map"></div>
                
                <script type="text/javascript">
                <!--
                    {strip}
                
                    show_preview_map(document.getElementById('preview-map'),
                                     {$map.place_lat|escape:javascript},
                                     {$map.place_lon|escape:javascript},
                                     {$map.bbox_south|escape:javascript},
                                     {$map.bbox_west|escape:javascript},
                                     {$map.bbox_north|escape:javascript},
                                     {$map.bbox_east|escape:javascript},
                                     '{$base_dir|escape:"javascript"}');
                
                    {/strip}
                //-->
                </script>

                <p class="map-date">
                    This Safety Map was made on <var>{$map.created_unixtime|nice_date|escape}</var>.
                </p>
                
                {if $map.note_full}
                    <blockquote>{$map.note_full|escape}</blockquote>
                {/if}
                
                {if $recipient and $recipient.email != $map.user.email}
                    <p>
                        From <var>{$map.user.name|escape}</var>.
                    </p>
                {/if}
                
                <div class="download-area">
                    {if $recipient}
                        {if $recipient.failed}
                            <p class="status-message">
                                [failed to generate this map for you {$recipient.failed}]
                            </p>

                        {elseif $recipient.waiting}
                            <p class="status-message">
                                [still generating this map for you]
                            </p>

                        {else}
                            <p>
                                Download a printable version as a PDF file:
                            </p>
                            
                            <ul class="formats">
                                {foreach item="format" from=$formats}
                                    <li class="format">
                                        <a href="{$base_dir}/files/{$map.id|escape}/{$recipient.id|escape}/letter-{$format|escape}.pdf">
                                            <img src="{$base_dir}/images/hands-{$format}.png"></a>
                                        <br>
                                        {foreach item="paper" from=$papers name="papers"}
                                            <a href="{$base_dir}/files/{$map.id|escape}/{$recipient.id|escape}/{$paper|escape}-{$format|escape}.pdf">
                                                {if $smarty.foreach.papers.first}{$format|ucwords}{/if} {$paper|ucwords|escape}</a>{if !$smarty.foreach.papers.last},{/if}
                                        {/foreach}
                                    </li>
                                {/foreach}
                            </ul>
                        {/if}

                    {else}
                        {if $map.waiting}
                            <p class="status-message">
                                Generating {$map.waiting} copies of this map…
                            </p>

                        {else}
                            <p>
                                PDF downloads are emailed to the sender and recipients of each Safety Map.
                                <a href="{$base_dir}/make-a-safety-map.php">Make your own!</a>
                            </p>
                            
                            <ul class="formats">
                                {foreach item="format" from=$formats}
                                    <li class="format">
                                        <img src="{$base_dir}/images/hands-gray-{$format}.png">
                                    </li>
                                {/foreach}
                            </ul>
                        {/if}
                    {/if}
                </div>

            {elseif $maps}
            
                {capture name="pagination"}
                    <p class="pagination">
                        {if $maps_count > 0}
                            {if $more_newer_maps}
                                <span class="newer">← <a href="{$base_dir}/maps.php?count={$count|escape}&amp;offset={$newer_maps_offset|escape}">Newer</a></span>
                            {/if}
                            {if $more_older_maps}
                                <span class="older"><a href="{$base_dir}/maps.php?count={$count|escape}&amp;offset={$older_maps_offset|escape}">Older</a> →</span>
                            {/if}
                        {else}
                            <span class="newer">← <a href="{$base_dir}/maps.php?count={$count|escape}">Newest</a></span>
                        {/if}
                    </p>
                {/capture}
                
                {$smarty.capture.pagination}
            
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
                
                {$smarty.capture.pagination}
            
            {/if}
        </div>

        {include file="footer.htmlf.tpl"}

    </body>
</html>

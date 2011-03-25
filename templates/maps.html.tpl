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
                {assign var="eventname" value="view map page"}
            
                <h2>
                    Safety Map
                    {if $recipient and $recipient.email != $map.user.email}
                        for <var>{$recipient.name|escape}</var>
                    {else}
                        by <var>{$map.user.name|escape}</var>
                    {/if}
                </h2>
                
                {if $is_admin}
                    <form class="admin" action="{$request.uri|escape}" method="post">
                        {if $map.waiting}
                            {$map.waiting} waiting recipients,
                        {/if}
                        {assign var="created_ts" value=$map.created|@strtotime}
                        {assign var="now_ts" value="now"|@time}
                        {$now_ts-$created_ts|nice_relativetime},
                        map
                        <a class="link" href="{$base_dir}/maps.php/{$map.id|escape}">{$map.id|escape}</a>.

                        {if $map.privacy == 'unlisted'}
                            Unlisted.
                        {else}
                            <select name="privacy">
                                <option label="Public" value="public" {if $map.privacy == 'public'}selected{/if}>Public</option>
                                <option label="Delisted" value="delisted" {if $map.privacy == 'delisted'}selected{/if}>Delisted</option>
                            </select>
                            <input name="id" type="hidden" value="{$map.id|escape}">
                            <button type="submit" name="action" value="Change Map">Change Map</button>
                        {/if}
                    </form>
                {/if}

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
                {assign var="eventname" value="view maps page"}
            
                {capture name="pagination"}
                    <p class="pagination">
                        {if $maps_count > 0}
                            {if $more_newer_maps}
                                <span class="newer">← <a href="{$base_dir}/maps.php?count={$count|escape}&amp;offset={$newer_maps_offset|escape}">Newer Maps</a></span>
                            {/if}
                            {if $more_newer_maps && $more_older_maps}
                                /
                            {/if}
                            {if $more_older_maps}
                                <span class="older"><a href="{$base_dir}/maps.php?count={$count|escape}&amp;offset={$older_maps_offset|escape}">Older Maps</a> →</span>
                            {/if}
                        {else}
                            <span class="newer">← <a href="{$base_dir}/maps.php?count={$count|escape}">Newest Maps</a></span>
                        {/if}
                    </p>
                {/capture}
                
                {$smarty.capture.pagination}
            
                <ul id="maps">
                    {foreach item="map" from=$maps}
                        <li class="map-info {if $is_admin}admin{/if}">
                            <h3>
                                Safety Map
                                {if $recipient and $recipient.email != $map.user.email}
                                    for <var>{$recipient.name|escape}</var>
                                {else}
                                    by <var>{$map.user.name|escape}</var>
                                {/if}
                            </h3>
                            
                            {if $is_admin}
                                <form action="{$request.uri|escape}" method="post">
                                    {if $map.waiting}
                                        {$map.waiting} waiting recipients,
                                    {/if}
                                    {assign var="created_ts" value=$map.created|@strtotime}
                                    {assign var="now_ts" value="now"|@time}
                                    {$now_ts-$created_ts|nice_relativetime},
                                    map
                                    <a class="link" href="{$base_dir}/maps.php/{$map.id|escape}">{$map.id|escape}</a>.
                                    <br>
                                    {if $map.privacy == 'unlisted'}
                                        Unlisted.
                                    {else}
                                        <select name="privacy">
                                            <option label="Public" value="public" {if $map.privacy == 'public'}selected{/if}>Public</option>
                                            <option label="Delisted" value="delisted" {if $map.privacy == 'delisted'}selected{/if}>Delisted</option>
                                        </select>
                                        <input name="id" type="hidden" value="{$map.id|escape}">
                                        <button type="submit" name="action" value="Change Map">Change Map</button>
                                    {/if}
                                </form>
                            {/if}

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
            
            {else}
                {assign var="eventname" value="view maps page unsuccessfully"}
            {/if}
        </div>

        {include file="footer.htmlf.tpl" eventname=$eventname}

    </body>
</html>

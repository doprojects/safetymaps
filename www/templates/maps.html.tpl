<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>{strip}
            Safety Map
            {if $map and $recipient and $recipient.email != $map.user.email} for {$recipient.name|escape}{/if}
            {if $map} by {$map.user.name|escape}{/if}
        {/strip}</title>
        <link rel="stylesheet" type="text/css" href="fonts/stylesheet.css" />
        <link rel="stylesheet" type="text/css" href="style.css" />
        {if $map}
            <link rel="stylesheet" type="text/css" href="map.css" />
            <script type="text/javascript" src="map.js"></script>
        {else}
            <link rel="stylesheet" type="text/css" href="maps.css" />
            <script type="text/javascript" src="maps.js"></script>
        {/if}
        <script type="text/javascript" src="jquery.min.js"></script>
        <script type="text/javascript" src="modestmaps.js"></script>
        <script type="text/javascript" src="cloudmade.js"></script>
        <script type="text/javascript" src="markerclip.js"></script>
    </head>
    <body>

        {include file="header.htmlf.tpl" current="maps"}

        <div id="main">
            {if $map}
            
                {if $recipient}
                    {if $recipient.failed}
                        <pre>[failed to generate this map for you {$recipient.failed}]</pre>
                    {elseif $recipient.waiting}
                        <pre>[still generating this map for you]</pre>
                    {elseif $recipient.sent}
                        <!-- sent you this map {$recipient.sent} -->
                    {/if}
                {else}
                    {if $map.waiting}
                        <pre>[still generating this map {$map.waiting}]</pre>
                    {/if}
                {/if}
            
                <h2>
                    Safety Map
                    {if $recipient}
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
                                     {$map.bbox_east|escape:javascript});
                
                    {/strip}
                //-->
                </script>

                <blockquote>{$map.note_full|escape}</blockquote>
                
                {if $recipient}
                    <p>
                        From <var>{$map.user.name|escape}</var>.
                    </p>
                {/if}
                
                <p>
                    This Safety Map was made on <var>{$map.created_unixtime|nice_date|escape}</var>.
                </p>
                
                {if $recipient}
                    <p>
                        Download a printable version as a PDF file:
                    </p>
                    
                    <ul class="formats">
                        {foreach item="format" from=$formats}
                            <li class="format">
                                <img src="{$base_dir}/images/hands-{$format}.png">
                                <br>
                                {foreach item="paper" from=$papers name="papers"}
                                    <a href="{$base_dir}/files/{$map.id|escape}/{$recipient.id|escape}/{$paper|escape}-{$format|escape}.pdf">
                                        {if $smarty.foreach.papers.first}{$format|ucwords}{/if} {$paper|ucwords|escape}</a>{if !$smarty.foreach.papers.last},{/if}
                                {/foreach}
                            </li>
                        {/foreach}
                    </ul>
                {/if}

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

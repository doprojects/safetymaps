<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>Safety Maps: Meeting Points</title>
        <link rel="stylesheet" type="text/css" href="fonts/stylesheet.css" />
        <link rel="stylesheet" type="text/css" href="style.css" />
        {if $map}
            <link rel="stylesheet" type="text/css" href="map.css" />
        {else}
            <link rel="stylesheet" type="text/css" href="maps.css" />
        {/if}
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
            
                {if $recipient}
                    {if $recipient.failed}
                        <pre>[failed to generate this map for you {$recipient.failed}]</pre>
                    {elseif $recipient.waiting}
                        <pre>[still generating this map for you]</pre>
                    {elseif $recipient.sent}
                        <pre>[sent you this map {$recipient.sent}]</pre>
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
                
                <pre>[map will go here]</pre>
                
                <blockquote>{$map.note_full|escape}</blockquote>
                
                {if $recipient}
                    <p>
                    </p>

                    <p>
                        From <var>{$map.user.name|escape}</var>.
                    </p>
                    
                    <ul>
                        {foreach item="paper_format" from=$paper_formats}
                            <li>
                                <a href="{$base_dir}/files/{$map.id|escape}/{$recipient.id|escape}/{$paper_format|@join:"-"|escape}.pdf"><code>[{$paper_format|@join:" "|ucwords|escape} PDF]</code></a>
                            </li>
                        {/foreach}
                    </ul>
                {/if}
                
                <p>
                    This Safety Map was made on <var>{$map.created_unixtime|nice_date|escape}</var>.
                </p>

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

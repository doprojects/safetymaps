<div id="header">
    <a href="{$base_dir}"><img src="{$base_dir}/images/header.png" width="860" height="137" alt="Safety Maps: Make and share maps of safe places to meet in the event of an emergency"></a>
    <div id="nav">
        <ul>
            <li><a class="{if $current == 'home'}current{/if}" href="{$base_dir}/">Home</a></li>
            <li><a class="{if $current == 'about'}current{/if}" href="{$base_dir}/about.php">About</a></li>
            <li><a class="{if $current == 'make'}current{/if}" href="{$base_dir}/make-a-safety-map.php">Make a Safety Map</a></li>
            {*
            <li><a class="{if $current == 'maps'}current{/if}" href="{$base_dir}/maps.php">See meeting points other people have chosen</a></li>
            *}
            <li><a class="{if $current == 'scenarios'}current{/if}" href="{$base_dir}/links.php">Links</a></li>
        </ul>
    </div>
</div>

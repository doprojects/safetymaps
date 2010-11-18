<p>Dear {$recipient.name|escape}:</p>

<p>{$sender.name|escape} has made you a Safety Map. This is a map that shows where they plan to meet up with you in the event of {$map.emergency|escape}.</p>

<p>You can download and print your Safety Map for free at this URL...</p>

<p><a href="{$map_href|escape}">{$map_href|escape}</a></p>

<p>...or you can make a Safety Map of your own at <a href="http://{$domain|escape}{$base_dir|escape}/make-a-safety-map.php">http://{$domain|escape}{$base_dir|escape}/make-a-safety-map.php</a></p>

<p>Thanks!<br>
The Safety Maps team</p>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>Safety Maps: Make A Safety Map</title>
        <link rel="stylesheet" type="text/css" href="{$base_dir}/fonts/stylesheet.css" />
        <link rel="stylesheet" type="text/css" href="{$base_dir}/style.css" />
        <link rel="stylesheet" type="text/css" href="{$base_dir}/make-a-safety-map.css" />
        <style type="text/css">{literal}
/* TODO: move to make-a-safety-map.css */
#charcount {
  color: #080;
}
#charcount.invalid {
  color: #800;
}
#main label[for="personal"] {
  font-size: 100%;
}

        {/literal}</style>
        <script type="text/javascript" src="{$base_dir}/jquery.min.js"></script>
        <script type="text/javascript" src="{$base_dir}/modestmaps.js"></script>
        <script type="text/javascript" src="{$base_dir}/cloudmade.js"></script>
        <script type="text/javascript" src="{$base_dir}/round-em-up.js"></script>
        <script type="text/javascript" src="{$base_dir}/anyzoom.js"></script>
        <script type="text/javascript" src="{$base_dir}/make-a-safety-map.js"></script>
        <script type="text/javascript">{literal}

// TODO: move to make-a-safety-map.js

$(document).ready(function() {

    // twitter style remaining character count 
    // (allow more chars to be typed but don't allow form submission, below)
    var prevLength;
    function onNoteChange() {
      if (this.value.length == prevLength) return;
      prevLength = this.value.length; // includes line breaks, OK?
      $('#charcount').text((300 - this.value.length) + " remaining");
      if (this.value.length <= 300) {
          $('#charcount').removeClass('invalid');
      }
      else {
          $('#charcount').addClass('invalid');
      }
    }
    $('#fullnote').change(onNoteChange); // only fires onblur
    $('#fullnote').keyup(onNoteChange); // fires with key strokes too
    $('#fullnote').bind('input', onNoteChange); // html5 event, catches paste with mouse too

    // disable recipients if map is personal
    $('#personal').change(function() {
        if($(this).attr('checked')) {
            $('#recipients').slideUp();
        }
        else {
            $('#recipients').slideDown();
        }
    });

    // rudimentary form validation, just check for existence/length and display alerts
    $('#mapform').submit(function() {
    
        return true;
     
        if($('#otherplace').attr('selected')) {
            var custom = this['place[emergency]'].value;
            if(custom.length == 0 || custom == 'other') {
                alert("Please specify a custom event or select a suggested value.");
                $('#emergency-other').focus();
                return false;
            }
        }

        // make a hash of basic required elements
        var required = {};
        required['place[name]'] = 'Meeting place name';
        required['place[note_full]'] = 'A short personal note'; 
        // only include recipients if the checkbox isn't selected
        if (!$('#personal').attr('checked')) {
            var count = $('#recipients li').length;
            for(var i = 0; i < count; i++) {
                required['recipients['+i+'][name]'] = 'Recipient\'s name or nickname';
                required['recipients['+i+'][email]'] = 'Recipient\'s email address';
            }
        }
        required['sender[name]'] = 'Your name or nickname';
        required['sender[email]'] = 'Your email address';
        for (var name in required) { 
            if(this[name].value.length == 0) {
               alert(required[name] + ' is required.');
               $(this[name]).focus();
               return false; 
            }
        }
 
        // reject 'far out' maps
        if (bboxmap.getZoom() < 10) {
            alert('The chosen map view is quite big - please zoom in and  choose a smaller area.');
            window.scrollTo(window.scrollX, -20 + $(bboxmap.parent).offset().top);
            $('#bboxmap').focus();
            return false;
        }

        // reject windbaggery
        if (this['place[note_full]'].value.length > 300) {
            alert('Note should be less than 300 characters.');
            $(this['place[note_full]']).focus();
            return false;
        }

        // TODO perhaps check for NaNs and valid angles in:
        // map[bounds][0] map[bounds][1] map[bounds][2] map[bounds][3]
        // place[location][0] place[location][1] should exist as numbers (validate degrees)
 
        return true;
    });

}); 
        {/literal}</script>
    </head>
    <body>

        {include file="header.htmlf.tpl" current="make"}

        <div id="main">
        
            <h2>Make a Safety Map.</h2>
                
            <p class="intro">By answering these few simple questions, you can make a 
               custom map of a place you think it will be safe for your
               friends, family or loved ones to meet in the event of an
               emergency.</p>

             <p class="intro">You can print this map out in a variety of formats, share 
                it via email, or both. Either way, you'll be able to 
                include a personal message to recipients.</p>
        
            <div id="make">

                <form id="mapform" method="POST" action="make-a-safety-map.php">
                
                <pre>{$request.post|@print_r:1|escape}</pre>
                
                <table>
                <tr class="first"><td class="inputs">
                
                    {* Assume no pre-chosen emergency, the first one in the list will be the default *}
                    {assign var="chosen" value="normal"}

                    {if $request.post}
                        {* Some emergency might have been chosen, we don't yet know which *}
                        {assign var="chosen" value="other"}
                    {/if}

                    {* Prepare the list of <option> elements in a captured block *}
                    {capture name="emergency_options"}
                        {* Build an array of possible emergencies from a comma-delimited list *}
                        {assign var="choices" value=","|explode:"an emergency,an earthquake,a blackout,a fire,a flood,a public transportation failure"}

                        {foreach from=$choices item="choice"}
                            {if $request.post.place.emergency == $choice}
                                {assign var="chosen" value="normal"}
                                <option selected>{$choice|escape}</option>

                            {else}
                                <option>{$choice|escape}</option>
                            {/if}
                        {/foreach}

                        {if $chosen == "normal"}
                            {* We found an existing emergency in the list of choices above *}
                            <option id="otherplace" value="other">Other (please specify)</option>

                        {elseif $chosen == "other"}
                            {* We got this far without hitting an existing emergency, must be "other" *}
                            <option id="otherplace" value="other" selected>Other (please specify)</option>
                        {/if}
                    {/capture}

                    <p>
                        In case of
                        <span id="emergency-chooser" style="{if $chosen == "other"}margin-top: -38px;{/if}">
                          {strip}
                            {if $chosen == "normal"}
                                <select id="emergency-select" name="place[emergency]">
                                    {$smarty.capture.emergency_options|strip}
                                </select>
                                <input id="emergency-other" style="display: none;" name="" value="" type="text" size="32">

                            {elseif $chosen == "other"}
                                <select id="emergency-select" name="" class="other">
                                    {$smarty.capture.emergency_options|strip}
                                </select>
                                <input id="emergency-other" style="display: inline;" name="place[emergency]" {$request.post.place.emergency|value_or_unacceptable_attr:$request.method} type="text" size="32">
                            {/if}
                          {/strip}
                        </span>
                        let's meet at
                        <input type="text" name="place[name]" size="25" {$request.post.place.name|value_or_unacceptable_attr:$request.method}>.
                        I've marked the spot on this map:
                    </p>           

                   </td><td class="help">&nbsp;</td></tr>

                   <tr><td class="inputs">          

                    <input type="hidden" id="loc0" name="place[location][0]" value="{$request.post.place.location.0|escape}">
                    <input type="hidden" id="loc1" name="place[location][1]" value="{$request.post.place.location.1|escape}">
                    <input type="hidden" id="bbox0" name="map[bounds][0]" value="{$request.post.map.bounds.0|escape}">
                    <input type="hidden" id="bbox1" name="map[bounds][1]" value="{$request.post.map.bounds.1|escape}">
                    <input type="hidden" id="bbox2" name="map[bounds][2]" value="{$request.post.map.bounds.2|escape}">
                    <input type="hidden" id="bbox3" name="map[bounds][3]" value="{$request.post.map.bounds.3|escape}">

                    <div id="bboxmap"><noscript>Please enable javascript and refresh this page to choose your location using our interactive map. Sorry for the inconvenience!</noscript></div>

                    </td><td class="help">
                    <p>Drag and zoom the map to change the area that will be printed.</p><p>You can scroll with your mouse, double-click or use the ╋ and ━ buttons to zoom.</p>
                    <p>Be sure to zoom close enough to see nearby streets!</p>
                    <p class="thoughtful">If you want an off-center map you can drag the green marker directly to fine tune your precise meeting point.</p>
                    </td></tr>

                    <tr class="last"><td class="inputs">
                    <p class="field">
                        <textarea type="text" name="place[note_full]" id="fullnote" rows="8">{$request.post.place.note_full|escape}</textarea>
                        <br>
                        <span id="charcount">300 remaining</span>
                    </p>
                    </td><td class="help">
                        <p>Include a personal note for your recipients.<br><em>(300 character limit)</em></p>
                    <p class="thoughtful">Remember that the recipient might be reading this at a very difficult moment, so please think carefully about what you want to write here!</p>
                    
                    </td></tr>

                    </table>
                    
                    <h3>Who's this map for?</h3>                    

                    <p>
                        Enter the names and email addresses of people you'd like to share this Safety Map with.
                    </p>
                    <ol id="recipients">

                        {* There will always be at least one recipient in the list *}
                        <li>
                            name: <input type="text" name="recipients[0][name]" {$request.post.recipients.0.name|value_or_unacceptable_attr:$request.method} size="15">
                            email: <input type="email" name="recipients[0][email]" placeholder="e.g. them@there.com" {$request.post.recipients.0.email|value_or_unacceptable_attr:$request.method} size="35">
                            <a class="remove-recipient" href="#">━ Remove recipient</a>
                        </li>
                        
                        {* Now do the rest, if there are any *}
                        {foreach from=$request.post.recipients key="index" item="recipient"}
                            {if $index >= 1}
                                <li>
                                    name: <input type="text" name="recipients[{$index}][name]" {$request.post.recipients.$index.name|value_or_unacceptable_attr:$request.method} size="15">
                                    email: <input type="email" name="recipients[{$index}][email]" placeholder="e.g. them@there.com" {$request.post.recipients.$index.email|value_or_unacceptable_attr:$request.method} size="35">
                                    <a class="remove-recipient" href="#">━ Remove recipient</a>
                                </li>
                            {/if}
                        {/foreach}
                    </ol>

                    {* Finally a row for the button that adds recipients *}
                    <p>
                        <a id="add-recipient" href="">╋ Add recipient</a>
                    </p>
                        
                    <h3>You're almost done.</h3>

                    <p>Now that you've chosen a safe place to meet, you're ready to make and print your maps.</p>
                    
                    <p>
                        What's your name or nickname?
                        <input type="text" name="sender[name]" {$request.post.sender.name|value_or_unacceptable_attr:$request.method} placeholder="e.g. Your Name">
                    </p>
                    <p>
                        What's your email address?
                        <input type="email" name="sender[email]" {$request.post.sender.email|value_or_unacceptable_attr:$request.method} placeholder="e.g. you@example.com" size="35">
                    </p>
                    <p>
                        Who can see your map?
                        <input type="radio" name="map[privacy]" value="public">Everyone
                        <input type="radio" name="map[privacy]" value="unlisted" checked>Just you and your recipients.
                    </p>

                    <p id="done"><button type="submit">Go!</button></p>

                </form>
                                    
            </div>
        
        </div>

        {include file="footer.htmlf.tpl"}

    </body>
</html>

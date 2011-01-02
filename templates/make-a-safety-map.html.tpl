<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>Safety Maps: Make A Safety Map</title>
        <link rel="stylesheet" type="text/css" href="fonts/stylesheet.css" />
        <link rel="stylesheet" type="text/css" href="style.css" />
        <link rel="stylesheet" type="text/css" href="make-a-safety-map.css" />
        <style type="text/css">{literal}
/* TODO: move to make-a-safety-map.css */
#pleasechoose {
  position: relative;
  display: inline-block;
  vertical-align: baseline;
  width: 300px;
  line-height: 1.0;
}
#emergencyplace, #otherinput {
  position: relative;
  display: inline-block;
  vertical-align: baseline;
  width: 295px;
}
#emergencyplace {
  margin: 6px 0 !important;
  padding: 0 !important;
}
#otherinput {
  margin: 8px 0 !important;
  padding: 0 !important;
}
#charcount {
  color: #080;
}
#charcount.invalid {
  color: #800;
}
#main label[for="personal"] {
  font-size: 100%;
}

#pleasechoose.other
{
    margin-top: -38px;
}

#pleasechoose.normal #otherinput
{
    display: none;
}

        {/literal}</style>
        <script type="text/javascript" src="jquery.min.js"></script>
        <script type="text/javascript" src="modestmaps.js"></script>
        <script type="text/javascript" src="cloudmade.js"></script>
        <script type="text/javascript" src="anyzoom.js"></script>
        <script type="text/javascript" src="make-a-safety-map.js"></script>
        <script type="text/javascript">{literal}

// TODO: move to make-a-safety-map.js

$(document).ready(function() {

    $('label[for=rdpublic]').mouseover(function(e) {
        $('#publictip').fadeIn().offset({ left: e.pageX+20, top: e.pageY+20 });
    });
    $('label[for=rdpublic]').mouseout(function() {
        $('#publictip').hide();
    });
    $('label[for=rdprivate]').mouseover(function(e) {
        $('#privatetip').fadeIn().offset({ left: e.pageX+20, top: e.pageY+20 });
    });
    $('label[for=rdprivate]').mouseout(function() {
        $('#privatetip').hide();
    });
    $('#privacy').mousemove(function(e) {
        $('#privatetip').offset({ left: e.pageX+20, top: e.pageY+20 });
        $('#publictip').offset({ left: e.pageX+20, top: e.pageY+20 });
    });
    
    function activateOther()
    {
        $('#pleasechoose').removeClass('normal');
        $('#pleasechoose').addClass('other');

        $('#emergencyplace').attr('name', '');
        $('#otherinput').attr('name', 'place[emergency]');
        $('#otherinput').focus();

        return;
    }
    
    function deactivateOther()
    {
        $('#pleasechoose').removeClass('other');
        $('#pleasechoose').addClass('normal');

        $('#emergencyplace').attr('name', 'place[emergency]');
        $('#otherinput').attr('name', '');

        return;
    }
    
    // deal with "Other (please specify)"
    $('#emergencyplace').change(function()
      {
        if($('#emergencyplace option#otherplace').attr('selected')) {
            return activateOther();

        } else {
            return deactivateOther();
        }
      }
    );

    // deal with additional recipients
    $('a.addrecipient').live('click', function() {
        var newLi = $(this).parent('li').clone();
        newLi.children('input').attr('value','');
        newLi.hide();
        // make sure there's a remove button:
        if (newLi.children('.removerecipient').length == 0) {
            newLi.append('<a href="" class="removerecipient">Remove this one?<\/a>');
        }
        // append it
        $(this).parent('li').after(newLi);
        newLi.slideDown(function(){
            renumberFormElements();
            // for want of a DOM change event
            $(document.body).trigger('search-needs-adjusting');
        });
        return false;
    });

    // undo additional recipients
    $('a.removerecipient').live('click', function() {
        $(this).parent('li').slideUp(function() {
            $(this).remove();
            renumberFormElements();
            $(document.body).trigger('search-needs-adjusting');
        });
        return false;
    });

    function renumberFormElements() {
        // renumber the form elements
        $('#recipients li').each(function(i) {
            $(this).children('input').attr('name', function(j, attr) {
                return attr.replace(/\d+/, i);
            });
            $(this).children('label').attr('for', function(j, attr) {
                return attr.replace(/\d+/, i);
            });
        });
    }

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
     
        if($('#otherplace').attr('selected')) {
            var custom = this['place[emergency]'].value;
            if(custom.length == 0 || custom == 'other') {
                alert("Please specify a custom event or select a suggested value.");
                $('#otherinput').focus();
                return false;
            }
        }

        // make a hash of basic required elements
        var required = {};
        required['place[name]'] = 'Meeting place name';
        required['place[full-note]'] = 'A short personal note'; 
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
        if (this['place[full-note]'].value.length > 300) {
            alert('Note should be less than 300 characters.');
            $(this['place[full-note]']).focus();
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
                
                <table>
                <tr class="first"><td class="inputs">
                
                    {* Assume no pre-chosen emergency, the first one in the list will be the default *}
                    {assign var="chosen" value="normal"}

                    {if $request.post.place.emergency}
                        {* Some emergency has been chosen, we don't yet know which *}
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
                        <span id="pleasechoose" class="{$chosen}">
                          {strip}
                            {if $chosen == "normal"}
                                <select id="emergencyplace" name="place[emergency]">
                                    {$smarty.capture.emergency_options}
                                </select>
                                <input id="otherinput" name="" value="" type="text" size="32">

                            {elseif $chosen == "other"}
                                <select id="emergencyplace" name="">
                                    {$smarty.capture.emergency_options}
                                </select>
                                <input id="otherinput" name="place[emergency]" value="{$request.post.place.emergency|escape}" type="text" size="32">
                            {/if}
                          {/strip}
                        </span>
                        let's meet at
                        <input type="text" name="place[name]" size="25" value="{$request.post.place.name|escape}">.
                        I've marked the spot on this map:
                    </p>           

                   </td><td class="help">&nbsp;</td></tr>

                   <tr><td class="inputs">          

                    <input type="hidden" id="loc0" name="place[location][0]">
                    <input type="hidden" id="loc1" name="place[location][1]">
                    <input type="hidden" id="bbox0" name="map[bounds][0]">
                    <input type="hidden" id="bbox1" name="map[bounds][1]">
                    <input type="hidden" id="bbox2" name="map[bounds][2]">
                    <input type="hidden" id="bbox3" name="map[bounds][3]">

                    <div id="bboxmap"><noscript>Please enable javascript and refresh this page to choose your location using our interactive map. Sorry for the inconvenience!</noscript></div>

                    </td><td class="help">
                    <p>Drag and zoom the map to change the area that will be printed.</p><p>You can scroll with your mouse, double-click or use the + and - buttons to zoom.</p><p>Be sure to zoom close enough to see nearby streets!</p>
                    <p class="thoughtful">If you want an off-center map you can drag the green marker directly to fine tune your precise meeting point.</p>
                    </td></tr>

                    <tr class="last"><td class="inputs">
                    <p class="field">
                        <textarea type="text" name="place[full-note]" id="fullnote" rows="8">{$request.post.place.full-note|escape}</textarea>
                        <br>
                        <span id="charcount">300 remaining</span>
                    </p>
                    </td><td class="help">
                        <p>Include a personal note for your recipients.<br><em>(300 character limit)</em></p>
                    <p class="field full" id="privacy">
                        <label for="rdprivate"><input type="radio" id="rdprivate" name="map[privacy]" value="unlisted" checked>This note is <em>private</em>.</label><br>
                        <label for="rdpublic"><input type="radio" id="rdpublic" name="map[privacy]" value="public">Make this note <em>public</em>?</label><br>
<span id="publictip" class="note"><strong>"Public" definition:</strong> this note can be displayed alongside the place you selected on maps like the one on our homepage. Anybody who visits the site will be able to read it.</span>
<span id="privatetip" class="note"><strong>"Private" definition:</strong> this note will be printed on whatever cards you make and share, but nobody other than the recipients you choose will be able to read it.</span>
                    </p>
                    <p class="thoughtful">Remember that the recipient might be reading this at a very difficult moment, so please think carefully about what you want to write here!</p>
                    
                    </td></tr>

                    </table>
                    
                    <h3>Who's this map for?</h3>                    

                    <p>
                        Enter the names and email addresses of people you'd like to share this Safety Map with.
                    </p>
                    <ol id="recipients">
                        <li>
                            <label for="recipients[0][name]">name:</label><input type="text" name="recipients[0][name]" value="{$request.post.recipients.0.name|escape}" size="15"> <label for="recipients[0][email]">email:</label><input type="email" name="recipients[0][email]" placeholder="e.g. them@there.com" value="{$request.post.recipients.0.email|escape}" size="35"> <a class="addrecipient" href="">Add another</a>
                        </li>
                        
                        {foreach from=$request.post.recipients key="index" item="recipient"}
                            {if $index >= 1}
                                <li>
                                    <label for="recipients[{$index}][name]">name:</label><input type="text" name="recipients[{$index}][name]" value="{$request.post.recipients.$index.name|escape}" size="15"> <label for="recipients[{$index}][email]">email:</label><input type="email" name="recipients[{$index}][email]" placeholder="e.g. them@there.com" value="{$request.post.recipients.$index.email|escape}" size="35"> <a class="addrecipient" href="">Add another</a>
                                </li>
                            {/if}
                        {/foreach}
                    </ol>
                    <p>
                        Alternatively, <input type="checkbox" name="personal" id="personal"> <label for="personal">check this box if this map is just for you.</label>
                    </p>
                        
                    <h3>You're almost done.</h3>

                    <p>Now that you've chosen a safe place to meet, you're ready to make and print your maps.</p>

                    <p class="field split">
                        <label for="sender[name]">What's your name or nickname?</label><input type="text" name="sender[name]" value="{$request.post.sender.name|escape}" placeholder="e.g. Your Name"><br>
                        <label for="sender[email]">What's your email address?</label><input type="email" name="sender[email]" value="{$request.post.sender.email|escape}" placeholder="e.g. you@example.com" size="35">
                    </p>
 
                    <p id="done"><button type="submit">Go!</button></p>

                </form>
                                    
            </div>
        
        </div>

        {include file="footer.htmlf.tpl"}

    </body>
</html>

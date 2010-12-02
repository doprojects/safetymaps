<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>Safety Maps: Make A Safety Map</title>
        <link rel="stylesheet" type="text/css" href="fonts/stylesheet.css" />
        <link rel="stylesheet" type="text/css" href="style.css" />
        <link rel="stylesheet" type="text/css" href="make-a-safety-map.css" />
        <script type="text/javascript" src="jquery.min.js"></script>
        <script type="text/javascript" src="modestmaps.js"></script>
        <script type="text/javascript" src="cloudmade.js"></script>
        <script type="text/javascript" src="markerclip.js"></script>
        <script type="text/javascript" src="anyzoom.js"></script>
        <script type="text/javascript" src="make-a-safety-map.js"></script>
        <!-- script type="text/javascript" src="h5f.js"></script -->
        <script type="text/javascript">{literal}

$(document).ready(function() {

    //H5F.setup(document.getElementById('mapform'));

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

    // deal with "Other (please specify)"
    $('#emergencyplace').bind('change', function() {
        if($('#otherplace').attr('selected')) {
            if ($('#otherinput').length == 0) {
                $('<input id="otherinput" type="text"></input>')
                    .bind('change', function() {
	                $('#otherplace').attr('value', $(this).attr('value'));
                    })
                    .insertAfter($('#emergencyplace'));
            }
        }
        else {
            $('#otherinput').remove();
        }
    });

    // deal with additional recipients
    $('a.addrecipient').live('click', function() {
        // TODO: can we clone a node and find/replace instead?
        // or use jquery templates?
        try {
            var index = $('#recipients li').length;
            $('#recipients').append($('<li>'
                + '<label for="recipients['+index+'][name]">name:<\/label><input type="text" name="recipients['+index+'][name]" size="15">'
                + ' <label for="recipients['+index+'][email]">email:<\/label><input type="email" placeholder="e.g. them@there.com" name="recipients['+index+'][email]" size="35"> <a class="addrecipient" href="">Add another<\/a> <a href="" class="removerecipient">Remove this one?<\/a>'
                + '<\/li>'));
            $(document.body).trigger('search-needs-adjusting');
        }
        catch(e) {
            console.log(e);
        }
        return false;
    });

    // undo additional recipients
    $('a.removerecipient').live('click', function() {
        try {
            $(this).parent('li').remove();
            $(document.body).trigger('search-needs-adjusting');
        }
        catch(e) {
            console.log(e);
        }
        return false;
    });

}); 
        {/literal}</script>
    </head>
    <body>

        {include file="header.htmlf.tpl" current="make"}

        <div id="main">
        
            <h2>Make a Safety Map.</h2>
                
            <!-- TODO: demand javascript, the map selection is impossible without it -->
            <p class="intro">By answering these few simple questions, you can make a 
               custom map of a place you think it will be safe for your
               friends, family or loved ones to meet in the event of an
               emergency.</p>

             <p class="intro">You can print this map out in a variety of formats, share 
                it via email, or both. Either way, you'll be able to 
                include a personal message to recipients.</p>
        
            <div id="make">

                <form id="mapform" method="POST" action="maps.php">
                
                <table>
                <tr class="first"><td class="inputs">

                    <p>In case of 
                        <select id="emergencyplace" name="place[emergency]" required>
                            <option>an emergency</option>
                            <option>an earthquake</option>
                            <option>a blackout</option>
                            <option>a fire</option>
                            <option>a flood</option>
                            <option>a public transportation failure</option>
                            <option id="otherplace" value="other">Other (please specify)</option>
                        </select>
                        let's meet at
                        <input type="text" name="place[name]" size="25" value="" required>.
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

                    <div id="bboxmap"></div>

                    </td><td class="help">
                    <p>Drag the map to change the area that will be printed. Drag the green marker to move it to the precise meeting point. Be sure to zoom close enough to see nearby streets!</p>
                    </td></tr>

                    <tr class="last"><td class="inputs">
                    <p class="field">
                        <textarea type="text" name="place[full-note]" id="fullnote" rows="6"></textarea>
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
                        Enter the names and email addresses of people you'd like to share this Safety Map with. Leave blank if this is just for you.
                    </p>
                    <ol id="recipients">
                        <li>
                            <label for="recipients[0][name]">name:</label><input type="text" name="recipients[0][name]" size="15" required> <label for="recipients[0][email]">email:</label><input type="email" name="recipients[0][email]" placeholder="e.g. them@there.com" value="" size="35" required> <a class="addrecipient" href="">Add another</a>
                        </li>
                    </ol>
                        
                    <h3>You're almost done.</h3>

                    <p>Now that you've chosen a safe place to meet, you're ready to make and print your maps.</p>

                    <p class="field split">
                        <label for="sender[name]">What's your name or nickname?</label><input type="text" name="sender[name]" value="" placeholder="e.g. Your Name" required><br>
                        <label for="sender[email]">What's your email address?</label><input type="email" name="sender[email]" value="" placeholder="e.g. you@example.com" size="35" required>
                    </p>
 
                    <p id="done"><button type="submit">Go!</button></p>

                </form>
                                    
            </div>
        
        </div>

        {include file="footer.htmlf.tpl"}

    </body>
</html>
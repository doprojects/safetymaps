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
        <script type="text/javascript" src="h5f.js"></script>
        <script type="text/javascript">{literal}

$(document).ready(function() {

    H5F.setup(document.getElementById('mapform'));

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

    var autoSummary = true;
    $('#fullnote').bind('change', function() {
       if (autoSummary) {
           var summary = $('#fullnote').attr('value');
           if (summary.length > 64) { // TODO: check this summary length
               summary = summary.substring(0,64);
           }
           var sentenceEnd = Math.max(summary.lastIndexOf('.'), summary.lastIndexOf('!'), summary.lastIndexOf('?'));
           if (sentenceEnd > 0) {
               summary = summary.substring(0,sentenceEnd+1);
           }
           $('#shortnote').attr('value', summary);
       } 
    });
    $('#fullnote').bind('keyup', function() { $('#fullnote').trigger('change') });
    $('#shortnote').bind('change', function() {
       console.log('disabling autoSummary');
       autoSummary = false;
    });

    // deal with additional recipients
    $('a.addrecipient').live('click', function() {
        // TODO: can we clone a node and find/replace instead?
        // or use jquery templates?
        try {
            var index = $('#recipients p').length;
            $('#recipients').append($('<p class="field full">'+nth(index+1)+' recipient:<br>'
                + '<label for="recipients['+index+'][name]">name:<\/label><input type="text" name="recipients['+index+'][name]" size="15">'
                + ' <label for="recipients['+index+'][email]">email:<\/label><input type="email" placeholder="e.g. them@there.com" name="recipients['+index+'][email]" size="35"> <a class="addrecipient" href="">Add another...<\/a> <a href="" class="removerecipient">Remove this one?<\/a>'
                + '<\/p>'));
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
            $(this).parent('p').remove();
            $('#recipients p').each(function(index) {
                var html = $(this).html();
                html = html.replace(/(\d+[a-z][a-z])/g, function() {
                    return nth(index+1);
                });
                html = html.replace(/(\[\d+\])/g, function() {
                    return '['+index.toString()+']';
                });
                $(this).html(html)
            });
            $(document.body).trigger('search-needs-adjusting');
        }
        catch(e) {
            console.log(e);
        }
        return false;
    });

    // number (1,2,3,4,5) to string (1st,2nd,3rd,4th,5th)
    function nth(n) {
        var s = n.toString();        if (n == 11 || n == 12 || n == 13) {            return s+'th';        }
        var last = s.charAt(s.length-1);
        if (last == '1') {
            return s+'st';
        }
        else if (last == '2') {
            return s+'nd';
        }
        else if (last == '3') {
            return s+'rd';
        }
        return s+'th';
    }

}); 
        {/literal}</script>
    </head>
    <body>

        {include file="header.htmlf.tpl" current="make"}

        <div id="main">
        
            <!-- TODO: demand javascript, the map selection is impossible without it -->
        
            <div id="make">

                <p>By answering these few simple questions, you can make a custom map of a 	place you think it will be safe* for your friends, family or loved ones to meet in 	the event of an emergency.</p>

                <p>You can print this map out in a variety of formats, share it via email, or both. 	Either way, you'll be able to include a personal message to recipients.</p>

                <h2>Are you ready to get started?</h2>
                
                <form id="mapform" method="POST" action="maps.php">
                
                    <p class="field split">
                        <label for="sender[name]">What's your name or nickname?</label><input type="text" name="sender[name]" value="" placeholder="e.g. Your Name" required><br>
                        <label for="sender[email]">What's your email address?</label><input type="email" name="sender[email]" value="" placeholder="e.g. you@example.com" size="35" required>
                    </p>

                    <h3><span class="step">1</span> Who's this map for?</h3>
                    <p>
                        Enter the names and email addresses of people you'd like to share this Safety Map with.
                    </p>
                    <div id="recipients">
                        <p class="field full">1st recipient:<br>
                            <label for="recipients[0][name]">name:</label><input type="text" name="recipients[0][name]" size="15" required> <label for="recipients[0][email]">email:</label><input type="email" name="recipients[0][email]" placeholder="e.g. them@there.com" value="" size="35" required> <a class="addrecipient" href="">Add another...</a>
                        </p>
                    </div>
                        
                    <h3><span class="step">2</span> Where will you meet?</h3>
                    
                    <p class="field split">
                        <label for="place[emergency]">What kind of emergency is this map for?</label><br>
                        <select id="emergencyplace" name="place[emergency]" required>
                            <option>an emergency</option>
                            <option>an earthquake</option>
                            <option>a blackout</option>
                            <option>a fire</option>
                            <option>a flood</option>
                            <option>a public transportation failure</option>
                            <option id="otherplace" value="other">Other (please specify)</option>
                        </select>
                    </p>                             

                    <p class="field">
                        <label for="place[name]">What would you like to call this meeting place?</label><br>
                        <input type="text" name="place[name]" size="50" value="" required><br>
                        (You can call it anything you like.)
                    </p>                                        

                    <p>Drag the map to change the area that will be printed. Drag the green marker to move it to the precise meeting point. Be sure to zoom close enough to see nearby streets!</p>
                    <input type="hidden" id="loc0" name="place[location][0]">
                    <input type="hidden" id="loc1" name="place[location][1]">
                    <br>
                    <input type="hidden" id="bbox0" name="map[bounds][0]">
                    <input type="hidden" id="bbox1" name="map[bounds][1]">
                    <input type="hidden" id="bbox2" name="map[bounds][2]">
                    <input type="hidden" id="bbox3" name="map[bounds][3]">
                    <div id="bboxmap"></div>

                    <h3><span class="step">3</span> Describe your map</h3>
                                        
                    <p class="field">
                        <label for="place[full-note]">Include a personal note for your recipients:</label><br>
                        <textarea type="text" name="place[full-note]" id="fullnote" rows="6"></textarea>
                        (Please note that everyone you send this map to will get the same note.)
                    </p>
                    
                    <p class="field">
                        <label for="place[short-note]">Here's a summary of your note, please edit it if it doesn't make sense:</label><br>
                        <input type="text" name="place[short-note]" id="shortnote" value="" size="50">
                        <!-- derive from full note, optionally? -->
                    </p>
                    
                    <p class="field full" id="privacy">
                        Personal note privacy:<br>
                        <label for="rdpublic"><input type="radio" id="rdpublic" name="map[privacy]" value="public">Make this note public? (what?)</label><br>
                        <label for="rdprivate"><input type="radio" id="rdprivate" name="map[privacy]" value="unlisted" checked>This note is private. (what?)</label><br>
<span id="publictip" class="note"><strong>"Public" definition:</strong> this note will be displayed alongside the place you selected on the <a href="maps.php">collective map</a>. Anybody who visits the site will be able to read it.</span>
<span id="privatetip" class="note"><strong>"Private" definition:</strong> this note will be printed on whatever cards you make and share, but nobody other than the recipients you choose will be able to read it.</span>
                    </p>
                    
                    <h3><span class="step">4</span> That's it! You're done.</h3>                    
                    
                    <p>Now that you've chosen a safe place to meet, you're ready to make and print a wallet-sized card to share with your friends and loved ones.</p>
                    
                    <!-- TODO: build a preview here, and add a checkbox that you have to select to say you've proof-read the preview -->

                    <!-- div id="preview">
                        [ preview goes here ]
                    </div>
                    
                    <p class="full">
                        Does everything look OK?<br>
                        <label for="verify"><input type="checkbox" name="verify">Yes, that looks right.</label>
                    </p -->
                    
                    <p id="done"><button type="submit">Go!</button></p>
                </form>
                                    
            </div>
        
        </div>

        {include file="footer.htmlf.tpl"}

    </body>
</html>

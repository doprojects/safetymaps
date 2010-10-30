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
                        <label for="sender[name]">What's your name or nickname?</label><input type="text" name="sender[name]" value=""><br><!-- TODO: required or optional? -->
                        <label for="sender[email]">What's your email address?</label><input type="text" name="sender[email]" value=""><!-- TODO: required or optional? -->
                    </p>

                    <h3><span class="step">1</span> Who's this map for?</h3>
                    <p>
                        Enter the names and email addresses of people you'd like to share this Safety Map with.
                    </p>
                    <div id="recipients">
                        <p class="field full">1st recipient:<br>
                            <label for="recipients[0][name]">name:</label><input type="text" name="recipients[0][name]" size="15"> <label for="recipients[0][email]">email:</label><input type="text" name="recipients[0][email]" value="" size="35"> <a class="addrecipient" href="">Add another...</a>
                        </p>
                    </div>
                        
                    <h3><span class="step">2</span> Where will you meet?</h3>
                    
                    <p>Drag the map to change the area that will be printed. <!-- TODO Drag the green marker to move it to the precise meeting point.--></p>
                    <!-- TODO: better copy? -->
                    <!-- TODO: editable center point -->
                    <!-- TODO: buttons for zooming -->                    
                    <input type="hidden" id="loc0" name="place[location][0]">
                    <input type="hidden" id="loc1" name="place[location][1]">
                    <br>
                    <input type="hidden" id="bbox0" name="map[bounds][0]">
                    <input type="hidden" id="bbox1" name="map[bounds][1]">
                    <input type="hidden" id="bbox2" name="map[bounds][2]">
                    <input type="hidden" id="bbox3" name="map[bounds][3]">
                    <div id="bboxmap">
                        <img src="cross_sm.png" style="margin-left:-25px; margin-top:-25px; position:absolute; left: 50%; top: 50%; z-index:1000;">
                        <p id="zoom" style="position:absolute; margin: 5px; padding: 0; left: 0; top: 0; z-index:2000;"><a href="#" id="zoomin" style="background:#fff; padding: 0px 3px; text-decoration: none;">zoom in</a> <a href="#" id="zoomout" style="background:#fff; padding: 0px 3px; text-decoration: none;">zoom out</a></p>
                        <p id="search" style="position:absolute; margin: 5px; padding: 0; left: 0; bottom: 0; z-index:2000;"><input type="text" name="search"></p>
                    </div>

                    <h3><span class="step">3</span> Describe your map</h3>
                    
                    <p class="field split">
                        <label for="place[emergency]">What kind of emergency is this map for?</label><br>
                        <select id="emergencyplace" name="place[emergency]">
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
                        <input type="text" name="place[name]" size="50" value=""><br>
                        (You can call it anything you like.)
                    </p>                                        
                                        
                    <p class="field">
                        <label for="place[full-note]">Include a personal note for your recipients:</label><br>
                        <textarea type="text" name="place[full-note]" rows="6"></textarea>
                        (Please note that everyone you send this map to will get the same note.)
                    </p>
                    
                    <p class="field">
                        <label for="place[short-note]">Here's a summary of your note, please edit it if it doesn't make sense:</label><br>
                        <input type="text" name="place[short-note]" value="" size="50">
                        <!-- derive from full note, optionally? -->
                    </p>
                    
                    <p class="field full">
                        Personal note privacy:<br>
                        <span class="note"><strong>"Public" definition:</strong> this note will be displayed alongside the place you selected on the <a href="#meeting-points">collective map</a>. Anybody who visits the site will be able to read it.</span>
                        <span class="note"><strong>"Private" definition:</strong> this note will be printed on whatever cards you make and share, but nobody other than the recipients you choose will be able to read it.</span>
                        <label for="rdpublic"><input type="radio" id="rdpublic" name="map[privacy]" value="public"> Make this note public?</label><br>
                        <label for="rdprivate"><input type="radio" id="rdprivate" name="map[privacy]" value="unlisted" checked> This note is private.</label><br>
                    </p>
                    
                    <h3><span class="step">4</span> Just two more questions...</h3>

                    <p class="field split">
                        <label for="map[paper]">What size will you print this out at?</label>
                        <select name="map[paper]">
                            <option value="letter">Letter</option>
                            <option value="a4">A4</option>
                        </select>
                    </p>
                    
                    <p class="field formats">                        
                        What format would you like?<br>
                        <label for="4up"><img src="4up.gif"><input type="radio" name="map[format]" checked id="4up" value="4up">Four cards</label>
                        <label for="2up-fridge"><img src="2up-fridge.gif"><input type="radio" name="map[format]" id="2up-fridge" value="2up-fridge">Two cards, fridge poster</label>
                        <label for="poster"><img src="poster.gif"><input type="radio" name="map[format]" id="poster" value="poster">Single-page poster</label>
                    </p>                    
                    
                    <h3><span class="step">5</span> That's it! You're done.</h3>                    
                    
                    <p>Now that you've chosen a safe place to meet, you're ready to make and print a wallet-sized card to share with your friends and loved ones.</p>
                    
                    <!-- TODO: build a preview here, and add a checkbox that you have to select to say you've proof-read the preview -->

                    <div id="preview">
                        [ preview goes here ]
                    </div>
                    
                    <p class="full">
                        Does everything look OK?<br>
                        <label for="verify"><input type="checkbox" name="verify">Yes, that looks right.</label>
                    </p>
                    
                    <p id="done"><button type="submit">Go!</button></p>
                    
                    <!--p><button>Print</button></p-->
                    <!-- TODO: what next? -->
                    <!--p><button>Answer a few more questions</button></p>
                    <p><button>Make another</button></p-->
                </form>
                                    
            </div>
        
        </div>

        {include file="footer.htmlf.tpl"}

    </body>
</html>

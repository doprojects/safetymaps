<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>Safety Maps: About</title>
        <link rel="stylesheet" type="text/css" href="fonts/stylesheet.css" />
        <link rel="stylesheet" type="text/css" href="style.css" />
    </head>
    <body>
        <div id="header">
            <h1>Safety Maps</h1>
            <h2>
                Where will you meet your loved ones in<br>
                the event of a natural disaster or other<br>
                emergency?
            </h2>
            <img src="logo.png" alt="Safety Maps Logo">
            <div id="questions">
                <p>
                    What happens when disaster strikes?<br>
                    You're separated from those you care about.<br>
                    Your phone doesn't work anymore...<br>
                    Where do you think it's a safe place to gather?<br>
                    Use Safety Maps to choose and share safe<br>
                    meeting points with your familiy and friends.
                </p>
                <p>
                    <a href="#create-your-map">Click here to start a scenario.</a>
                </p>
            </div>
            <div id="nav">
                <ul>
                    <li><a href="index.html" class="current">Home</a></li>
                    <li><a href="about.html">About</a></li>
                    <li><a href="make_a_safety_map.html">Make a Safety Map</a></li>
                    <li><a href="#meeting-points">See meeting points other people have chosen</a></li>
                    <li><a href="#scenarios">View disaster scenarios</a></li>
                </ul>
            </div>
        </div>
        <div id="main">
            <div id="maps">
                {if $map}
                    <pre>{$map.features.0|@print_r:1|escape:'html'}</pre>
                {elseif $maps}
                    <pre>{$maps|@print_r:1|escape:'html'}</pre>
                {/if}
            </div>
        </div>
        <div id="footer">
            <p>&copy; 2010 Do Projects. Credits, Privacy Policy, etc.</p>
        </div>
    </body>
</html>

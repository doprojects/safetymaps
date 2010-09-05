<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<title>Safety Maps Post Form</title>
    <style type="text/css" title="text/css">
    <!--
        body { font: 12px/1.2 sans-serif; }
    
    -->
    </style>
</head>
<body>

<form method="POST" action="post.php">
    <p>
        User name <input type="text" name="sender[name]">
        email <input type="text" name="sender[email]">
    </p>
    <p>
        Place name <input type="text" name="place[name]">
    </p>
    <p>
        Location
        <input type="text" name="place[location][0]">
        <input type="text" name="place[location][1]">
    </p>
    <p>
        Full Note
        <input type="text" name="place[full-note]">
    </p>
    <p>
        Short Note
        <input type="text" name="place[short-note]">
    </p>
    <p>
        Privacy
        <select name="map[privacy]">
            <option label="Public" value="public">Public</option>
            <option label="Unlisted" value="unlisted">Unlisted</option>
        </select>
    </p>
    <p>
        Bounds
        <input type="text" name="map[bounds][0]">
        <input type="text" name="map[bounds][1]">
        <input type="text" name="map[bounds][2]">
        <input type="text" name="map[bounds][3]">
    </p>
    <p>
        Paper
        <select name="map[paper]">
            <option label="Letter" value="letter">Letter</option>
            <option label="A4" value="a4">A4</option>
        </select>
    </p>
    <p>
        Format
        <select name="map[format]">
            <option label="Four cards" value="4up">Four cards</option>
            <option label="Two cards, fridge poster" value="2up-fridge">Two cards, fridge poster</option>
            <option label="Single-page poster" value="poster">Single-page poster</option>
        </select>
    </p>
    <p>
        1st Recipient name <input type="text" name="recipients[0][name]">
        email <input type="text" name="recipients[0][email]">
    </p>
    <p>
        2nd Recipient name <input type="text" name="recipients[1][name]">
        email <input type="text" name="recipients[1][email]">
    </p>
    <p>
        3rd Recipient name <input type="text" name="recipients[2][name]">
        email <input type="text" name="recipients[2][email]">
    </p>
    <p>
        <input type="submit">
    </p>
</form>

<h3>Actual</h3>
<pre><?=print_r($_POST, 1)?></pre>

<h3>Expected</h3>
<pre>
{
    sender: { name: ___, email: ___ }
    place:
    {
        name: ___,
        location: [ ___, ___ ],
        full-note: ___,
        short-note: ___
    },
    map:
    {
        privacy: ___,
        bounds: [ ___, ___, ___, ___ ],
        paper: ___,
        format: ___
    },
    recipients:
    [
        { name: ___, email: ___ },
        { name: ___, email: ___ },
        ...
    ]
}
</pre>

</body>
</html>

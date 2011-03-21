$(document).ready(function()
{
    function add_mapmarker(map, location)
    {
        var img = new Image();
        
        img.onload = function()
        {
            var pos = map.locationPoint(location);
            var anc = document.createElement('a');

            anc.className = 'map-link';
            anc.style.width = this.width.toFixed(0) + 'px';
            anc.style.height = this.height.toFixed(0) + 'px';
            anc.style.left = (pos.x - this.width/2).toFixed(0) + 'px';
            anc.style.top = (pos.y - this.height/2).toFixed(0) + 'px';
            anc.title = location.name;
            anc.href = location.href;

            anc.appendChild(img);
            map.parent.appendChild(anc);
        }
        
        var loc = location.href;
        img.src = loc.replace(/\/maps.php(\/.*)?$/, '/images/cross_round_sm.png');
    }

    function make_map(element, location)
    {
        var mm = com.modestmaps;
        var cm = new mm.CloudMadeProvider('1a914755a77758e49e19a26e799268b7', '22677');
        var dim = {x: $(element).width(), y: $(element).height()};
        var map = new mm.Map(element, cm, dim, []);
        
        map.setExtent(location.extent);
        add_roundy_corners(map);
        
        add_mapmarker(map, location);
    }

    $('#maps .map-info').each(function()
    {
        var lat = parseFloat($(this).find('.geo .latitude').text());
        var lon = parseFloat($(this).find('.geo .longitude').text());
        var west = parseFloat($(this).find('.geo .bbox-west').text());
        var east = parseFloat($(this).find('.geo .bbox-east').text());
        var south = parseFloat($(this).find('.geo .bbox-south').text());
        var north = parseFloat($(this).find('.geo .bbox-north').text());
        var href = $(this).find('a.link').attr('href');
        var name = $(this).find('.place-name').text();
        
        var extent = [{lat: north, lon: west}, {lat: south, lon: east}];
        var location = {lat: lat, lon: lon, href: href, name: name, extent: extent};
        var element = $(this).find('.map-area')[0];
        
        make_map(element, location);
    });
});

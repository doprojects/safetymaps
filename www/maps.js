$(document).ready(function() {
    // make a map!
    var mm = com.modestmaps;
    //var template = 'http://{S}tile.openstreetmap.org/{Z}/{X}/{Y}.png';
    //var domains = [ '', 'a.', 'b.', 'c.' ];
    //var osm = new mm.TemplatedMapProvider(template, domains);
    var cm = new mm.CloudMadeProvider('1a914755a77758e49e19a26e799268b7','22677');
    var map = new mm.Map('map', cm);
    var firstLocation = null;
    
    var markerClip = new MarkerClip(map);
    $('.geo').each(function() {
        var $geo = $(this);
        var lat = $geo.find('.latitude').text();
        var lon = $geo.find('.longitude').text();
        var marker = markerClip.createDefaultMarker(63,63);
        marker.className = 'marker';
        var $geolink = $geo.parent().find('a');
        $(marker).mouseover(function() {
            $(this).addClass('hover');
            $geolink.addClass('hover');
        });
        $(marker).mouseout(function() {
            $(this).removeClass('hover');
            $geolink.removeClass('hover');
        });
        $geolink.mouseover(function() {
            $(marker).trigger('mouseover');
        });
        $geolink.mouseout(function() {
            $(marker).trigger('mouseout');
        });
        var location = new mm.Location(lat, lon);
        var offset = new mm.Point(-63/2,-63/2);
        marker.title = $geolink.text();
        markerClip.addMarker(marker, location, offset);
        if (!firstLocation) firstLocation = location;
    });

    if (firstLocation) {
      // TODO: use extent of all current markers
      map.setCenterZoom(firstLocation, 13);
    }
});

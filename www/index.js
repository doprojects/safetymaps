$(document).ready(function() {
    // make a map!
    var mm = com.modestmaps;
    //var template = 'http://{S}tile.openstreetmap.org/{Z}/{X}/{Y}.png';
    //var domains = [ '', 'a.', 'b.', 'c.' ];
    //var osm = new mm.TemplatedMapProvider(template, domains);
    var cm = new mm.CloudMadeProvider('1a914755a77758e49e19a26e799268b7','22677');
    var map = new mm.Map('map', cm);
    map.setCenterZoom(new mm.Location(40.7143528, -74.0059731), 13);
    
    // for demo porpoises, add some random locations to the points:
    var extent = map.getExtent();
    var minLat = extent[1].lat;
    var maxLat = extent[0].lat;
    var minLon = extent[0].lon;
    var maxLon = extent[1].lon;
    var latRange = maxLat - minLat;
    var lonRange = maxLon - minLon;
    $('.geo .latitude').each(function() {
        $(this).text(minLat + latRange*Math.random());
    });
    $('.geo .longitude').each(function() {
        $(this).text(minLon + lonRange*Math.random());
    });

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
    });
});

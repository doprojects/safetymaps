$(document).ready(function() {

    // make a map!
    var mm = com.modestmaps;

    var provider = new mm.CloudMadeProvider('1a914755a77758e49e19a26e799268b7','22677');
    var bboxmap = new mm.Map('bboxmap', provider, null, [ new AnyZoomHandler() ]);
    bboxmap.setCenterZoom(new mm.Location(0,0), 1);

    function onMapChange() {
        var extent = bboxmap.getExtent();
        if (extent[0].lat-extent[1].lat > 0.001 && extent[1].lon-extent[0].lon > 0.001) {
            var loc = mm.Location.interpolate(extent[0], extent[1], 0.5);
            $("input#loc0").attr('value', loc.lat.toFixed(6));
            $("input#loc1").attr('value', loc.lon.toFixed(6));
            $("input#bbox0").attr('value', extent[0].lat.toFixed(6));
            $("input#bbox1").attr('value', extent[0].lon.toFixed(6));
            $("input#bbox2").attr('value', extent[1].lat.toFixed(6));
            $("input#bbox3").attr('value', extent[1].lon.toFixed(6)); 
        }
    };
    bboxmap.addCallback('drawn', onMapChange);
    onMapChange();

    // TODO template?
    var $mark = $('<img src="cross_sm.png" style="margin-left:-25px; margin-top:-25px; position:absolute; left: 50%; top: 50%; z-index:1000;">');
    var $zoom = $('<p id="zoom" style="position:absolute; margin: 5px; padding: 0; right: 0; top: 0; z-index:2000;"></p>')
                    .append('<a href="#" id="zoomin" style="background:#fff; padding: 0px; text-decoration: none;"><img src=""></a><br>')
                    .append('<a href="#" id="zoomout" style="background:#fff; padding: 0px; text-decoration: none;"><img src=""></a>');

    $('#bboxmap').append($mark);
    $('#bboxmap').append($zoom);

    $('#zoomin').bind('click', function() { bboxmap.zoomIn(); return false; });
    $('#zoomout').bind('click', function() { bboxmap.zoomOut(); return false; });

    var $search = $('<p style="position:absolute; margin: 0px; padding: 0px; z-index:2000;"></p>')
                      .append('<form id="searchform"><input type="text" id="search" name="search"></form>');
    $(document.body).append($search);
    
    $(document.body).bind('search-needs-adjusting', function() {
        var mapOffset = $('#bboxmap').offset();
        mapOffset.left += 5;
        mapOffset.top += 5;
        $search.offset(mapOffset);
    });
    $(window).load(function() {
        $(document.body).trigger('search-needs-adjusting');
    });
    setTimeout(function() {
        $(document.body).trigger('search-needs-adjusting');
    }, 100);

    $('#searchform').bind('submit', function() {
        var q = $('#search').attr('value');
        $('#search').attr('disabled', 'disabled');
        $.ajax({
            dataType: 'jsonp',
            url: 'http://www.mapquestapi.com/geocoding/v1/address' +
                 '?inFormat=kvp&outFormat=json' +
                 '&key=' + escape('Dmjtd|lu612007nq,20=o5-50zah') +
                 '&location=' + escape(q) +
                 '&callback=?',
            success: onPlaceSearch
        });
        return false;
    } );

    function onPlaceSearch(rsp) {
        $('#search').attr('disabled', '');
        if (rsp && rsp.results && rsp.results.length) {
            var result = rsp.results[0];
            if (result.locations && result.locations.length) {
                var loc = result.locations[0].displayLatLng;
                bboxmap.setCenterZoom(new mm.Location(loc.lat, loc.lng), 10);
            }
        }
    }

});


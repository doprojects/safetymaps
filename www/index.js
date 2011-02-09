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
        
        img.src = 'images/cross_round_lg.png';
    }

    function cluster(max_diameter)
    {
        var locations = [];

       /**
        * Adapted from http://www.johndcook.com/python_longitude_latitude.html
        */
        function distance(loc1, loc2)
        {
            var deg2rad = Math.PI/180;
    
            var p1 = (90 - loc1.lat) * deg2rad;
            var p2 = (90 - loc2.lat) * deg2rad;
    
            var t1 = loc1.lon * deg2rad;
            var t2 = loc2.lon * deg2rad;
    
            var cos = Math.sin(p1) * Math.sin(p2) * Math.cos(t1 - t2) + Math.cos(p1) * Math.cos(p2);
            var arc = Math.acos(cos);
    
            return arc * 6378100;
        }
        
        function add(location)
        {
            for(var i = 0; i < locations.length; i++)
            {
                if(distance(location, locations[i]) > max_diameter) {
                    return false;
                }
            }
            
            // unshift to keep everything in reverse-chronological order.
            locations.unshift(location);
            return true;
        }
        
        function extent()
        {
            var minlat = locations[0].lat,
                minlon = locations[0].lon,
                maxlat = locations[0].lat,
                maxlon = locations[0].lon;
            
            for(var i = 1; i < locations.length; i++)
            {
                minlat = Math.min(minlat, locations[i].lat);
                minlon = Math.min(minlon, locations[i].lon);
                maxlat = Math.max(maxlat, locations[i].lat);
                maxlon = Math.max(maxlon, locations[i].lon);
            }
            
            var latspan = (maxlat - minlat),
                lonspan = (maxlon - minlon);
            
            if(latspan < 0.04 && lonspan < 0.04) {
                var south = minlat - 0.02,
                    north = maxlat + 0.02,
                    west  = minlon - 0.02,
                    east  = maxlon + 0.02;
            
            } else {
                var south = minlat - latspan * 0.1,
                    north = maxlat + latspan * 0.1,
                    west  = minlon - lonspan * 0.1,
                    east  = maxlon + lonspan * 0.1;
            }

            return [{lat: south, lon: west}, {lat: north, lon: east}];
        }
        
        function make_map(element)
        {
            var mm = com.modestmaps;
            var cm = new mm.CloudMadeProvider('1a914755a77758e49e19a26e799268b7', '22677');
            var dim = {x: $(element).width(), y: $(element).height()};
            var map = new mm.Map(element, cm, dim, []);

            map.setExtent(extent());
            
            add_roundy_corners(map, 600, 400);
            
            for(var i = 0; i < locations.length; i++)
            {
                add_mapmarker(map, locations[i]);
            }
        }
        
        return {add: add, make_map: make_map};
    }
    
    var clusters = [cluster(10000)];
    
    $('#maps .map-info').each(function()
    {
        var lat = parseFloat($(this).find('.geo .latitude').text());
        var lon = parseFloat($(this).find('.geo .longitude').text());
        var href = $(this).find('a.link').attr('href');
        var name = $(this).find('a.link').text();

        var location = {lat: lat, lon: lon, href: href, name: name};
        
        for(var i = 0; i < clusters.length; i++)
        {
            if(clusters[i].add(location)) {
                return;
            }
        }
        
        var c = cluster(10000);
        c.add(location);
        clusters.push(c);
    });
    
    $('#maps').empty();
    
    for(var i = 0; i < Math.min(3, clusters.length); i++)
    {
        var el = document.createElement('div');
        el.className = 'cluster-map';
        $('#maps').append(el);
        clusters[i].make_map(el);
    }
});

$(document).ready(function()
{
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
            
            locations.push(location);
            return true;
        }
        
        return {add: add};
    }
    
    var clusters = [cluster(10000)];
    
    $('#maps .map').each(function()
    {
        var lat = parseFloat($(this).find('.geo .latitude').text());
        var lon = parseFloat($(this).find('.geo .longitude').text());
        var name = $(this).find('a.link').text();

        var location = {lat: lat, lon: lon, name: name};
        
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
    
    var locations = [];
    
    $('#maps .map').each(function()
    {
        var lat = parseFloat($(this).find('.geo .latitude').text());
        var lon = parseFloat($(this).find('.geo .longitude').text());
        var href = $(this).find('a.link').attr('href');
        var name = $(this).find('a.link').text();
        
        // unshift so that the newest location ends up on top
        locations.unshift({lat: lat, lon: lon, href: href, name: name});
    });
    
    function mapmarker(map, location)
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
        
        img.src = 'cross_round.png';
    }

    function indexmap(id, locations)
    {
        $('#'+id).empty();
    
        var mm = com.modestmaps;
        var cm = new mm.CloudMadeProvider('1a914755a77758e49e19a26e799268b7', '22677');
        var map = new mm.Map(id, cm, {x: 700, y: 400}, []);
        
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
        
        var south = minlat - (maxlat - minlat) * .1,
            north = maxlat + (maxlat - minlat) * .1,
            west  = minlon - (maxlon - minlon) * .1,
            east  = maxlon + (maxlon - minlon) * .1;
        
        map.setExtent([{lat: south, lon: west}, {lat: north, lon: east}]);
        
        for(var i = 0; i < locations.length; i++)
        {
            mapmarker(map, locations[i]);
        }
    }
    
    var map = indexmap('map', locations);

});

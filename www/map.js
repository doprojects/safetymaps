function show_preview_map(element, lat, lon, south, west, north, east, base_dir)
{
    var mm = com.modestmaps;
    var cm = new mm.CloudMadeProvider('1a914755a77758e49e19a26e799268b7', '22677');
    
    var map = new mm.Map(element, cm, {x: 600, y: 400}, []);
    var ext = [{lat: north, lon: west}, {lat: south, lon: east}];

    map.setExtent(ext);
    add_roundy_corners(map);
    
    var img = new Image();
    
    img.onload = function()
    {
        var pos = map.locationPoint({lat: lat, lon: lon});
        var anc = document.createElement('a');

        anc.className = 'map-link';
        anc.style.width = this.width.toFixed(0) + 'px';
        anc.style.height = this.height.toFixed(0) + 'px';
        anc.style.left = (pos.x - this.width/2).toFixed(0) + 'px';
        anc.style.top = (pos.y - this.height/2).toFixed(0) + 'px';

        anc.appendChild(img);
        map.parent.appendChild(anc);
    }
    
    img.src = base_dir + '/images/cross_round_lg.png';
}

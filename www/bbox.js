function BoundingBox(map) {

    var mm = com.modestmaps;

    this.map = map;

    var theBox = this;

    this.getMousePoint = function(e) {
        // start with just the mouse (x, y)
        var point = new mm.Point(e.clientX, e.clientY);
        
        // correct for scrolled document
        point.x += document.body.scrollLeft + document.documentElement.scrollLeft;
        point.y += document.body.scrollTop + document.documentElement.scrollTop;

        // correct for nested offsets in DOM
        for(var node = this.map.parent; node; node = node.offsetParent) {
            point.x -= node.offsetLeft;
            point.y -= node.offsetTop;
        }
        
        return point;
    };

    var boxDiv = document.createElement('div');
    boxDiv.id = map.parent.id+'-boundingBox';
    boxDiv.width = map.dimensions.x;
    boxDiv.height = map.dimensions.y;
    boxDiv.style.margin = '0';
    boxDiv.style.padding = '0';
    boxDiv.style.position = 'absolute';
    boxDiv.style.top = '0px';
    boxDiv.style.left = '0px';
    boxDiv.style.width = map.dimensions.x+'px';
    boxDiv.style.height = map.dimensions.y+'px';        
    map.parent.appendChild(boxDiv);    

    var box = document.createElement('div');
    box.id = map.parent.id+'-boundingBox-box';
    box.width = map.dimensions.x;
    box.height = map.dimensions.y;
    box.style.margin = '0';
    box.style.padding = '0';
    box.style.outlineWidth = '2px';
    box.style.outlineColor = '#00DF43';
    box.style.outlineStyle = 'solid';
    box.style.position = 'absolute';
    box.style.display = 'none';
    box.style.top = '0px';
    box.style.left = '0px';    
    box.style.width = '0px';
    box.style.height = '0px';    
    boxDiv.appendChild(box);    

    // TODO: respond to resize

    var mouseDownPoint = null;
    
    this.mouseDown = function(e) {
        if (e.shiftKey) {
            mouseDownPoint = theBox.getMousePoint(e);
            
            box.style.width = '0px';
            box.style.height = '0px';
            box.style.left = mouseDownPoint.x + 'px';
            box.style.top = mouseDownPoint.y + 'px';
    
            mm.addEvent(map.parent, 'mousemove', theBox.mouseMove);
            mm.addEvent(map.parent, 'mouseup', theBox.mouseUp);
            
            map.parent.style.cursor = 'crosshair';
            
            return mm.cancelEvent(e);
        }
    };

    this.mouseMove = function(e) {
        var point = theBox.getMousePoint(e);
        box.style.display = 'block';
        if (point.x < mouseDownPoint.x) {
            box.style.left = point.x + 'px';
        }
        else {
            box.style.left = mouseDownPoint.x + 'px';
        }
        box.style.width = Math.abs(point.x - mouseDownPoint.x) + 'px';
        if (point.y < mouseDownPoint.y) {
            box.style.top = point.y + 'px';
        }
        else {
            box.style.top = mouseDownPoint.y + 'px';
        }
        box.style.height = Math.abs(point.y - mouseDownPoint.y) + 'px';
        theBox.updateInfo();
        return mm.cancelEvent(e);
    };    

    this.updateInfo = function() {
        if (!this.onchange) return;
        if (box.style.display != 'none') {
            var p1 = new mm.Point(box.offsetLeft, box.offsetTop);
            var p2 = new mm.Point(box.offsetLeft+box.offsetWidth, box.offsetTop+box.offsetHeight);
            var l1 = map.pointLocation(p1);
            var l2 = map.pointLocation(p2);
            var northWest = new mm.Location(Math.max(l1.lat,l2.lat), Math.min(l1.lon, l2.lon));
            var southEast = new mm.Location(Math.min(l1.lat,l2.lat), Math.max(l1.lon, l2.lon));
            this.onchange([ northWest.lat, northWest.lon, southEast.lat, southEast.lon ]);
        }
    };

    this.mouseUp = function(e) {
    
        theBox.updateInfo();

        mm.removeEvent(map.parent, 'mousemove', theBox.mouseMove);
        mm.removeEvent(map.parent, 'mouseup', theBox.mouseUp);        

        map.parent.style.cursor = 'auto';
        
        return mm.cancelEvent(e);
    };
    
    mm.addEvent(boxDiv, 'mousedown', this.mouseDown);
}


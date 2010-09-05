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
    box.style.margin = '0';
    box.style.padding = '0';
    box.style.outlineWidth = '1000px';
    box.style.outlineColor = 'rgba(0,0,0,0.2)';
    box.style.outlineStyle = 'solid';
    box.style.borderWidth = '2px';
    box.style.borderColor = '#00DF43';
    box.style.borderStyle = 'solid';
    box.style.position = 'absolute';
    box.style.display = 'block';
    box.style.left = (map.dimensions.x/4)+'px';
    box.style.top = (map.dimensions.y/4)+'px';
    box.style.width = (map.dimensions.x/2)+'px';
    box.style.height = (map.dimensions.y/2)+'px';
    boxDiv.appendChild(box);    

    // TODO: respond to resize

    this.mouseMove = function(e) {
        var p = theBox.getMousePoint(e);
        
        var b = {
            x: parseInt(box.style.left.slice(0,-2)),
            y: parseInt(box.style.top.slice(0,-2)),
            w: parseInt(box.style.width.slice(0,-2)),
            h: parseInt(box.style.height.slice(0,-2))
        };
        
        var t = 10; // tolerance
        
        if (p.y > b.y-t && p.y < b.y+t) {
            if (p.x > b.x-t && p.x < b.x+t) {
                map.parent.style.cursor = 'nw-resize';
            }
            else if (p.x > b.x+b.w-t && p.x < b.x+b.w+t) {
                map.parent.style.cursor = 'ne-resize';
            }
            else if (p.x > b.x+t && p.x < b.x+b.w-t) {
                map.parent.style.cursor = 'n-resize';
            }
            else {
                map.parent.style.cursor = 'auto';
            }            
        }
        else if (p.y > b.y+b.h-t && p.y < b.y+b.h+t) {
            if (p.x > b.x-t && p.x < b.x+t) {
                map.parent.style.cursor = 'sw-resize';
            }
            else if (p.x > b.x+b.w-t && p.x < b.x+b.w+t) {
                map.parent.style.cursor = 'se-resize';
            }
            else if (p.x > b.x+t && p.x < b.x+b.w-t) {
                map.parent.style.cursor = 's-resize';
            }
            else {
                map.parent.style.cursor = 'auto';
            }
        }
        else if (p.y > b.y+t && p.y < b.y+b.h-t) {
            if (p.x > b.x-t && p.x < b.x+t) {
                map.parent.style.cursor = 'w-resize';
            }
            else if (p.x > b.x+b.w-t && p.x < b.x+b.w+t) {
                map.parent.style.cursor = 'e-resize';
            }
            else if (p.x > b.x+t && p.x < b.x+b.w-t) {
                map.parent.style.cursor = 'move';
            }
            else {
                map.parent.style.cursor = 'auto';
            }
        }
        else {
            map.parent.style.cursor = 'auto';
        }
    };


    var mouseDownPoint = null;
    
    this.mouseDown = function(e) {        
        if (map.parent.style.cursor.indexOf('resize') >= 0 || map.parent.style.cursor == 'move') {
            mouseDownPoint = theBox.getMousePoint(e);
            mm.addEvent(window, 'mousemove', theBox.mouseDrag);
            mm.removeEvent(boxDiv, 'mousemove', theBox.mouseMove);
            mm.addEvent(window, 'mouseup', theBox.mouseUp);
            return mm.cancelEvent(e);
        }
    };

    this.mouseDrag = function(e) {
        var p = theBox.getMousePoint(e);
        var b = {
            x: parseInt(box.style.left.slice(0,-2)),
            y: parseInt(box.style.top.slice(0,-2)),
            w: parseInt(box.style.width.slice(0,-2)),
            h: parseInt(box.style.height.slice(0,-2))
        };
        if (map.parent.style.cursor == 'move') {
            var newX = b.x + p.x - mouseDownPoint.x,
                newY = b.y + p.y - mouseDownPoint.y;
            var dx = 0, dy = 0;
            if (newX < 2) {
                dx = 2 - newX;
                newX = 2;
            }
            else if (newX > boxDiv.offsetWidth-b.w-6) {
                dx = (boxDiv.offsetWidth-b.w-6) - newX;
                newX = boxDiv.offsetWidth-b.w-6;
            }
            if (newY < 2) {
                dy = 2 - newY;
                newY = 2;
            }
            else if (newY > boxDiv.offsetHeight-b.h-6) {
                dy = (boxDiv.offsetHeight-b.h-6) - newY;
                newY = boxDiv.offsetHeight-b.h-6;
            }
            if (dx || dy) {
                map.panBy(dx,dy)
            }
            box.style.left = newX + 'px'
            box.style.top = newY + 'px';
            mouseDownPoint = p;
        }
        else if (map.parent.style.cursor == 'w-resize' && p.x > 2 && p.x < b.x+b.w-10) {
            box.style.width = b.w + (b.x-p.x) + 'px';
            box.style.left = p.x + 'px';
        }
        else if (map.parent.style.cursor == 'e-resize' && p.x < boxDiv.offsetWidth-2 && p.x > b.x+10) {
            box.style.width = (p.x-b.x) + 'px';
        }
        else if (map.parent.style.cursor == 'n-resize' && p.y > 2 && p.y < b.y+b.h-10) {
            box.style.height = b.h + (b.y-p.y) + 'px';
            box.style.top = p.y + 'px';
        }
        else if (map.parent.style.cursor == 's-resize' && p.y < boxDiv.offsetHeight-2 && p.y > b.y+10) {
            box.style.height = (p.y-b.y) + 'px';
        }        
        else if (map.parent.style.cursor == 'nw-resize') {
            if (p.y > 2 && p.y < b.y+b.h-10) {
                box.style.height = b.h + (b.y-p.y) + 'px';
                box.style.top = p.y + 'px';
            }
            if (p.x > 2 && p.x < b.x+b.w-10) {
                box.style.width = b.w + (b.x-p.x) + 'px';
                box.style.left = p.x + 'px';
            }
        }
        else if (map.parent.style.cursor == 'ne-resize') {
            if (p.y > 2 && p.y < b.y+b.h-10) {
                box.style.height = b.h + (b.y-p.y) + 'px';
                box.style.top = p.y + 'px';
            }
            if (p.x < boxDiv.offsetWidth-2 && p.x > b.x+10) {
                box.style.width = (p.x-b.x) + 'px';
            }
        }
        else if (map.parent.style.cursor == 'sw-resize') {
            if (p.y < boxDiv.offsetHeight-2 && p.y > b.y+10) {
                box.style.height = (p.y-b.y) + 'px';
            }
            if (p.x > 2 && p.x < b.x+b.w-10) {
                box.style.width = b.w + (b.x-p.x) + 'px';
                box.style.left = p.x + 'px';
            }
        }
        else if (map.parent.style.cursor == 'se-resize') {
            if (p.y < boxDiv.offsetHeight-2 && p.y > b.y+10) {
                box.style.height = (p.y-b.y) + 'px';
            }
            if (p.x < boxDiv.offsetWidth-2 && p.x > b.x+10) {
                box.style.width = (p.x-b.x) + 'px';
            }
        }        
        theBox.updateInfo();
        return mm.cancelEvent(e);
    };    

    this.mouseUp = function(e) {
        theBox.updateInfo();
        mm.removeEvent(window, 'mousemove', theBox.mouseDrag);
        mm.addEvent(boxDiv, 'mousemove', theBox.mouseMove);
        mm.removeEvent(window, 'mouseup', theBox.mouseUp);
        map.parent.style.cursor = 'auto';
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
    
    mm.addEvent(boxDiv, 'mousemove', this.mouseMove);
    mm.addEvent(boxDiv, 'mousedown', this.mouseDown);
}


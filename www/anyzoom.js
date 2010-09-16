var AnyZoomHandler = function(map) { 
    if (map !== undefined) {
        this.init(map);
    }
};

AnyZoomHandler.prototype = {

    init: function(map) {
        this.map = map;
        com.modestmaps.addEvent(map.parent, 'dblclick', this.getDoubleClick());
        com.modestmaps.addEvent(map.parent, 'mousedown', this.getMouseDown());
        com.modestmaps.addEvent(map.parent, 'mousewheel', this.getMouseWheel());            
    },
    
    mouseDownHandler: null,

    getMouseDown: function() {
        if (!this.mouseDownHandler) {
            var theHandler = this;
            this.mouseDownHandler = function(e) {
    
                com.modestmaps.addEvent(document, 'mouseup', theHandler.getMouseUp());
                com.modestmaps.addEvent(document, 'mousemove', theHandler.getMouseMove());
                        
                theHandler.prevMouse = new com.modestmaps.Point(e.clientX, e.clientY);
                
                theHandler.map.parent.style.cursor = 'move';
            
                return com.modestmaps.cancelEvent(e);
            };
        }
        return this.mouseDownHandler;
    },
    
    mouseMoveHandler: null,
    
    getMouseMove: function() {
        if (!this.mouseMoveHandler) {
            var theHandler = this;
            this.mouseMoveHandler = function(e) {
    
                if (theHandler.prevMouse) {
                    theHandler.map.panBy(e.clientX - theHandler.prevMouse.x, e.clientY - theHandler.prevMouse.y);
                    theHandler.prevMouse.x = e.clientX;
                    theHandler.prevMouse.y = e.clientY;
                }
            
                return com.modestmaps.cancelEvent(e);
            };
        }
        return this.mouseMoveHandler;
    },

    mouseUpHandler: null,

    getMouseUp: function() {
        if (!this.mouseUpHandler) {
            var theHandler = this;
            this.mouseUpHandler = function(e) {
    
                com.modestmaps.removeEvent(document, 'mouseup', theHandler.getMouseUp());
                com.modestmaps.removeEvent(document, 'mousemove', theHandler.getMouseMove());
        
                theHandler.prevMouse = null;

                theHandler.map.parent.style.cursor = '';                
        
                return com.modestmaps.cancelEvent(e);
            };
        }
        return this.mouseUpHandler;
    },
    
    mouseWheelHandler: null,

    getMouseWheel: function() {
        if (!this.mouseWheelHandler) {
            var theHandler = this;
            this.mouseWheelHandler = function(e) {
    
                var delta = 0;
                
                if (e.wheelDelta) {
                    delta = e.wheelDelta / 60;
                }
                else if (e.detail) {
                    delta = -e.detail / 2;
                }
    
                var point = theHandler.getMousePoint(e);
                    
                theHandler.map.zoomByAbout(Math.min(0.5, Math.max(-0.5, delta/10.0)), point);
                    
                return com.modestmaps.cancelEvent(e);
            };
        }
        return this.mouseWheelHandler;
    },

    doubleClickHandler: null,

    getDoubleClick: function() {
        if (!this.doubleClickHandler) {
            var theHandler = this;
            this.doubleClickHandler = function(e) {
    
                var point = theHandler.getMousePoint(e);
                
                // use shift-double-click to zoom out
                theHandler.map.zoomByAbout(e.shiftKey ? -1 : 1, point);    
                
                return com.modestmaps.cancelEvent(e);
            };
        }
        return this.doubleClickHandler;
    },

    // interaction helper

    getMousePoint: function(e)
    {
        // start with just the mouse (x, y)
        var point = new com.modestmaps.Point(e.clientX, e.clientY);
        
        // correct for scrolled document
        point.x += document.body.scrollLeft + document.documentElement.scrollLeft;
        point.y += document.body.scrollTop + document.documentElement.scrollTop;

        // correct for nested offsets in DOM
        for(var node = this.map.parent; node; node = node.offsetParent) {
            point.x -= node.offsetLeft;
            point.y -= node.offsetTop;
        }
        
        return point;
    }

};
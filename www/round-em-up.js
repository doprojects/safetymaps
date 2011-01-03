function add_roundy_corners(map)
{
    var canvas = document.createElement('canvas');
    
    if(canvas.getContext)
    {
        var w = map.dimensions.x,
            h = map.dimensions.y,
            gutter = .5,
            radius = 4;
        
        canvas.style.position = 'absolute';
        canvas.style.left = 0;
        canvas.style.top = 0;
        canvas.width = w;
        canvas.height = h;
        map.parent.appendChild(canvas);
        
        map.parent.style.borderWidth = 0;
        
        var ctx = canvas.getContext('2d');
        
        ctx.fillStyle = '#fff';
        ctx.beginPath();
        
        ctx.moveTo(0, 0);
        ctx.lineTo(0, h);
        ctx.lineTo(w, h);
        ctx.lineTo(w, 0);
        ctx.lineTo(0, 0);
        
        ctx.moveTo(w/2, gutter);
        ctx.arcTo(w - gutter, gutter, w - gutter, h/2, radius);
        ctx.arcTo(w - gutter, h - gutter, w/2, h - gutter, radius);
        ctx.arcTo(gutter, h - gutter, gutter, h/2, radius);
        ctx.arcTo(gutter, gutter, w/2, gutter, radius);
        
        ctx.closePath();
        ctx.fill();
        
        if($(map.parent).css('borderTopColor'))
        {
            ctx.strokeStyle = $(map.parent).css('borderTopColor');
            ctx.beginPath();
            
            ctx.moveTo(w/2, gutter);
            ctx.arcTo(w - gutter, gutter, w - gutter, h/2, radius);
            ctx.arcTo(w - gutter, h - gutter, w/2, h - gutter, radius);
            ctx.arcTo(gutter, h - gutter, gutter, h/2, radius);
            ctx.arcTo(gutter, gutter, w/2, gutter, radius);
            
            ctx.closePath();
            ctx.stroke();
        }
    }
}

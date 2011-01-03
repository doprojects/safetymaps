var bboxmap;

function behaveAsRequired(index, input)
{
    function onBlur()
    {
        var omission = $(input).nextAll('.omission').first();
      
        if($(input).attr('value')) {
            omission.css({ opacity: 1 });
            omission.animate({ opacity: 0 }, function() { omission.hide(); });
        
        } else {
            omission.show();
            omission.css({ opacity: 0 });
            omission.animate({ opacity: 1 });
        }
    }

    $(input).blur(onBlur);
}

function prepareEmergencyChoiceInput()
{
   /**
    * Change to #emergency-chooser margin-top: -38px
    */
    function chooseOther()
    {
        $('#emergency-other').show();
        $('#emergency-chooser').css({ marginTop: -38 });
        $('#emergency-select').css({ top: 38, zIndex: 1000 });
        $('#emergency-select').animate({ top: 0 }, { duration: 'fast' });
        $('#emergency-select').addClass('other');

        $('#emergency-select').attr('name', '');
        $('#emergency-other').attr('name', 'place[emergency]');
        $('#emergency-other').focus();
    }
    
   /**
    * Change to #emergency-chooser margin-top: 0
    */
    function chooseNormal()
    {
        function onMoved()
        {
            $('#emergency-chooser').css({ marginTop: 0 });
            $('#emergency-select').css({ top: 0, zIndex: 1000 });
            $('#emergency-select').removeClass('other');
            $('#emergency-other').hide();
    
            $('#emergency-select').attr('name', 'place[emergency]');
            $('#emergency-other').attr('name', '');
        }
        
        $('#emergency-select').css({ top: 0, zIndex: 1000 });
        $('#emergency-select').animate({ top: 38 }, { duration: 'fast', complete: onMoved });
    }
    
    // deal with "Other (please specify)"
    $('#emergency-select').change(function()
      {
        if($('#emergency-select option#otherplace').attr('selected')) {
            return chooseOther();

        } else if($('#emergency-other').attr('name') == 'place[emergency]') {
            return chooseNormal();
        }
      }
    );
}

function prepareBBoxMapInput()
{
    var mm = com.modestmaps;

    // pull an initial location from the DOM.
    var lat0 = parseFloat($('input#loc0').attr('value')),
        lon0 = parseFloat($('input#loc1').attr('value')),

        initialLocation = (isNaN(lat0) || isNaN(lon0))
            ? new mm.Location(0, 0)
            : new mm.Location(lat0, lon0);
    
    // pull an initial extent from the DOM.
    var lat1 = parseFloat($('input#bbox0').attr('value')),
        lon1 = parseFloat($('input#bbox1').attr('value')),
        lat2 = parseFloat($('input#bbox2').attr('value')),
        lon2 = parseFloat($('input#bbox3').attr('value')),

        initialExtentA = (isNaN(lat1) || isNaN(lon1))
            ? undefined
            : new mm.Location(lat1, lon1),
        initialExtentB = (isNaN(lat2) || isNaN(lon2))
            ? undefined
            : new mm.Location(lat2, lon2),
        
        initialExtent = (initialExtentA && initialExtentB)
            ? [initialExtentA, initialExtentB]
            : undefined;
    
    var provider = new mm.CloudMadeProvider('1a914755a77758e49e19a26e799268b7','22677');
    // make a map!
    bboxmap = new mm.Map('bboxmap', provider, null, [ new AnyZoomHandler() ]);
    
    if(initialExtent) {
        bboxmap.setExtent(initialExtent);
    
    } else {
        bboxmap.setCenterZoom(initialLocation, 1);
    }
    
    function onMapChange() {
        var extent = bboxmap.getExtent();
        if (extent[0].lat-extent[1].lat > 0.001 && extent[1].lon-extent[0].lon > 0.001) {
            var pos = $('#mark').position();
            var loc;
            if (pos) loc  = bboxmap.pointLocation(new mm.Point(pos.left, pos.top));
            else loc = mm.Location.interpolate(extent[0], extent[1], 0.5);
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
    
    var point = bboxmap.locationPoint(initialLocation),
        left = point.x.toFixed(0) + 'px',
        top = point.y.toFixed(0) + 'px',
        $mark = $('<img id="mark" style="left: '+left+'; top: '+top+'; margin-left: -29px; margin-top: -29px; cursor: move; position: absolute; z-index: 1000;" src="images/cross_round_lg.png">');

    $mark.bind('mousedown', function(mde) {
      var mousePosition = $mark.offset();
      var mouseOffset = { left: mde.pageX - mousePosition.left, top: mde.pageY - mousePosition.top }
      function onMarkMouseMove(mme) {
          var newPos = { left: mme.pageX - mouseOffset.left, top: mme.pageY - mouseOffset.top };
          $mark.offset(newPos);
          var pos = $mark.position();
          var loc = bboxmap.pointLocation(new mm.Point(pos.left, pos.top));
          $("input#loc0").attr('value', loc.lat.toFixed(6));
          $("input#loc1").attr('value', loc.lon.toFixed(6));
          return false;
      }
      $(document.body).bind('mousemove', onMarkMouseMove);
      $(document.body).one('mouseup', function() {
          $(document.body).unbind('mousemove', onMarkMouseMove);
          return false;
      });
      return false;
    });

    var $zoom = $('<p id="zoom" style="position:absolute; margin: 10px; padding: 0; right: 0; top: 0; z-index:2000;"></p>')
                    .append('<a href="#" id="zoomin" style="padding: 0px; margin-bottom: 5px; text-decoration: none;"><img border="0" src="images/zoom_in_25px_recent.png"></a><br>')
                    .append('<a href="#" id="zoomout" style="padding: 0px; text-decoration: none;"><img border="0" src="images/zoom_out_25px_short.png"></a>');

    $('#bboxmap').append($mark);
    $('#bboxmap').append($zoom);

    $('#zoomin').bind('click', function() { bboxmap.zoomIn(); return false; });
    $('#zoomout').bind('click', function() { bboxmap.zoomOut(); return false; });

    var $search = $('<p style="position:absolute; margin: 0px; padding: 0px; z-index:2000;"></p>')
                      .append('<form id="searchform"><input type="text" id="search" name="search"><button type="submit">Search</button></form>');
    $(document.body).append($search);
    
    $(document.body).bind('search-needs-adjusting', function() {
        var mapOffset = $('#bboxmap').offset();
        mapOffset.left += 10;
        mapOffset.top += 10;
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
        var key = 'nbHkmO_V34FNW1fULoqJl3Ito52VgOmgeN_dFHoEM7vRb65sl3tpVWA4pvzI5mWDGw--';
        var flags = 'JXG';
        var realURL = 'http://where.yahooapis.com/geocode?'+
                      'q='+encodeURIComponent(q)+
                      '&flags='+encodeURIComponent(flags)+
                      '&appid='+encodeURIComponent(key);
        $('#search').attr('disabled', 'disabled');
        $.ajax({
            dataType: 'jsonp',
            url: 'slimjim.php?url='+encodeURIComponent(realURL)+'&callback=?',
            success: onPlaceSearch
        });
        return false;
    } );

    function onPlaceSearch(rsp) {
        $('#search').attr('disabled', '');
        if (rsp && rsp.ResultSet && rsp.ResultSet.Results && rsp.ResultSet.Results.length) {
            var result = rsp.ResultSet.Results[0];
            if (result.boundingbox) {
              bboxmap.setExtent([new mm.Location(result.boundingbox.north, result.boundingbox.west), 
                              new mm.Location(result.boundingbox.south, result.boundingbox.east)]);
            }
            else {
              bboxmap.setCenterZoom(new mm.Location(result.latitude, result.longitude), 12);
            }
        }
    }

}

function prepareRecipientsListInput()
{
    function addRecipient()
    {
        var html = ['<li>',
                    'name: <input type="text" name="recipients[99][name]" size="15">',
                    ' ',
                    'email: <input type="email" name="recipients[99][email]" placeholder="e.g. them@there.com" size="35">',
                    ' ',
                    '<span class="omission tab">★ Name + E-mail Required<','/span>',
                    '<a class="remove-recipient tab" href="#">Remove recipient<','/a>',
                    '<','/li>'];

        function onAdded()
        {
            $(this).find('input').first().focus();
            $(this).find('a.remove-recipient').live('click', removeRecipient);
            $(this).find('input').each(behaveAsRequired);
        }
        
        var newLI = $(html.join(''));
        $('#recipients').append(newLI);
        newLI.show(onAdded);
        
        renumberFormElements();
        return false;
    }
    
    function removeRecipient()
    {
        function onRemoved()
        {
            $(this).remove();
            renumberFormElements();
        }
        
        $(this).parent('li').slideUp(onRemoved);

        return false;
    }
    
    function renumberFormElements()
    {
        // renumber the form elements
        $('#recipients li').each(function(i) {
            $(this).find('input').attr('name', function(j, attr) {
                return attr.replace(/\d+/, i);
            });
        });
    }

    $('#recipients a.remove-recipient').live('click', removeRecipient);
    $('#add-recipient').live('click', addRecipient);
}

$(document).ready(function() {

    $('input.required').each(behaveAsRequired);

    prepareEmergencyChoiceInput();
    prepareBBoxMapInput();
    prepareRecipientsListInput();

});

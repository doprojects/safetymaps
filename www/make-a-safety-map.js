$(document).ready(function() {

    $('#emergencyplace').bind('change', function() {
      if($('#otherplace').attr('selected')) {
        if ($('#otherinput').length == 0) {
          $('<input id="otherinput" type="text"></input>').insertAfter($('#emergencyplace'));
        }
      }
      else {
        $('#otherinput').remove();
      }
    });

    // make a map!
    var mm = com.modestmaps;
    //var template = 'http://{S}tile.openstreetmap.org/{Z}/{X}/{Y}.png';
    //var domains = [ '', 'a.', 'b.', 'c.' ];
    //var provider = new mm.TemplatedMapProvider(template, domains);
    var provider = new mm.CloudMadeProvider('1a914755a77758e49e19a26e799268b7','22677');

    var bboxmap = new mm.Map('bboxmap', provider, null, [ new AnyZoomHandler() ]);
    bboxmap.setCenterZoom(new mm.Location(40.7143528, -74.0059731), 13);
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
    
    $('#zoomin').bind('click', function() { bboxmap.zoomIn(); return false; });
    $('#zoomout').bind('click', function() { bboxmap.zoomOut(); return false; });
    
    function nth(n) {
        var s = n.toString();
        if (n == 11 || n == 12 || n == 13) {
            return s+'th';
        }
        var last = s.charAt(s.length-1);
        if (last == '1') {
            return s+'st';
        }
        else if (last == '2') {
            return s+'nd';
        }
        else if (last == '3') {
            return s+'rd';
        }
        return s+'th';
    }
    
    $('a.addrecipient').live('click', function() {
        // TODO: can we clone a node and find/replace instead?
        // or use jquery templates?
        try {
            var index = $('#recipients p').length;
            $('#recipients').append($('<p class="field full">'+nth(index+1)+' recipient:<br>'
                + '<label for="recipients['+index+'][name]">name:<\/label><input type="text" name="recipients['+index+'][name]" size="15">'
                + ' <label for="recipients['+index+'][email]">email:<\/label><input type="text" name="recipients['+index+'][email]" size="35"> <a class="addrecipient" href="">Add another...<\/a> <a href="" class="removerecipient">Remove this one?<\/a>'
                + '<\/p>'));
        }
        catch(e) {
            console.log(e);
        }
        return false;
    });
    
    $('a.removerecipient').live('click', function() {
        try {
            $(this).parent('p').remove();
            $('#recipients p').each(function(index) {
                var html = $(this).html();
                html = html.replace(/(\d+[a-z][a-z])/g, function() {
                    return nth(index+1);
                });
                html = html.replace(/(\[\d+\])/g, function() {
                    return '['+index.toString()+']';
                });
                $(this).html(html)
            });
        }
        catch(e) {
            console.log(e);
        }
        return false;
    });
    
    // sigh http://stackoverflow.com/questions/156430/regexp-recognition-of-email-address-hard
    // aha  http://www.regular-expressions.info/email.html
    var emailRegexp = /(?:[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*|"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])/;

    var desired = {
        'map': {
            'bounds': {
                '0': /\d+\.*\d*/,
                '1': /\d+\.*\d*/,
                '2': /\d+\.*\d*/,
                '3': /\d+\.*\d*/
            },
            'format': /(4up|2up-fridge|poster)/,
            'paper': /(letter|a4)/,
            'privacy': /(unlisted|public)/
        },
        'place': {
            'name': /.+/,
            'emergency': /.+/,
            'full-note': /.+/,
            'short-note': /.*/,
            'location': {
                '0': /\d+\.*\d*/,
                '1': /\d+\.*\d*/
            }
        },
        'recipients': {
            '*': {
                'email': emailRegexp,
                'name': /.+/
            }
        },
        'sender': {
            'email': emailRegexp,
            'name': /.+/
        }
    };
    
    $('#mapform').bind('submit', function() {
        var data = deserialize($(this).serialize());
        try {
            var errors = validate(data,desired,[]);
            for (var i = 0; i < errors.length; i++) {
                console.error(errors[i]);
            }
        }
        catch(e) {
            console.log(e);
        }
        return errors.length == 0;
    });
});

function validate(data,desired,path) {
    var errors = [];
    var pathString = path.map(function(p) { return '['+p+']'}).join('');
    for (var prop in desired) {
        if (desired[prop] instanceof RegExp) {
            if (prop in data) {
                if (!desired[prop].test(data[prop] || '')) {
                    // TODO: actionable error obj
                    errors.push('data' + pathString + '[' + prop + ']=' + data[prop] + ' doesn\'t match ' + desired[prop].toString());
                }
            }
            else if (prop == '*') {
                errors = errors.concat(validateSplat(data,desired[prop],path));
            }                        
            else {
                // TODO: actionable error obj
                errors.push(prop + ' not in data' + pathString);
            }
        }
        else {
            if (prop in data) {
                errors = errors.concat(validate(data[prop],desired[prop],path.concat(prop)));
            }
            else if (prop == '*') {
                errors = errors.concat(validateSplat(data,desired[prop],path));
            }
            else {
                // TODO: actionable error obj
                errors.push(prop + ' not in data' + pathString);
            }
        }
    }
}

function validateSplat(data,desired,path) {
    var errors = [];            
    var pathString = path.map(function(p) { return '['+p+']'}).join('');
    var max = 100; // TODO: Make this configurable somehow
    for (var i = 0; i < max; i++) {
        if (i.toString() in data) {
            errors = errors.concat(validate(data[i], desired, path.concat(i.toString())));
        }
        else {
            break;
        }
    }
    return errors;
}


// pull apart php-style form params assembled by $.serialize
function deserialize(params) {
    var data = {};
    try {
        var parts = params.split('&');
        for (var i = 0; i < parts.length; i++) {
            var words = parts[i].split('=').map(decodeURIComponent);
            var nameParts = words[0].match(/([A-Za-z0-9\-]+)/g);
            var dataPart = data;
            while(nameParts.length > 1) {
                var namePart = nameParts.shift();
                if (!(namePart in dataPart)) {
                    dataPart[namePart] = {};
                }
                dataPart = dataPart[namePart];
            }
            dataPart[nameParts[0]] = words[1] || null;
        }           
    }
    catch (e) {
        return null;
    }
    return data;
}

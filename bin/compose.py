""" Generate a PDF of a meeting point map.
"""

from sys import stderr, stdin
from os import close, unlink, chmod
from os.path import dirname, join as pathjoin
from re import compile, DOTALL
from math import pi
from time import strftime
from tempfile import mkstemp
from optparse import OptionParser
from ModestMaps import mapByExtentZoom, mapByCenterZoom
from ModestMaps.Geo import Location
from ModestMaps.Core import Point
from ModestMaps.Providers import TemplatedMercatorProvider
from cairo import PDFSurface, ImageSurface, Context, FORMAT_A8
import rsvg
import ctypes

mmppt = 0.352777778
inppt = 0.013888889

ptpin = 1./inppt
ptpmm = 1./mmppt

green = (0, .8, 0)
dk_gray = (.2, .2, .2) # for text, alternates with green
md_gray = (.4, .4, .4) # mostly for page master text & dotted lines
lt_gray = (.8, .8, .8) # fat, round borders

def mapByExtentZoomAspect(prov, locA, locB, zoom, aspect):
    """ Get a map by extent and zoom, and adjust it to the desired aspect ratio.
    
        Adjustments always increase the size. Return a ModestMaps.Map instance.
    """
    mmap = mapByExtentZoom(prov, locA, locB, zoom)
    center = mmap.pointLocation(Point(mmap.dimensions.x/2, mmap.dimensions.y/2))
    
    if aspect < float(mmap.dimensions.x) / float(mmap.dimensions.y):
        # make taller
        dimensions = Point(mmap.dimensions.x, int(mmap.dimensions.x / aspect))

    else:
        # make wider
        dimensions = Point(int(mmap.dimensions.y * aspect), mmap.dimensions.y)
    
    return mapByCenterZoom(prov, center, zoom, dimensions)

def create_cairo_font_face_for_file(filename, faceindex=0, loadoptions=0):
    """
    
        http://cairographics.org/freetypepython
    """

    CAIRO_STATUS_SUCCESS = 0
    FT_Err_Ok = 0

    # find shared objects
    _freetype_so = ctypes.CDLL("libfreetype.so.6")
    _cairo_so = ctypes.CDLL("libcairo.so.2")

    # initialize freetype
    _ft_lib = ctypes.c_void_p()
    if FT_Err_Ok != _freetype_so.FT_Init_FreeType(ctypes.byref(_ft_lib)):
      raise "Error initialising FreeType library."

    class PycairoContext(ctypes.Structure):
        _fields_ = [("PyObject_HEAD", ctypes.c_byte * object.__basicsize__),
                    ("ctx", ctypes.c_void_p),
                    ("base", ctypes.c_void_p)]

    _surface = ImageSurface(FORMAT_A8, 0, 0)

    # create freetype face
    ft_face = ctypes.c_void_p()
    cairo_ctx = Context(_surface)
    cairo_t = PycairoContext.from_address(id(cairo_ctx)).ctx
    _cairo_so.cairo_ft_font_face_create_for_ft_face.restype = ctypes.c_void_p

    if FT_Err_Ok != _freetype_so.FT_New_Face(_ft_lib, filename, faceindex, ctypes.byref(ft_face)):
        raise Exception("Error creating FreeType font face for " + filename)

    # create cairo font face for freetype face
    cr_face = _cairo_so.cairo_ft_font_face_create_for_ft_face(ft_face, loadoptions)

    if CAIRO_STATUS_SUCCESS != _cairo_so.cairo_font_face_status(cr_face):
        raise Exception("Error creating cairo font face for " + filename)

    _cairo_so.cairo_set_font_face(cairo_t, cr_face)

    if CAIRO_STATUS_SUCCESS != _cairo_so.cairo_status(cairo_t):
        raise Exception("Error creating cairo font face for " + filename)

    face = cairo_ctx.get_font_face()

    return face

def set_font_face_from_file(ctx, filename):
    """
    """
    fullpath = pathjoin(dirname(__file__), filename)
    fontface = create_cairo_font_face_for_file(fullpath)
    ctx.set_font_face(fontface)

def place_image(context, img, x, y, width, height):
    """ Add an image to a given context, at a position and size given in millimeters.
    
        Assume that the scale matrix of the context is already in mm.
    """
    context.save()
    context.translate(x, y)
    
    # switch to point scale for the sake of the image dimensions
    context.scale(mmppt, mmppt)

    # determine the scale needed to make the image the requested size
    xscale = width * ptpmm / img.get_width()
    yscale = height * ptpmm / img.get_height()
    context.scale(xscale, yscale)

    # paint the image
    context.set_source_surface(img, 0, 0)
    context.paint()

    context.restore()

def place_svg_image(context, filename, x, y, width=None, height=None, flush_right=False):
    """
    """
    context.save()
    context.translate(x, y)

    # switch to point scale for the sake of the drawing dimensions
    context.scale(mmppt, mmppt)
    
    # Guess what? It's a pain in the ass to use SVG from Cairo:
    # http://cairographics.org/pyrsvg
    svg = rsvg.Handle(filename)

    w_, h_, w, h = svg.get_dimension_data()
    
    if width and height:
        context.scale(ptpmm * width/w, ptpmm * height/h)

    if flush_right:
        context.translate(-w, 0)
    
    svg.render_cairo(context)
    
    context.restore()

def place_cc_logo(context, x, y, w, h):
    """
    """
    filename = pathjoin(dirname(__file__), 'assets/cc.svg')
    place_svg_image(context, filename, x, y, width=w, height=h)

def place_do_logo(context, x, y):
    """
    """
    filename = pathjoin(dirname(__file__), 'assets/do.svg')
    place_svg_image(context, filename, x, y, flush_right=True)

def place_sm_logo(context, x, y, w, h):
    """ Add the logo.
    
        Position and size are given in millimeters.
    """
    filename = pathjoin(dirname(__file__), 'assets/logo.svg')
    place_svg_image(context, filename, x, y, width=w, height=h)

def place_hands(context, x, y, format):
    """ Add the hands icon, flush-right.
    """
    filename = pathjoin(dirname(__file__), 'assets/hands-%(format)s.svg' % locals())
    place_svg_image(context, filename, x, y, flush_right=True)

def place_marker(context, x, y):
    """ Draw a provisional-looking marker.
    """
    # push
    context.save()
    
    context.translate(x, y)

    # switch to point scale for the sake of the drawing dimensions
    context.scale(mmppt, mmppt)

    # Guess what? It's a pain in the ass to use SVG from Cairo:
    # http://cairographics.org/pyrsvg
    svg = rsvg.Handle(pathjoin(dirname(__file__), 'assets/marker.svg'))

    w_, h_, w, h = svg.get_dimension_data()
    context.translate(-w/2, -h/2)

    svg.render_cairo(context)

    # pop
    context.restore()

def draw_rounded_box(ctx, x, y, width, height):
    """ Draw a rounded box with corner radius of 2.
    """
    ctx.save()
    
    ctx.translate(x, y)
    
    radius = 2
    bezier = radius / 2
    
    ctx.move_to(0, 0)
    ctx.rel_move_to(radius, 0)
    ctx.rel_line_to(width - 4, 0)
    ctx.rel_curve_to(bezier, 0, radius, bezier, radius, radius)
    ctx.rel_line_to(0, height - 4)
    ctx.rel_curve_to(0, bezier, -bezier, radius, -radius, radius)
    ctx.rel_line_to(4 - width, 0)
    ctx.rel_curve_to(-bezier, 0, -radius, -bezier, -radius, -radius)
    ctx.rel_line_to(0, 4 - height)
    ctx.rel_curve_to(0, -bezier, bezier, -radius, radius, -radius)
    
    ctx.set_line_width(2 * mmppt)
    ctx.set_source_rgb(*lt_gray)
    ctx.set_dash([])
    ctx.stroke()
    
    ctx.restore()

def get_map_image(bbox, width, height, marker, target_dpi=150):
    """ Get a cairo ImageSurface for a given bounding box, plus the (x, y) point of a marker.
    
        Try to match a target DPI. Width and height are given in millimeters!
    """
    prov = TemplatedMercatorProvider('http://a.tile.cloudmade.com/1a914755a77758e49e19a26e799268b7/22677/256/{Z}/{X}/{Y}.png')
    locA, locB = Location(bbox[0], bbox[1]), Location(bbox[2], bbox[3])
    
    aspect = float(width) / float(height)
    
    mmaps = [mapByExtentZoomAspect(prov, locA, locB, zoom, aspect)
             for zoom in range(10, 19)]

    inches_wide = width * ptpmm * inppt
    resolutions = [(mmap.dimensions.x / inches_wide, mmap) for mmap in mmaps]
    differences = [(abs(dpi - target_dpi), mmap) for (dpi, mmap) in resolutions]
    
    diff, mmap = sorted(differences)[0]
    
    if (mmap.dimensions.x * mmap.dimensions.y) > (4000 * 4000):
        raise ValueError('Requested map is too large: %d x %d' % (mmap.dimensions.x, mmap.dimensions.y))

    handle, filename = mkstemp(suffix='.png')
    close(handle)
    
    point = mmap.locationPoint(marker)
    x = width * point.x / mmap.dimensions.x
    y = height * point.y / mmap.dimensions.y
    
    mmap.draw().save(filename)
    img = ImageSurface.create_from_png(filename)
    unlink(filename)
    
    return img, (x, y)

def continue_text_box(ctx, left, width, leading, tail):
    """ Fill up a text box with words.
    
        This function can be called repeatedly with parts of a paragraph.
    """
    words_pat = compile(r'^(\S+)(.*)$', DOTALL)
    white_pat = compile(r'^(\s)(.*)$', DOTALL)
    
    while tail:
        x, y = ctx.get_current_point()

        words = words_pat.match(tail)
        white = white_pat.match(tail)
        
        if words:
            word, tail = words.group(1), words.group(2)
        
            x += ctx.text_extents(word)[4]
            
            if x > width:
                # carriage return
                ctx.move_to(left, y + leading)
    
            ctx.show_text(word)

        elif white:
            space, tail = white.group(1), white.group(2)
            
            if space in ('\n', '\r'):
                # new line
                ctx.move_to(left, y + leading)

            else:
                ctx.show_text(space)

    ctx.show_text(' ')

def today_short():
    """ E.g. "6 Sep 2010"
    """
    return strftime('%d %b %Y').lstrip('0')

def today_long():
    """ E.g. "6th September 2010"
    """
    day, month = strftime('%d').lstrip('0'), strftime('%B %Y')
    suffix = {'1': 'st', '2': 'nd', '3': 'rd'}.get(day[-1], 'th')
    return '%(day)s%(suffix)s %(month)s' % locals()

def write_phrases(ctx, phrases, justify_right=False):
    """
    """
    if justify_right:
        phrases = reversed(phrases)
    
    for (rgb, phrase) in phrases:
        ctx.set_source_rgb(*rgb)
        
        w = ctx.text_extents(phrase)[4]
        
        if justify_right:
            ctx.rel_move_to(-w, 0)
        
        ctx.show_text(phrase)
        
        if justify_right:
            ctx.rel_move_to(-w, 0)

def draw_card_left(ctx, recipient, sender, text):
    """ Draw out the left-hand side of a card.
    
        Modify and restore the matrix stack.
    """
    ctx.save()
    
    draw_rounded_box(ctx, 1.5, 1, 84, 59)

    place_sm_logo(ctx, 4.2, 3.4, 6.9, 6.9)

    # big title text
    ctx.move_to(12.5, 8.4)
    
    set_font_face_from_file(ctx, 'assets/VAGRoundedStd-Bold.otf')
    ctx.set_font_size(11 * mmppt)

    write_phrases(ctx, [(dk_gray, 'Safety Map for '), (green, recipient)])

    # "from" text
    ctx.move_to(81.4, 56)

    write_phrases(ctx,
                  [(dk_gray, 'from '), (green, sender)],
                  justify_right=True)

    # body text
    set_font_face_from_file(ctx, 'assets/HelveticaNeue.ttc')
    ctx.set_source_rgb(*md_gray)
    
    ctx.move_to(5.5, 17)
    ctx.set_font_size(8 * mmppt)
    
    ctx.show_text('(Leave a personal note here)')
    
    ctx.move_to(5.5, 24.5)
    ctx.set_font_size(10 * mmppt)
    
    continue_text_box(ctx, 5.5, 5.5 + 77, 12 * mmppt, text)
    
    ctx.restore()

def draw_card_right(ctx, img, point, emergency, place):
    """ Draw out the right-hand side of a card.
    
        Modify and restore the matrix stack.
    """
    ctx.save()
    
    place_image(ctx, img, 1, 18, 84, 39)
    place_marker(ctx, 1 + point[0], 18 + point[1])

    draw_rounded_box(ctx, 1, 1, 84, 59)

    # big title text
    ctx.move_to(3.2, 6.9)
    
    set_font_face_from_file(ctx, 'assets/VAGRoundedStd-Bold.otf')
    ctx.set_font_size(11 * mmppt)

    write_phrases(ctx,
                  [(dk_gray, 'This Safety Map was made on '),
                   (green, today_short() + '.')])

    # explanation text
    set_font_face_from_file(ctx, 'assets/HelveticaNeue.ttc')
    ctx.set_font_size(8 * mmppt)

    ctx.move_to(3.6, 12)
    
    phrases = [(dk_gray, "In case of"), (green, emergency + ','),
               (dk_gray, "let’s meet at"), (green, place + '.'),
               (dk_gray, "I’ve marked the spot on this map:")]
    
    for (rgb, phrase) in phrases:
        ctx.set_source_rgb(*rgb)
        continue_text_box(ctx, 3.6, 3.6 + 78, 9.6 * mmppt, phrase)

    # text on the bottom
    ctx.move_to(3.8, 58.8)
    ctx.set_source_rgb(*md_gray)
    ctx.set_font_size(4 * mmppt)
    ctx.show_text('This map came from safetymaps.org. You can visit and make your own Safety Maps for free!')

    ctx.restore()

def draw_small_poster(ctx, img, point, emergency, place, recipient, sender, text):
    """ Draw a small version of the poster.
    
        Modify and restore the matrix stack.
    """
    ctx.save()

    draw_rounded_box(ctx, 1.5, 1.5, 119.5, 170)
    
    place_image(ctx, img, 6.8, 27, 109, 77)
    place_marker(ctx, 6.8 + point[0], 27 + point[1])

    ctx.rectangle(6.8, 27, 109, 77)
    ctx.set_line_width(1 * mmppt)
    ctx.set_source_rgb(*lt_gray)
    ctx.set_dash([])
    ctx.stroke()

    place_sm_logo(ctx, 8.4, 14.6, 8.5, 8.5)

    # big title text
    ctx.move_to(8.3, 10.5)
    
    set_font_face_from_file(ctx, 'assets/VAGRoundedStd-Bold.otf')
    ctx.set_font_size(14 * mmppt)

    write_phrases(ctx, [(dk_gray, 'Safety Map for '), (green, recipient)])
    
    # "from" text
    ctx.move_to(115.8, 159)

    ctx.set_font_size(10 * mmppt)

    write_phrases(ctx,
                  [(dk_gray, 'from '),
                   (green,   sender)],
                  justify_right=True)

    # explanation text
    ctx.move_to(18.8, 17.7)
    
    set_font_face_from_file(ctx, 'assets/HelveticaNeue.ttc')
    ctx.set_font_size(10 * mmppt)

    phrases = [(dk_gray, "In case of"), (green, emergency + ','),
               (dk_gray, "let’s meet at"), (green, place + '.'),
               (dk_gray, "I’ve marked the spot on this map:")]
    
    for (rgb, phrase) in phrases:
        ctx.set_source_rgb(*rgb)
        continue_text_box(ctx, 18.8, 18.8 + 97, 12 * mmppt, phrase)

    # text on the bottom
    ctx.move_to(115.8, 106.5)

    ctx.set_font_size(5 * mmppt)

    write_phrases(ctx,
                  [(dk_gray, 'This Safety Map was made on '),
                   (green, today_long() + '.')],
                  justify_right=True)

    # body text
    ctx.move_to(10.6, 120.5)
    
    ctx.set_source_rgb(*md_gray)
    ctx.set_font_size(10 * mmppt)
    
    continue_text_box(ctx, 10.6, 10.6 + 101, 12 * mmppt, text)

    # text on the bottom
    ctx.move_to(6.8, 167.6)
    ctx.set_source_rgb(*md_gray)
    ctx.set_font_size(7.1 * mmppt)
    ctx.show_text('This map came from safetymaps.org. You can visit and make your own Safety Maps for free!')

    ctx.restore()

def draw_large_poster(ctx, img, point, emergency, place, recipient, sender, text):
    """ Draw a large version of the poster.
    
        Modify and restore the matrix stack.
    """
    ctx.save()

    draw_rounded_box(ctx, 2.5, 2.5, 168, 240)
    
    place_image(ctx, img, 10, 39, 153, 108)
    place_marker(ctx, 10 + point[0], 39 + point[1])

    ctx.rectangle(10, 39, 153, 108)
    ctx.set_line_width(1 * mmppt)
    ctx.set_source_rgb(*lt_gray)
    ctx.set_dash([])
    ctx.stroke()

    place_sm_logo(ctx, 12.1, 21.6, 11.9, 11.9)

    # big title text
    ctx.move_to(11.9, 15.9)
    
    set_font_face_from_file(ctx, 'assets/VAGRoundedStd-Bold.otf')
    ctx.set_font_size(19.6 * mmppt)

    write_phrases(ctx,
                  [(dk_gray, 'Safety Map for '), (green, recipient)])
    
    # "from" text
    ctx.move_to(163, 224)

    ctx.set_font_size(14 * mmppt)

    write_phrases(ctx,
                  [(dk_gray, 'from '), (green, sender)],
                  justify_right=True)

    # explanation text
    ctx.move_to(26.8, 26)
    
    set_font_face_from_file(ctx, 'assets/HelveticaNeue.ttc')
    ctx.set_font_size(14 * mmppt)

    phrases = [(dk_gray, "In case of"), (green, emergency + ','),
               (dk_gray, "let’s meet at"), (green, place + '.'),
               (dk_gray, "I’ve marked the spot on this map:")]
    
    for (rgb, phrase) in phrases:
        ctx.set_source_rgb(*rgb)
        continue_text_box(ctx, 26.8, 26.8 + 136, 16.8 * mmppt, phrase)

    # text on the bottom
    ctx.move_to(163, 150.5)

    ctx.set_font_size(7 * mmppt)

    write_phrases(ctx,
                  [(dk_gray, 'This Safety Map was made on '),
                   (green, today_long() + '.')],
                  justify_right=True)

    # body text
    ctx.move_to(15, 170)
    
    ctx.set_source_rgb(*md_gray)
    ctx.set_font_size(14 * mmppt)
    
    continue_text_box(ctx, 15, 15 + 143, 16.8 * mmppt, text)

    # text on the bottom
    ctx.move_to(10, 236)
    ctx.set_source_rgb(*md_gray)
    ctx.set_font_size(10.8 * mmppt)
    ctx.show_text('This map came from safetymaps.org. You can visit and make your own Safety Maps for free!')

    ctx.restore()

def draw_a4_master(ctx, format):
    """
    """
    ctx.save()
    
    # top-left of page, draw the header
    ctx.set_source_rgb(*md_gray)

    set_font_face_from_file(ctx, 'assets/VAGRoundedStd-Bold.otf')
    ctx.set_font_size(24 * mmppt)

    ctx.move_to(21, 18)
    ctx.show_text('Safety Maps')
    
    set_font_face_from_file(ctx, 'assets/HelveticaNeue.ttc')
    ctx.set_font_size(8 * mmppt)

    ctx.move_to(21, 22)
    ctx.show_text('Unique URL for this map: www.safetymaps.org/maps/URLtokenwithmanycharacters')
    
    # top-right of page, draw the hands icon
    place_hands(ctx, 192, 11, format)
    
    # bottom-left of page, draw the footer
    ctx.set_font_size(9 * mmppt)

    ctx.move_to(25.5, 276)
    ctx.show_text('2011 Do projects.')

    ctx.set_font_size(8 * mmppt)

    ctx.move_to(20, 281)
    ctx.show_text('Safety Maps and OpenStreetMap.org data are offered to you under')
    ctx.move_to(20, 281 + 9 * mmppt)
    ctx.show_text('a Creative Commons Attribution-Noncommercial-Share Alike license.')
    ctx.move_to(20, 281 + 18 * mmppt)
    ctx.show_text('Please see creativecommons.org/licenses/by-nc-sa/3.0 for details.')
    
    place_cc_logo(ctx, 20, 272.7, 4.7, 4.7)
    place_do_logo(ctx, 192, 280)
    
    ctx.restore()

def draw_letter_master(ctx, format):
    """
    """
    ctx.save()
    
    # top-left of page, draw the header
    ctx.set_source_rgb(*md_gray)

    set_font_face_from_file(ctx, 'assets/VAGRoundedStd-Bold.otf')
    ctx.set_font_size(24 * mmppt)

    ctx.move_to(22, 12)
    ctx.show_text('Safety Maps')
    
    set_font_face_from_file(ctx, 'assets/HelveticaNeue.ttc')
    ctx.set_font_size(8 * mmppt)

    ctx.move_to(22, 16)
    ctx.show_text('Unique URL for this map: www.safetymaps.org/maps/URLtokenwithmanycharacters')
    
    # top-right of page, draw the hands icon
    place_hands(ctx, 193, 6, format)
    
    # bottom-left of page, draw the footer
    ctx.set_font_size(6 * mmppt)

    ctx.move_to(25, 268)
    ctx.show_text('2011 Do projects.')

    ctx.move_to(43, 268)
    ctx.show_text('Safety Maps and OpenStreetMap.org data are offered to you under')
    ctx.move_to(43, 268 + 7 * mmppt)
    ctx.show_text('a Creative Commons Attribution-Noncommercial-Share Alike license.')
    ctx.move_to(43, 268 + 14 * mmppt)
    ctx.show_text('Please see creativecommons.org/licenses/by-nc-sa/3.0 for details.')
    
    place_cc_logo(ctx, 21, 265.7, 3.3, 3.3)
    place_do_logo(ctx, 192, 268)
    
    ctx.restore()

parser = OptionParser()

parser.set_defaults(recipient='Fred', sender='Wilma', emergency='earthquake',
                    place='Dolores Park playground', point=(37.75883, -122.42689),
                    bbox=(37.7669, -122.4177, 37.7565, -122.4302),
                    text='Sed ut perspiciatis, unde omnis iste natus error sit ' \
                    + 'voluptatem accusantium doloremque laudantium, totam rem ' \
                    + 'aperiam eaque ipsa, quae ab illo invent ore veritatis et ' \
                    + 'quasi architecto beatae vitae dicta sunt, explicabo.\n\n' \
                    + 'My love always, G.',
                    paper='letter', format='4up')

papers = 'a4 letter'.split()
formats = '4up 2up-fridge poster'.split()

parser.add_option('-p', '--paper', dest='paper',
                  help='Choice of papers: %s.' % ', '.join(papers),
                  choices=papers)

parser.add_option('-f', '--format', dest='format',
                  help='Choice of formats: %s.' % ', '.join(formats),
                  choices=formats)

parser.add_option('-m', '--meeting-point', dest='point',
                  help='Latitude and longitude of meeting point.',
                  type='float', nargs=2)

parser.add_option('-b', '--bbox', dest='bbox',
                  help='North, west, south, east bounds of map to show.',
                  type='float', nargs=4)

parser.add_option('-r', '--recipient', dest='recipient',
                  help='Name of recipient.')

parser.add_option('-s', '--sender', dest='sender',
                  help='Name of sender.')

parser.add_option('-e', '--emergency', dest='emergency',
                  help='Name of emergency.')

parser.add_option('-n', '--place-name', dest='place',
                  help='Name of meeting place.')

parser.add_option('-t', '--text', dest='text',
                  help='Message text, "-" to use stdin.')

def main(marker, paper, format, bbox, emergency, place, recipient, sender, text):
    """
    """
    mark = Location(*marker)
    
    handle, filename = mkstemp(prefix='safetymap-', suffix='.pdf')
    close(handle)

    if paper == 'a4':
        surf = PDFSurface(filename, 210*ptpmm, 297*ptpmm)
    
    elif paper == 'letter':
        surf = PDFSurface(filename, 8.5*ptpin, 11*ptpin)
    
    ctx = Context(surf)
    
    ctx.scale(ptpmm, ptpmm)

    set_font_face_from_file(ctx, 'assets/HelveticaNeue.ttc')
    
    if paper == 'a4':
        draw_a4_master(ctx, format)
        ctx.translate(19, 24)
    
    elif paper == 'letter':
        draw_letter_master(ctx, format)
        ctx.translate(21, 18)

    ctx.set_line_width(.25 * mmppt)
    ctx.set_source_rgb(*md_gray)
    ctx.set_dash([3 * mmppt])

    reps = {'4up': 4, '2up-fridge': 2, 'poster': 0}
    
    if reps[format]:
        card_img, mark_point = get_map_image(bbox, 84, 39, mark)
        
    for i in range(reps[format]):
    
        # dashed outlines
        ctx.move_to(0, 61)
        ctx.line_to(0, 0)
        ctx.line_to(173, 0)
        ctx.line_to(173, 61)
        #ctx.move_to(86, 0)
        #ctx.line_to(86, 61)
        ctx.stroke()
    
        # two card sides and contents
        draw_card_left(ctx, recipient, sender, text)
        ctx.translate(86.5, 0)

        draw_card_right(ctx, card_img, mark_point, emergency, place)
        ctx.translate(-86.5, 61)

    if format == '4up':
        # bottom dashed outline
        ctx.move_to(0, 0)
        ctx.line_to(172, 0)
        ctx.stroke()

    elif format == '2up-fridge':
        # prepare to draw sideways
        ctx.translate(0, 122.5)
        ctx.rotate(-pi/2)

        ctx.rectangle(0, 0, 122.5, 173)
        ctx.stroke()

        poster_img, mark_point = get_map_image(bbox, 109, 77, mark)
        draw_small_poster(ctx, poster_img, mark_point, emergency, place, recipient, sender, text)

    elif format == 'poster':
        ctx.rectangle(0, 0, 173, 245)
        ctx.stroke()

        poster_img, mark_point = get_map_image(bbox, 153, 108, mark)
        draw_large_poster(ctx, poster_img, mark_point, emergency, place, recipient, sender, text)

    surf.finish()
    chmod(filename, 0644)
    return filename

if __name__ == '__main__':
    opts, args = parser.parse_args()
    
    text = (opts.text == '-') and stdin.read().strip() or opts.text

    print main(opts.point, opts.paper, opts.format, opts.bbox, opts.emergency,
               opts.place, opts.recipient, opts.sender, text)

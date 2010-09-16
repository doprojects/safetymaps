""" Generate a PDF of a meeting point map.
"""

from sys import stderr
from os import close, unlink, chmod
from os.path import dirname, join as pathjoin
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

def place_image(context, img, width, height):
    """ Add an image to a given context, at a given size in millimeters.
    
        Assume that the scale matrix of the context is already in mm.
    """
    # push
    context.save()
    
    # switch to point scale for the sake of the image dimensions
    context.scale(mmppt, mmppt)

    # determine the scale needed to make the image the requested size
    xscale = width * ptpmm / img.get_width()
    yscale = height * ptpmm / img.get_height()
    context.scale(xscale, yscale)

    # paint the image
    context.set_source_surface(img, 0, 0)
    context.paint()

    # pop
    context.restore()

def place_hands(context, format):
    """ Add the hands icon, flush-right.
    """
    # push
    context.save()

    # switch to point scale for the sake of the drawing dimensions
    context.scale(mmppt, mmppt)
    
    # Guess what? It's a pain in the ass to use SVG from Cairo:
    # http://cairographics.org/pyrsvg
    svg = 'hands-%(format)s.svg' % locals()
    svg = rsvg.Handle(pathjoin(dirname(__file__), svg))

    w, h, w_, h_ = svg.get_dimension_data()
    context.translate(-w, 0)

    svg.render_cairo(context)

    # pop
    context.restore()

def place_marker(context):
    """ Draw a provisional-looking marker.
    """
    # push
    context.save()

    # switch to point scale for the sake of the drawing dimensions
    context.scale(mmppt, mmppt)

    # adjust for marker center
    context.translate(-14.1735, -14.1735)
    
    # draw the marker
    context.move_to(23.622, 9.449)
    context.rel_line_to(-1.94, 1.94)
    context.rel_line_to(2.784, 2.784)
    context.rel_line_to(-2.784, 2.785)
    context.rel_line_to(1.94, 1.94)
    context.rel_line_to(4.725, -4.725)
    context.line_to(23.622, 9.449)

    context.move_to(9.448, 4.725)
    context.rel_line_to(1.94, 1.94)
    context.rel_line_to(2.784, -2.784)
    context.rel_line_to(2.784, 2.784)
    context.rel_line_to(1.94, -1.94)
    context.line_to(14.173, 0)
    context.line_to(9.448, 4.725)

    context.move_to(14.173, 24.466)
    context.rel_line_to(-2.784, -2.785)
    context.rel_line_to(-1.939, 1.94)
    context.rel_line_to(4.724, 4.725)
    context.rel_line_to(4.725, -4.725)
    context.rel_line_to(-1.94, -1.94)
    context.line_to(14.173, 24.466)

    context.move_to(6.664, 11.389)
    context.rel_line_to(-1.939, -1.94)
    context.line_to(0, 14.173)
    context.rel_line_to(4.725, 4.725)
    context.rel_line_to(1.939, -1.94)
    context.rel_line_to(-2.783, -2.785)
    context.line_to(6.664, 11.389)

    context.set_source_rgb(0, 0, 0)
    context.fill()

    # pop
    context.restore()

def draw_rounded_box(ctx, width, height):
    """ Draw a rounded box with corner radius of 2.
    
        Don't modify the matrix stack.
    """
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
    ctx.set_source_rgb(.8, .8, .8)
    ctx.set_dash([])
    ctx.stroke()

def get_map_image(bbox, width, height, target_dpi=100):
    """ Get a cairo ImageSurface for a given bounding box.
    
        Try to match a target DPI. Width and height are given in millimeters!
    """
    prov = TemplatedMercatorProvider('http://127.0.0.1/~migurski/TileStache/tilestache.cgi/osm/{Z}/{X}/{Y}.png')
    locA, locB = Location(bbox[0], bbox[1]), Location(bbox[2], bbox[3])
    
    aspect = float(width) / float(height)
    
    mmaps = [mapByExtentZoomAspect(prov, locA, locB, zoom, aspect)
             for zoom in range(10, 19)]

    inches_wide = width * ptpmm * inppt
    resolutions = [(mmap.dimensions.x / inches_wide, mmap) for mmap in mmaps]
    differences = [(abs(dpi - target_dpi), mmap) for (dpi, mmap) in resolutions]
    
    diff, mmap = sorted(differences)[0]

    handle, filename = mkstemp(suffix='.png')
    close(handle)
    
    mmap.draw().save(filename)
    img = ImageSurface.create_from_png(filename)
    unlink(filename)
    
    return img

def continue_text_box(ctx, left, width, leading, text):
    """ Fill up a text box with words.
    
        This function can be called repeatedly with parts of a paragraph.
    """
    words = text.split()
    
    for word in words:
        x, y = ctx.get_current_point()
        x += ctx.text_extents(word)[4]
        
        if x > width:
            ctx.move_to(left, y + leading)

        ctx.show_text(word + ' ')

def today():
    """ E.g. "6 Sep 2010"
    """
    return strftime('%d %b %Y').lstrip('0')

def draw_card_left(ctx, name):
    """ Draw out the left-hand side of a card.
    
        Modify and restore the matrix stack.
    """
    ctx.save()

    # big title text
    ctx.move_to(4, 7.5)
    ctx.set_font_size(11 * mmppt)
    
    phrases = [((.2, .2, .2),  'Safety Map for '),
               ((0, .75, .25), name),
               ((.2, .2, .2),  ' made on '),
               ((0, .75, .25), today())]
    
    for (rgb, phrase) in phrases:
        ctx.set_source_rgb(*rgb)
        ctx.show_text(phrase)

    # text on the bottom
    ctx.move_to(4, 58.75)
    ctx.set_source_rgb(.6, .6, .6)
    ctx.set_font_size(4 * mmppt)
    ctx.show_text('This map came from safetymaps.org. You can visit and make your own Safety Maps for free!')

    ctx.translate(1, 1)
    draw_rounded_box(ctx, 84, 59)

    ctx.restore()

def draw_card_right(ctx, img, name):
    """ Draw out the right-hand side of a card.
    
        Modify and restore the matrix stack.
    """
    ctx.save()
    
    ctx.save()
    ctx.translate(1, 18)
    place_image(ctx, img, 84, 39)
    ctx.restore()

    # big title text
    ctx.move_to(4, 7.5)
    ctx.set_font_size(11 * mmppt)
    
    phrases = [((.2, .2, .2),  'Safety Map for '),
               ((0, .75, .25), name),
               ((.2, .2, .2),  ' made on '),
               ((0, .75, .25), today())]
    
    for (rgb, phrase) in phrases:
        ctx.set_source_rgb(*rgb)
        ctx.show_text(phrase)
    
    # explanation
    ctx.move_to(4, 9 + 9.6 * mmppt)
    ctx.set_font_size(8 * mmppt)
    
    phrases = [((.2, .2, .2),  "In case of"),
               ((0, .75, .25), "fire or explosion near our apartment,"),
               ((.2, .2, .2),  "let’s meet at"),
               ((0, .75, .25), "Madison Square park."),
               ((.2, .2, .2),  "I’ve marked the spot on this map:")]
    
    for (rgb, phrase) in phrases:
        ctx.set_source_rgb(*rgb)
        continue_text_box(ctx, 4, 78, 9.6 * mmppt, phrase)

    # text on the bottom
    ctx.move_to(4, 58.75)
    ctx.set_source_rgb(.6, .6, .6)
    ctx.set_font_size(4 * mmppt)
    ctx.show_text('This map came from safetymaps.org. You can visit and make your own Safety Maps for free!')

    ctx.translate(1, 1)
    draw_rounded_box(ctx, 84, 59)

    ctx.restore()

def draw_header(ctx, format):
    """ Draw out the header.
    
        Modify and restore the matrix stack.
    """
    ctx.save()
    
    # top-left of the page, draw the header
    ctx.translate(20, 20)

    face = pathjoin(dirname(__file__), '../design/fonts/MgOpen/MgOpenModataBold.ttf')
    face = create_cairo_font_face_for_file(face)
    ctx.set_font_face(face)
    ctx.set_font_size(24 * mmppt)
    ctx.set_source_rgb(.8, .8, .8)
    ctx.show_text('Safety Maps')
    
    # top-right of the page, draw the hands icon
    ctx.translate(170, -8)
    place_hands(ctx, format)

    ctx.restore()

parser = OptionParser()

parser.set_defaults(name='Fred', paper='letter', format='4up', point=(37.75883, -122.42689), bbox=(37.7669, -122.4177, 37.7565, -122.4302))

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

parser.add_option('-n', '--name', dest='name',
                  help='Name of recipient - keep it short!')

def main(marker, paper, format, bbox, name):
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
    ctx.select_font_face('Helvetica')
    
    draw_header(ctx, format)

    if paper == 'a4':
        ctx.translate(19, 24)
    
    elif paper == 'letter':
        raise Exception('wah')
        ctx.translate(22, 17.5)

    img = get_map_image(bbox, 84, 39)
    reps = {'4up': 4, '2up-fridge': 2, 'poster': 0}
    
    for i in range(reps[format]):
    
        # dashed outlines
        ctx.move_to(0, 61)
        ctx.line_to(0, 0)
        ctx.line_to(172, 0)
        ctx.line_to(172, 61)
        #ctx.move_to(86, 0)
        #ctx.line_to(86, 61)

        ctx.set_line_width(.25 * mmppt)
        ctx.set_source_rgb(.8, .8, .8)
        ctx.set_dash([3 * mmppt])
        ctx.stroke()
    
        # two card sides and contents
        draw_card_left(ctx, name)
        ctx.translate(86, 0)

        draw_card_right(ctx, img, name)
        ctx.translate(-86, 61)

    if format == '2up-fridge':
        # prepare to draw sideways
        ctx.translate(*ctx.device_to_user(0, 0))
        ctx.translate(19, 269)
        ctx.rotate(-pi/2)
    
        # dashed outlines
        ctx.rectangle(0, 0, 123, 172)

        ctx.set_line_width(.25 * mmppt)
        ctx.set_source_rgb(.8, .8, .8)
        ctx.set_dash([3 * mmppt])
        ctx.stroke()
    
        # round box and contents
        ctx.translate(1, 1)
        draw_rounded_box(ctx, 121, 170)

        ctx.translate(6, 26)
        img = get_map_image(bbox, 109, 77)
        place_image(ctx, img, 109, 77)

        ctx.rectangle(0, 0, 109, 77)
        ctx.set_line_width(1 * mmppt)
        ctx.set_source_rgb(.8, .8, .8)
        ctx.set_dash([])
        ctx.stroke()

    surf.finish()
    chmod(filename, 0644)
    return filename

if __name__ == '__main__':
    opts, args = parser.parse_args()

    print main(opts.point, opts.paper, opts.format, opts.bbox, opts.name)

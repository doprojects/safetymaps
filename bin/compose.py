""" Generate a PDF of a meeting point map.
"""

from os import close, unlink
from tempfile import mkstemp
from optparse import OptionParser
from ModestMaps import mapByExtentZoom, mapByCenterZoom
from ModestMaps.Geo import Location
from ModestMaps.Core import Point
from ModestMaps.Providers import TemplatedMercatorProvider
from cairo import PDFSurface, ImageSurface, Context

mmppt = 0.352777778
inppt = 0.013888889

ptpin = 1./inppt
ptpmm = 1./mmppt

def mapByExtentZoomAspect(prov, locA, locB, zoom, aspect):
    """
    """
    mmap = mapByExtentZoom(prov, Location(lat1, lon1), Location(lat2, lon2), zoom)
    center = mmap.pointLocation(Point(mmap.dimensions.x/2, mmap.dimensions.y/2))
    
    if aspect < float(mmap.dimensions.x) / float(mmap.dimensions.y):
        # make taller
        dimensions = Point(mmap.dimensions.x, int(mmap.dimensions.x / aspect))

    else:
        # make wider
        dimensions = Point(int(mmap.dimensions.y * aspect), mmap.dimensions.y)
    
    return mapByCenterZoom(prov, center, zoom, dimensions)

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

def place_marker(context):
    """
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
    """
    """
    radius = 2
    bezier = radius / 2
    
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
    ctx.stroke()

parser = OptionParser()

parser.set_defaults(format='letter', point=(37.75883, -122.42689), bbox=(37.7669, -122.4177, 37.7565, -122.4302))

formats = 'a4 letter'.split()

parser.add_option('-f', '--format', dest='format',
                  help='Choice of formats: %s.' % ', '.join(formats),
                  choices=formats)

parser.add_option('-m', '--meeting-point', dest='point',
                  help='Latitude and longitude of meeting point.',
                  type='float', nargs=2)

parser.add_option('-b', '--bbox', dest='bbox',
                  help='North, west, south, east bounds of map to show.',
                  type='float', nargs=4)

if __name__ == '__main__':
    options, args = parser.parse_args()
    
    prov = TemplatedMercatorProvider('http://127.0.0.1/~migurski/TileStache/tilestache.cgi/osm/{Z}/{X}/{Y}.png')
    lat1, lon1, lat2, lon2 = options.bbox
    mmap = mapByExtentZoomAspect(prov, Location(lat1, lon1), Location(lat2, lon2), 16, 86./61.)
    
    mark = Location(*options.point)
    
    print mark, mmap.locationPoint(mark)
    
    handle, filename = mkstemp(suffix='.png')
    close(handle)
    
    #mmap.draw().save(filename)
    #img = ImageSurface.create_from_png(filename)
    
    if options.format == 'a4':
        surf = PDFSurface('out.pdf', 210*ptpmm, 297*ptpmm)
    
    elif options.format == 'letter':
        surf = PDFSurface('out.pdf', 8.5*ptpin, 11*ptpin)
    
    ctx = Context(surf)
    
    ctx.scale(ptpmm, ptpmm)

    if options.format == 'a4':
        ctx.translate(19 + 86, 26.5)
    
    elif options.format == 'letter':
        ctx.translate(22 + 86, 17.5)

    ctx.move_to(1, 1)
    draw_rounded_box(ctx, 84, 59)
    
    #for i in range(4):
    #    place_image(ctx, img, 86, 61)
    #    
    #    # push
    #    ctx.save()
    #
    #    # marker center point expressed in millimeters
    #    xpos = 86 * mmap.locationPoint(mark).x / float(img.get_width())
    #    ypos = 61 * mmap.locationPoint(mark).y / float(img.get_height())
    #    ctx.translate(xpos, ypos)
    #
    #    place_marker(ctx)
    #
    #    # pop
    #    ctx.restore()
    #    
    #    ctx.translate(0, 61)
    #
    #ctx.select_font_face('Helvetica')
    #ctx.set_font_size(5)
    #ctx.show_text('Hello World')
    
    surf.finish()
    unlink(filename)

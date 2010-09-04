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

parser = OptionParser()

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
    
    handle, filename = mkstemp(suffix='.png')
    close(handle)
    
    mmap.draw().save(filename)
    
    surf = PDFSurface('out.pdf', 210*ptpmm, 297*ptpmm)
    
    img = ImageSurface.create_from_png(filename)
    
    ctx = Context(surf)
    
    ctx.scale(ptpmm, ptpmm)

    ctx.translate(19 + 86, 26.5)
    
    for i in range(4):
        place_image(ctx, img, 86, 61)
        ctx.translate(0, 61)
    
    surf.finish()
    unlink(filename)

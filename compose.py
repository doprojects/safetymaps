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
    mmap = mapByExtentZoomAspect(prov, Location(lat1, lon1), Location(lat2, lon2), 16, 1)
    
    handle, filename = mkstemp(suffix='.png')
    close(handle)
    
    mmap.draw().save(filename)
    
    surf = PDFSurface('out.pdf', 8.5*ptpin, 11*ptpin)
    
    img = ImageSurface.create_from_png(filename)
    
    ctx = Context(surf)
    
    ctx.scale(ptpmm, ptpmm)

    ctx.translate(10, 10)

    ctx.save()
    ctx.scale(mmppt, mmppt)
    scale = 195.9 * ptpmm / img.get_width()
    ctx.scale(scale, scale)
    ctx.set_source_surface(img, 0, 0)
    ctx.paint()
    ctx.restore()
    
    surf.finish()
    unlink(filename)

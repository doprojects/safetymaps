""" Generate a PDF of a meeting point map.
"""

from os import close, unlink
from tempfile import mkstemp
from optparse import OptionParser
from ModestMaps import mapByExtentZoom
from ModestMaps.Geo import Location
from ModestMaps.OpenStreetMap import Provider
from cairo import PDFSurface, ImageSurface, Context

parser = OptionParser()

parser.add_option('-m', '--meeting-point', dest='point',
                  help='Latitude and longitude of meeting point.',
                  type='float', nargs=2)

parser.add_option('-b', '--bbox', dest='bbox',
                  help='North, west, south, east bounds of map to show.',
                  type='float', nargs=4)

if __name__ == '__main__':
    options, args = parser.parse_args()
    
    osm = Provider()
    lat1, lon1, lat2, lon2 = options.bbox
    mmap = mapByExtentZoom(osm, Location(lat1, lon1), Location(lat2, lon2), 16)
    
    handle, filename = mkstemp(suffix='.png')
    close(handle)
    
    mmap.draw(True).save(filename)
    
    surf = PDFSurface('out.pdf', 612, 792)
    
    img = ImageSurface.create_from_png(filename)
    
    ctx = Context(surf)
    
    ctx.set_source_surface(img, 10, 10)
    ctx.paint()
    
    surf.finish()
    unlink(filename)

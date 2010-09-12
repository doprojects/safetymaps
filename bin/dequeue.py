from sys import stderr
from optparse import OptionParser
from urlparse import urlparse, urlunparse, urljoin
from httplib import HTTPConnection
from json import loads
from time import time

from compose import main as compose

parser = OptionParser()

parser.set_defaults(time_limit=0)

parser.add_option('-t', '--time-limit', dest='time_limit',
                  help='Time limit in seconds',
                  type=int)

if __name__ == '__main__':
    
    opts, args = parser.parse_args()
    
    url = urlparse(args[0])
    due = time() + opts.time_limit

    while True:
    
        conn = HTTPConnection(url.netloc)
        conn.request('GET', url.path)
        resp = conn.getresponse()
        
        if resp.status != 200:
            print resp.status
            break

        job = loads(resp.read())
    
        marker = job['place']['location']
        bbox = job['map']['bounds']
        paper = job['map']['paper']
        format = job['map']['format']
        name = job['recipient']['name']
        
        print >> stderr, 'Map for', name, '...',
        
        filename = compose(marker, paper, format, bbox, name)
        print >> stderr, filename,
        print >> stderr, urljoin(urlunparse(url), job['put-back']['pdf'])
        
        if time() > due:
            break

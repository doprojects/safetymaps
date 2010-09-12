from sys import stderr
from optparse import OptionParser
from urlparse import urlparse, urlunparse, urljoin
from httplib import HTTPConnection
from json import loads
from time import time

from compose import main as compose

def path(url):
    """
    """
    return url.path + (url.query and '?'+url.query or '')

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
        conn.request('GET', path(url))
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

        base_url = urlunparse(url)
        post_url = urljoin(base_url, job['post-back']['pdf'])

        print >> stderr, post_url,
        
        post_url = urlparse(post_url)
        
        conn = HTTPConnection(post_url.netloc)
        conn.request('POST', path(post_url), open(filename, 'r'))
        resp = conn.getresponse()
        
        print >> stderr, resp.status, resp.read()
        
        if time() > due:
            break

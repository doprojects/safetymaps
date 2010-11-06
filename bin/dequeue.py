from sys import stderr
from os import unlink
from optparse import OptionParser
from urlparse import urlparse, urlunparse, urljoin
from itertools import product
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
            print >> stderr, resp.status
            break

        job = loads(resp.read())
    
        marker = job['place']['location']
        bbox = job['map']['bounds']
        emergency = job['place']['emergency']
        place = job['place']['name']
        recipient = job['recipient']['name']
        sender = job['sender']['name']
        text = job['place']['full-note']
        
        print 'Maps for', recipient, '...'
        
        for (paper, format) in product(job['papers'], job['formats']):
            filename = compose(marker, paper, format, bbox, emergency, place, recipient, sender, text)

            print filename,
    
            base_url = urlunparse(url)
            post_url = urljoin(base_url, job['post-back']['pdf'])
    
            print post_url,
            
            post_url = urlparse(post_url)
            
            conn = HTTPConnection(post_url.netloc)
            head = {'X-Print-Paper': paper, 'X-Print-Format': format}
            conn.request('POST', path(post_url), open(filename, 'r'), head)
            resp = conn.getresponse()
            
            print resp.status, resp.read()
            
            unlink(filename)
        
        if time() > due:
            break

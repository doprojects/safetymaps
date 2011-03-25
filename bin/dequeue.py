from sys import stderr
from os import unlink
from optparse import OptionParser
from urlparse import urlparse, urlunparse, urljoin
from itertools import product
from httplib import HTTPConnection
from json import loads, dumps
from time import time, sleep
from hashlib import md5

from compose import main as compose

def path(url):
    """
    """
    return url.path + (url.query and '?'+url.query or '')

parser = OptionParser(usage="%prog [options] <API URL>")

parser.set_defaults(time_limit=0, admin_secret='example')

parser.add_option('-t', '--time-limit', dest='time_limit',
                  help='Time limit in seconds',
                  type=int)

parser.add_option('-s', '--admin-secret', dest='admin_secret',
                  help='Admin secret for writing data back')

if __name__ == '__main__':

    opts, args = parser.parse_args()
    
    url = urlparse(args[0])
    due = time() + opts.time_limit
    
    # signed cookie must match site/lib/lib.php:write_userdata()
    userdata = dumps({'is_admin': True}, separators=(',', ':'))
    cookie = userdata + ' ' + md5(userdata + opts.admin_secret).hexdigest()

    while time() <= due:
    
        conn = HTTPConnection(url.netloc)
        conn.request('GET', path(url))
        resp = conn.getresponse()
        
        if resp.status == 200:
            pass
        elif resp.status == 404:
            sleep(5)
            continue
        else:
            print >> stderr, 'Unexpected status:', resp.status
            exit(1)

        job = loads(resp.read())
    
        map_href = job['map-href']
        marker = job['place']['location']
        bbox = job['map']['bounds']
        emergency = job['place']['emergency']
        place = job['place']['name']
        recipient = job['recipient']['name']
        sender = job['sender']['name']
        text = job['place']['note_full']
        sender_is_recipient = job['sender-is-recipient']
        
        pdf_href = job['post-back']['pdf']
        error_href = job['post-back']['error']
        
        base_href = urlunparse(url)

        print 'Maps for', recipient.encode('ascii', 'replace'), '...'
        
        for (paper, format) in product(job['papers'], job['formats']):
            try:
                filename = compose(marker, paper, format, bbox, emergency, place,
                                   recipient, sender, text, sender_is_recipient,
                                   urljoin(base_href, map_href))
    
                print filename,
        
                post_url = urljoin(base_href, pdf_href)
                post_url = urlparse(post_url)
                
                conn = HTTPConnection(post_url.netloc)
                head = {'X-Print-Paper': paper, 'X-Print-Format': format, 'Cookie': cookie}
                conn.request('POST', path(post_url), open(filename, 'r'), head)
                resp = conn.getresponse()
                
                print resp.status, resp.read().strip()
                
                unlink(filename)

            except ValueError, error:
                error_url = urljoin(base_href, error_href)
                error_url = urlparse(error_url)
        
                print 'Uh-oh:', error
                
                conn = HTTPConnection(error_url.netloc)
                head = {'X-Print-Paper': paper, 'X-Print-Format': format, 'Cookie': cookie, 'Referer': path(url)}
                conn.request('POST', path(error_url), str(error), head)
                resp = conn.getresponse()
                
                print resp.status, resp.read().strip()

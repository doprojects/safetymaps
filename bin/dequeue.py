from sys import stderr
from os import rename, chmod

from MySQLdb import connect

from compose import main as compose

if __name__ == '__main__':
    
    db = connect(host='localhost', user='safetymaps', passwd='s4f3tym4ps', db='safetymaps')
    cur = db.cursor()
    
    while True:
    
        cur.execute('BEGIN')
    
        cur.execute("""SELECT u.id, m.id, r.id
                       FROM recipients AS r
                       LEFT JOIN maps AS m
                         ON m.id = r.map_id
                       LEFT JOIN users AS u
                         ON u.id = r.user_id
                       WHERE r.sent IS NULL
                       HAVING u.id AND m.id
                       ORDER BY r.id ASC
                       LIMIT 1""")
        
        ids = cur.fetchone()
        
        if ids:
            user_id, map_id, recipient_id = ids

        else:
            cur.execute('ROLLBACK')
            break
        
        cur.execute("""SELECT m.place_lat, m.place_lon,
                              m.bbox_north, m.bbox_west, m.bbox_south, m.bbox_east,
                              m.paper, m.format,
                              r.name
                       FROM recipients AS r
                       LEFT JOIN maps AS m
                         ON m.id = r.map_id
                       WHERE r.id = %(recipient_id)d
                       ORDER BY r.id ASC""" % locals())

        row = cur.fetchone()
        
        marker = row[0:2]
        bbox = row[2:6]
        paper = row[6]
        name = row[8]
        
        print >> stderr, 'Map', map_id, 'for', name, '...',
        
        filename = compose(marker, paper, bbox, name)
        realname = 'out-%(map_id)06d-%(recipient_id)06d.pdf' % locals()
        
        rename(filename, realname)
        chmod(realname, 0644)
        
        cur.execute('UPDATE recipients SET sent=NOW() WHERE id=%(recipient_id)d' % locals())
        cur.execute('COMMIT')

        print >> stderr, realname
        
    cur.close()
    db.close()

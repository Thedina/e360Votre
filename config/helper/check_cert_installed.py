#!/usr/bin/python
import os

""" Simple script to check if an SSL certificate exists for apache
"""

CERT_FILE = '/etc/apache2/ssl/e360cert.pem'

def main():
    print int(os.path.isfile(CERT_FILE))

if __name__ == '__main__':
    main()

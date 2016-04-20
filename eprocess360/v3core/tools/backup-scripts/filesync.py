#!/usr/bin/python
import os, subprocess

HOME_DIR = '/home/slcoepro'
DB_DIR = 'dbbackup'
WEB_DIR = 'public_html'
TARGET_DOMAIN = 'slco.eprocess360.com'
TARGET_LOGIN = 'slcoepro'
PASS = '7XwXCvKNSQ3%'

def main():
	target = TARGET_LOGIN + '@' + TARGET_DOMAIN
	target_web= target + ':~/' + WEB_DIR
	target_db= target + ':~/' + DB_DIR
	ssh_cmd = '--rsh=/usr/bin/sshpass -p ' + PASS + ' ssh -o StrictHostKeyChecking=no -l ' + TARGET_LOGIN
	subprocess.call(['rsync', '-az', ssh_cmd, target_db, HOME_DIR])
	subprocess.call(['rsync', '-az', ssh_cmd, target_web, HOME_DIR])


if __name__ == "__main__":
	main()
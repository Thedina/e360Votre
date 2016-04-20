#!/usr/local/bin/python2.7
import sys, os, time
BACKUP_DIR = '/home/slcoepro/dbbackup/'
DB_NAME = 'slcoepro_db'
DB_USER = 'slcoepro_dbuser'
DB_PASS = '!72^aK}q&W-3'

def main():
	date_string = time.strftime('-%Y%m%d')
	dump_path = BACKUP_DIR + DB_NAME + date_string + '.tar.gz'
	os.system("mysqldump --user='" + DB_USER + "' --password='" + DB_PASS + "' --databases " + DB_NAME + " | gzip -c > " + dump_path)
	sys.exit(0)


if __name__ == "__main__":
    main()
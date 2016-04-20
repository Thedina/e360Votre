#!/usr/bin/python
import os, subprocess, sys

PID_PATH = '/var/run/mysqld/mysqld.pid'

def main():
	try: 
		f = open(PID_PATH, 'r')
		file_pid = int(f.read())
		f.close()
	except (IOError, ValueError):
		do_restart()
		sys.exit(0)

	p = subprocess.Popen(['pidof', 'mysqld'], stdin=subprocess.PIPE, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
	output, err = p.communicate()
	
	try:
		cur_pid = int(output)
	except ValueError:
		cur_pid = 0
	
	if cur_pid != file_pid:
		do_restart()

	sys.exit(0)

def do_restart():
	subprocess.Popen(['/etc/init.d/mysqld', 'start'])

if __name__ == "__main__":
	main()
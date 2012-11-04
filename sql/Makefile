db:
	psql -f signage.sql signage

reinstall: regendb db
	@echo 'all done'

regendb:
	psql -c 'drop database if exists signage;' template1
	psql -c "create database signage encoding='UTF8';" template1

clean:
	@rm -vf *~

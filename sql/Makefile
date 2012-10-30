db:
	psql -f signage.sql signage

reinstall: clean db
	@echo 'all done'

clean:
	psql -c 'drop database if exists signage;' template1
	psql -c "create database signage encoding='UTF8';" template1

clean:
	@rm -vf *~
	@cd lib; make clean
	@cd screen; make clean
	@cd sql; make clean

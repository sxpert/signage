all: tests

tests: 
	@php feeds/apod.php

cleanfeeds: cleanapod

cleanapod:
	rm -rf ../cache/images/apod

clean:
	@rm -vf *~
	@cd feeds; make clean

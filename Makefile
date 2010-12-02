all: www/files templates/cache
	#

templates/cache:
	mkdir templates/cache
	chmod a+rwX templates/cache

www/files:
	mkdir www/files
	chmod a+rwX www/files

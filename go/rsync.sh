#!/bin/bash

while true ; do
	chown -R liudonghai:work /home/work/orp/app/stock/go/hello
	chmod -R 775  /home/work/orp/app/stock/go/hello
	rsync -HavP /home/work/orp/app/stock/go/hello/* /home/work/.jumbo/lib/go/site/src/hello/
	sleep 2
done
#!/bin/bash

cd ../../
if [ ! -f index.php ] ; then
	echo "index.php is not exist!"
fi

index=0
for stock in `ls application/data/qq_stock_data/`; do
	echo "start to load [$stock] and index is [$index]"
	/home/work/osp/php/bin/php index.php stock loadQQData $stock
	((index++))
done




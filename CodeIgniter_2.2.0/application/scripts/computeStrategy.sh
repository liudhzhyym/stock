#!/bin/bash

cd ../../
#return


index=0
for i in `seq 300` ; do
	((index=i-1))
	/home/work/osp/php/bin/php index.php strategy computeIncomeByIndex $index
done 

# for stock in `cat application/data/stockList.conf` ; do
# 	for dayTime in `cat application/data/timeList.conf` ; do
# 		/home/work/osp/php/bin/php index.php income checkStockIncome $stock $dayTime $keepDay
# 		echo "checkStockIncome of [$stock] at [$dayTime]"
# 	done
# 	#((index++))
# done




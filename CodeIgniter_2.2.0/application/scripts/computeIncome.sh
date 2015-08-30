#!/bin/bash

cd ../../
#return

keepDay=10

for dayTime in `/home/work/osp/php/bin/php index.php income getMarketTimeListForShell` ; do
	/home/work/osp/php/bin/php index.php income checkStockIncomeByDay $dayTime $keepDay
done 

# for stock in `cat application/data/stockList.conf` ; do
# 	for dayTime in `cat application/data/timeList.conf` ; do
# 		/home/work/osp/php/bin/php index.php income checkStockIncome $stock $dayTime $keepDay
# 		echo "checkStockIncome of [$stock] at [$dayTime]"
# 	done
# 	#((index++))
# done




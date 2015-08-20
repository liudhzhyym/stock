#!/bin/bash

function random()
{
    min=$1
    max=$2-$1
    num=$RANDOM
    ((retnum=num%max+min))
    #进行求余数运算即可
    echo $retnum
    #这里通过echo 打印出来值，然后获得函数的，stdout就可以获得值
}

cd ../../
if [ ! -f index.php ] ; then
	echo "index.php is not exist!"
fi

index=$1
for strategy in `cat application/data/strategy2load.conf`; do
	for day in `cat application/data/timeList.conf` ; do
		/home/work/osp/php/bin/php index.php stock parseDataByIndexAndDay $index $day
		#rand=$(random 3 5)
		#echo "sleep [$rand]s"
		#sleep $rand
	done
#	exit
	((index++))
done




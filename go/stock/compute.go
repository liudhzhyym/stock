package main

import (
    "fmt"
    "database/sql"
    _ "github.com/go-sql-driver/mysql"
//    "time"
//    "math/rand"
    "os"
    "io/ioutil"
//     "flag"  //命令行选项解析器
     "strings"
//     "net/http"
     "regexp"
//     "errors"
     "sort"
     "strconv"
     "time"
// //    "log"
)

var mysqlDB *sql.DB;

func isStock(code string) (ret bool){
	code = strings.Replace(code, "sh", "", -1)
	code = strings.Replace(code, "sz", "", -1)
	reg0 := regexp.MustCompile("^0")
	reg6 := regexp.MustCompile("^6")
	if reg0.MatchString(code) || reg6.MatchString(code) {
    	return true            
    }
    return false
}

//func getStockData(code string) (ret map[string] []string,length int,err error) {
//func getStockData(db *sql.DB,code string) (ret map[string] map[string]string,err error){
func getStockData(code string) (ret map[string] map[string]string,days []string,err error){
	dbRet,err := query(code)
	stockData := make(map[string] map[string]string)
    var sortKeys []string
	//mapCreated["key1"] = "111"
	for _,data := range(dbRet) {
		
		//break
		day := data[0]
		if len(day) > 0 {
			key := data[1]
			value := data[2]
			
			_,ok := stockData[day]
			if ok {
	            stockData[day][key] = value
			} else {
				mapCreated := make(map[string]string)
				mapCreated[key] = value
				stockData[day] = mapCreated
				//fmt.Println("Did not find person with ID 1234.")
			}
			stockData[day]["time"] = day			
		}

	}
	for day,_ := range(stockData) {
		if len(day) >0 {
			sortKeys = append(sortKeys,day)
		}
	}

	sort.Strings(sortKeys)
   
    //fmt.Println("ret = ",sortKeys)
    return stockData,sortKeys,nil
	//return
}

func getMarketTimeList() (list []string,err error) {
	_,sortKeys,err := getStockData("sh000001")
	if err != nil {
        fmt.Println("get getMarketTimeList of sh000001 failed")
        return
    }
    fmt.Println("list is ", sortKeys)
	return sortKeys,nil
}

func getAllStockList() (stockList []string,err error) {
    stockListFile := "data/stock_list"
    buf, err := ioutil.ReadFile(stockListFile)
    if err != nil {
        fmt.Fprintf(os.Stderr, "File Error: %s\n", err)
        return
    }
    str := string(buf)
    codeArr := strings.Split(str, "\n")

    for _,code := range(codeArr) {
        //tmpcode := code
        //tmpcode = strings.Replace(tmpcode, "sh", "", -1)
        //tmpcode = strings.Replace(tmpcode, "sz", "", -1)
        if isStock(code) {
        	stockList = append(stockList,code)
        }
        if len(stockList) > 100 {
            break
        }
        //fmt.Println("Split: ", code)
    }
    if len(stockList)==0 {
    	fmt.Println("get stockList failed")
    	return
    }
    //fmt.Println("stockList: ", stockList,len(stockList))
    return stockList,nil
}

func dbInit() (db *sql.DB,err error) {
    db, err = sql.Open("mysql", "dog:123@tcp(127.0.0.1:3306)/test?charset=utf8")
    if err != nil {
        fmt.Println("Open database error: %s\n", err)
        return
    }
    err = db.Ping()
    if err != nil {
    	fmt.Println("Connect database error: %s\n", err)
    	return
    }
    return db,nil
}

func query(code string) (ret [][]string,err error){
	sql := "select * from stock_data where stock='"+code+"'"
    rows, err := mysqlDB.Query(sql)
    if err != nil {
    	fmt.Println("Query error: %s\n", err,sql)
    }
    defer rows.Close()
    var stock,day,name,value,updateTime string
    for rows.Next() {
    	var temp []string
        err := rows.Scan(&stock,&day,&name,&value,&updateTime)
        if err != nil {
        	fmt.Println("Query error: %s\n", err)
        }
        temp = append(temp,day)
        temp = append(temp,name)
        temp = append(temp,value)
        ret = append(ret,temp)
        //fmt.Println(temp)
    }
    return ret,nil
}

func checkStock(code string,time string,keepDays int) (ret map[string]string,errInfo error) {

	if !isStock(code) {
		//fmt.Println( "code is not a correct code, skip it",code)
		return
	}

	stockData,days,err := getStockData(code)
	if err != nil {
    	//fmt.Println("getStockData failed , code = ", code)
        return
    }
    //fmt.Println("days  ", stockData)
	// 判断是否有这一天的数据
	first,ok := stockData[time]
	if !ok || len(days)==0 {
        //没有这天的数据
        //fmt.Println("no data of time is "+time+", code = "+code+" , skip it")
	    return
	}

	//获取第一天的开盘价
    // 开盘价
    startPrice,_ := strconv.ParseFloat(first["opening_price"],64)
    // // 当天收盘价
    firstEndPrice,_ := strconv.ParseFloat(first["closing_price"],64)
    firstChangePercent,_ := strconv.ParseFloat(first["change_percent"],64)
    // // 当天最高价 
    // maxPrice,_ := strconv.ParseFloat(first['max_price'],64)
    // // 当天最低价 
    // minPrice,_ := strconv.ParseFloat(first['min_price'],64)
    //startPrice = 0
    if !(startPrice>=3&&startPrice<=200) {
        //fmt.Println("time is ["+ time +"], code = [" + code + "] startPrice=["+first["opening_price"]+"] is not ok, skip it!")
        return
    }

    yesterdayClosingPrice := firstEndPrice/(1+firstChangePercent/100)
	startPercent := 100*(startPrice-yesterdayClosingPrice)/yesterdayClosingPrice
	//fmt.Println("startPrice is too high,startPrice , yesterdayClosingPrice , startPercent skip it",startPrice,yesterdayClosingPrice,startPercent);
    if startPercent>=8 {
    	//fmt.Println("startPrice is too high,code ,startPrice, yesterdayClosingPrice, startPercent, skip it",code,startPrice,yesterdayClosingPrice,startPercent)
        return
    }

    stopLossPercent := -9.9
    stopWinPercent := 100.0
    //最后一天的时间
    lastDay := ""
    //止损、止盈的时间
    sellDay := ""
    volPercent := 0.0
    maxPercent := 0.0
    minPercent := 0.0
    index := 0
    result := make(map[string]string)
    for _,dayTime := range(days) {
    	if dayTime > time {
            index++
            if index >= keepDays {
                break
            }
            lastDay = dayTime
            sellDay = dayTime
            // 开盘价
            _startPrice,_ := strconv.ParseFloat(stockData[time]["opening_price"],64)
            // // 当天收盘价
            _endPrice,_ := strconv.ParseFloat(stockData[dayTime]["closing_price"],64)
            // 当天最高价 
            _maxPrice,_ := strconv.ParseFloat(stockData[dayTime]["max_price"],64)
            // 当天最低价 
            _minPrice,_ := strconv.ParseFloat(stockData[dayTime]["min_price"],64)
            // //计算收益

            volPercent = 100*(_endPrice - _startPrice)/_startPrice;
            maxPercent = 100*(_maxPrice - _startPrice)/_startPrice;
            minPercent = 100*(_minPrice - _startPrice)/_startPrice;
            //volPercentStr = strconv.FormatFloat(volPercent, 'f', -1, 64)
            //fmt.Println("startPrice,endPrice,volPercent:",_startPrice, _endPrice,volPercent)
            //止损
            if minPercent < stopLossPercent {
                volPercent = stopLossPercent
                //fmt.Println("stop to loss!")
                break
            }
            //止盈
            if maxPercent >= stopWinPercent {
                volPercent = stopWinPercent
                //fmt.Println("stop to win!")
                break
            }
    	}
    }
    result["firstDay"] = time
    result["lastDay"] = lastDay
    result["sellDay"] = sellDay
    result["volPercent"] = strconv.FormatFloat(volPercent, 'f', -1, 64)
    //fmt.Println("result: ", result)
	return result,nil
}


//输入股票列表，计算每天的平均收益
func computeAverageIncomeByStrategy(stockList map[string] []string,keepDays int) (ret map[string] float64,err error) {
    time1 := time.Now()
	var result map[string] []float64
	result = make(map[string] []float64)
	for dayTime,list := range(stockList) {
		//result[dayTime] = make([]float64)
		for _,stock := range(list) {
			ret,err := checkStock(stock,dayTime,keepDays)
			if err == nil {
				percent,_ := strconv.ParseFloat(ret["volPercent"],64)
				result[dayTime] = append(result[dayTime],percent)
		    	//fmt.Println("getStockData failed , code = ", code)
		    }
		}
	}
	var averageResult map[string] float64
	averageResult = make(map[string] float64)
	cnt := 0
	for dayTime,percentList := range(result) {
		sum := 0.0
		length := len(percentList)
		if length > 0 {
			for _,percent := range(percentList) {
				sum += percent
			}
			averageResult[dayTime] = sum/float64(length)
		}
		cnt++
	}
	if cnt==0 {
		fmt.Println("error! get data is null,result = ", result)
		return
	}
	fmt.Println("computeAverageIncomeByStrategy result,averageResult is  ", result,averageResult)
    //time.Sleep(1e9)
    time2 := time.Now()
    fmt.Println("run time is ",time2.Sub(time1).Seconds())
	return averageResult,nil
}

func computeAverageIncomeByStrategyMap(stockList map[string] []string,keepDays int) {
    averageResult,_ := computeAverageIncomeByStrategy(stockList,keepDays)
    reduce <- averageResult
}

var reduce chan map[string] float64

func chanTest() {
    var stockList map[string] []string
    stockList = make(map[string] []string)
    //allList,_ := getAllStockList()
    allList,_ := getAllStockList()
    // stockList["20150521"] = []string{"sz002577","sz000019"}
    // stockList["20150522"] = []string{"sz002577","sz000019"}
    // stockList["20150116"] = []string{"sz000019"}
    stockList["20150521"] = allList
    stockList["20150522"] = allList
    stockList["20150116"] = allList
    time1 := time.Now()
    //time.Sleep(3*time.Second);
    

    fmt.Println("now time is : ", time.Now().Unix())
    for dayTime,value := range(stockList) {
        var tmpStockList map[string] []string
        tmpStockList = make(map[string] []string)
        tmpStockList[dayTime] = value
        go computeAverageIncomeByStrategyMap(tmpStockList,10)
    }

    //stockList["20150522"] = allList
    // go computeAverageIncomeByStrategyMap(stockList,10)
    // go computeAverageIncomeByStrategyMap(stockList,10)
    // fmt.Println("wait ......")
    for dayTime,_ := range(stockList) {
        <- reduce
        fmt.Println("dayTime = ",dayTime)
    }

    
    //fmt.Println("now time is : ", time.Now().Unix())
    time2 := time.Now()
    fmt.Println("time is ",time2.Sub(time1).Seconds())
    for dayTime,value := range(stockList) {
        var tmpStockList map[string] []string
        tmpStockList = make(map[string] []string)
        tmpStockList[dayTime] = value
        computeAverageIncomeByStrategy(tmpStockList,10)
    }
    //computeAverageIncomeByStrategy(stockList,10)
    time3 := time.Now()
    //var dur_time time.Duration = time2.Sub(time1)
    //var elapsed_sec float64 = dur_time.Seconds()
    fmt.Println("time is ",time3.Sub(time2).Seconds())
}

func main() {
    var err error
    // code := "sh000001"
    // getStockData(code)

    mysqlDB,err=dbInit()
    defer mysqlDB.Close()
    if err != nil {
        fmt.Println( "db init failed ",err)
    }
    //code := "sz002577"
    //dbRet,err := query(code)
    //stockData,days,err := getStockData(code)
    //fmt.Println( "dbRet =  ",stockData,days,err)
    //checkStock(code,"20150129",10)
    //ret,_ := checkStock(code,"20150522",10)
    

    reduce = make(chan map[string] float64)

    chanTest()
    //fmt.Println("now time is : ", time.Now().Unix())
    //computeAverageIncomeByStrategy(stockList1,10)
    // getMarketTimeList()
    // getAllStockList()
    //query("sh000001")
    //getStockData("sh000001")
    // ret1 := isStock("sh000001")
    // fmt.Println( "ret1 =  ",ret1)

    // ret2 := isStock("sh300001")
    // fmt.Println( "ret2 =  ",ret2)
    // rows, err := db.Query("select id,name,ts from t")
    // if err != nil {
    // 	fmt.Println("Query error: %s\n", err)
    //     //log.Println(err)
    // }
    // defer rows.Close()
    // var id int
    // var name string
    // var ts string
    // for rows.Next() {
    //     err := rows.Scan(&id, &name,&ts)
    //     if err != nil {
    //     	fmt.Println("Query error: %s\n", err)
    //     }
    //     fmt.Println(id, name,ts)
    // }
    //
}



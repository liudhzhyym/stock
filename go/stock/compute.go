package main

import (
    "fmt"
    "database/sql"
    _ "github.com/go-sql-driver/mysql"
    "github.com/bitly/go-simplejson"
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
    "log"
    "errors"
)

// type Stock struct {  
//     adx float64
//     adxr float64
//     change float64
//     change_percent float64
//     closing_price float64
//     d float64
//     dea float64
//     di1 float64
//     di2 float64
//     diff float64
//     j float64
//     k float64
//     lower float64
//     macd float64
//     max_price float64
//     ma_10 float64
//     ma_20 float64
//     ma_5 float64
//     mid float64
//     min_price float64
//     obv float64
//     opening_price float64
//     rsi_12 float64
//     rsi_24 float64
//     rsi_6 float64
//     upper float64
//     volume float64
//     volume_ma_10 float64
//     volume_ma_20 float64
//     volume_ma_5 float64
//     wr1 float64
//     wr2 float64
// }

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
    fmt.Println("getStockData is ",code)
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

func getStockDataNew(code string) (ret map[string] map[string]float64,days []string,err error){
    // day => array('open_price'=>1.234)
    stockData := allStockData[code]
    var sortKeys []string
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
    //fmt.Println("list is ", sortKeys)
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
        if len(stockList) > 10000 {
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
    //fmt.Println("rows =  ", sql,rows)
    if err != nil {
    	fmt.Println("Query error: %s\n", err,sql)
    }
    defer rows.Close()
    var stock,day,name,value,updateTime string
    for rows.Next() {
    	var temp []string
        err := rows.Scan(&stock,&day,&name,&value,&updateTime)
        //fmt.Println("stock =  ",stock,day,name)
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

func queryNewStock(code string) (ret string){
    sql := "select * from new_stock_data where stock='"+code+"'"
    rows, err := mysqlDB.Query(sql)
    //fmt.Println("rows =  ", sql,rows)
    if err != nil {
        fmt.Println("Query error: %s\n", err,sql)
        return
    }
    defer rows.Close()
    var stock,result,updateTime string
    for rows.Next() {
        err := rows.Scan(&stock,&result,&updateTime)
        //fmt.Println("stock =  ",stock,day,name)
        if err != nil {
            fmt.Println("Query error: %s\n", err)
            return
        }
        //return result
        //fmt.Println(temp)
    }
    if len(result) == 0 {
        //fmt.Println("get result is null")
        return
    }
    return result
}

func getAllStockData() (ret map[string] map[string] map[string]float64){
    //code := "sh600191"
    var stockData map[string] map[string] map[string]float64
    stockData = make(map[string] map[string] map[string]float64)
    allList,_ := getAllStockList()
    timeList,_ := getMarketTimeList()
    allList = []string{"sz002577","sz000019","sh600191"}
    for _,code := range(allList) {
        result := queryNewStock(code)
        if len(result) == 0 {
            //fmt.Println("get result is null")
            continue
        }
        //fmt.Println( "result =  ",result,err)
        

        js, err := simplejson.NewJson([]byte(result))
        if err != nil {
            fmt.Println("json format error")
            //panic("json format error")
        }

        stockData[code] = make(map[string] map[string]float64)  
        //fmt.Println( "timeList =  ",timeList)
        for _,dayTime := range(timeList) {
            var dayData map[string]float64
            dayData = make(map[string]float64)
            s, err := js.Get(dayTime).Map()
            if err != nil {
                //fmt.Println("no data of ",dayTime)
                continue
                //return
            }
            for k, v := range(s) {
                value,_ := strconv.ParseFloat(v.(string),64)
                dayData[k] = value
                //fmt.Println("kv is ",k,v.(string),dayData)
                // var iv int
                // switch v.(type) {
                // case float64:
                //     iv = int(v.(float64))
                //     fmt.Println(iv)
                // case string:
                //     iv, _ = strconv.Atoi(v.(string))
                //     fmt.Println(iv)
                // }
            }
            //fmt.Println("dayData",dayTime,dayData)
            stockData[code][dayTime] = dayData
            //fmt.Println( "data =  ",s,err)
        }
        //break
    }
    for code,data := range(stockData) {
        log.Printf("Runtime error caught: %s,len = %d", code,len(data))

        //fmt.Println( "code , len =",code,len(data))
    }
    return stockData
    // for stock,_ := range(stockData) {
    //     fmt.Println( "dayTime =  ",stock)
    // }
    //fmt.Println( "stockData =  ",stockData["sh600191"]["20140225"])
}

func checkStock(code string,day string,keepDays int) (ret map[string]string,errInfo error) {

	if !isStock(code) {
		//fmt.Println( "code is not a correct code, skip it",code)
		return nil,errors.New("code is not a correct code, skip it :"+code)
	}
    time1 := time.Now()
	//stockData,days,err := getStockData(code)
    stockData,days,err := getStockDataNew(code)
    time2 := time.Now()
    fmt.Println("getStockData time is ",time2.Sub(time1).Seconds())
	if err != nil {
    	//fmt.Println("getStockData failed , code = ", code)
        return nil,errors.New("getStockData failed , code = " + code)
    }
    //fmt.Println("days  ", stockData)
	// 判断是否有这一天的数据
	first,ok := stockData[day]
	if !ok || len(days)==0 {
        //没有这天的数据
        //fmt.Println("no data of time is "+time+", code = "+code+" , skip it")
        return nil,errors.New("no data of time is ["+day+"], code = ["+code+"], skip it")
	}

	//获取第一天的开盘价
    // 开盘价
    startPrice,_ := first["opening_price"]
    // // 当天收盘价
    firstEndPrice,_ := first["closing_price"]
    firstChangePercent,_ := first["change_percent"]
    // startPrice,_ := strconv.ParseFloat(first["opening_price"],64)
    // // // 当天收盘价
    // firstEndPrice,_ := strconv.ParseFloat(first["closing_price"],64)
    // firstChangePercent,_ := strconv.ParseFloat(first["change_percent"],64)
    // // 当天最高价 
    // maxPrice,_ := strconv.ParseFloat(first['max_price'],64)
    // // 当天最低价 
    // minPrice,_ := strconv.ParseFloat(first['min_price'],64)
    //startPrice = 0
    if !(startPrice>=3&&startPrice<=200) {
        //fmt.Println("time is ["+ time +"], code = [" + code + "] startPrice=["+first["opening_price"]+"] is not ok, skip it!")
        return nil,errors.New("time is ["+ day +"], code = [" + code + "] startPrice is not ok, skip it!")
    }

    yesterdayClosingPrice := firstEndPrice/(1+firstChangePercent/100)
	startPercent := 100*(startPrice-yesterdayClosingPrice)/yesterdayClosingPrice
	fmt.Println("startPrice is too high,startPrice , yesterdayClosingPrice , startPercent:",startPrice,yesterdayClosingPrice,startPercent);
    if startPercent>=8 {
    	//fmt.Println("startPrice is too high,code ,startPrice, yesterdayClosingPrice, startPercent, skip it",code,startPrice,yesterdayClosingPrice,startPercent)
        return nil,errors.New("startPrice is too high, skip it")
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
    	if dayTime > day {
            index++
            if index >= keepDays {
                break
            }
            lastDay = dayTime
            sellDay = dayTime
            // 开盘价
            _startPrice,_ := stockData[day]["opening_price"]
            // // 当天收盘价
            _endPrice,_ := stockData[dayTime]["closing_price"]
            // 当天最高价 
            _maxPrice,_ := stockData[dayTime]["max_price"]
            // 当天最低价 
            _minPrice,_ := stockData[dayTime]["min_price"]
            
            // _startPrice,_ := strconv.ParseFloat(stockData[day]["opening_price"],64)
            // // // 当天收盘价
            // _endPrice,_ := strconv.ParseFloat(stockData[dayTime]["closing_price"],64)
            // // 当天最高价 
            // _maxPrice,_ := strconv.ParseFloat(stockData[dayTime]["max_price"],64)
            // // 当天最低价 
            // _minPrice,_ := strconv.ParseFloat(stockData[dayTime]["min_price"],64)

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
    result["code"] = code
    result["firstDay"] = day
    result["lastDay"] = lastDay
    result["sellDay"] = sellDay
    result["volPercent"] = strconv.FormatFloat(volPercent, 'f', -1, 64)
    fmt.Println("result: ", result)
    time3 := time.Now()
    fmt.Println("compute time is,total time is ",time3.Sub(time2).Seconds(),time3.Sub(time1).Seconds())
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
		//fmt.Println("error! get data is null,result = ", result)
		return nil,errors.New("error! get data is null")
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
    stockList["20150520"] = allList
    stockList["20150116"] = allList
    time1 := time.Now()
    //time.Sleep(3*time.Second);
    

    // fmt.Println("now time is : ", time.Now().Unix())
    // for dayTime,value := range(stockList) {
    //     var tmpStockList map[string] []string
    //     tmpStockList = make(map[string] []string)
    //     tmpStockList[dayTime] = value
    //     go computeAverageIncomeByStrategyMap(tmpStockList,10)
    // }

    //stockList["20150522"] = allList
    // go computeAverageIncomeByStrategyMap(stockList,10)
    // go computeAverageIncomeByStrategyMap(stockList,10)
    // fmt.Println("wait ......")
    // for dayTime,_ := range(stockList) {
    //     <- reduce
    //     fmt.Println("dayTime = ",dayTime)
    // }

    
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
    fmt.Println("time is,total time is ",time3.Sub(time2).Seconds(),time3.Sub(time1).Seconds())
}

var allStockData map[string] map[string] map[string]float64

func main() {
    var err error
    // code := "sh000001"
    // getStockData(code)

    mysqlDB,err=dbInit()
    defer mysqlDB.Close()
    if err != nil {
        fmt.Println( "db init failed ",err)
    }
    allStockData = getAllStockData()
    //fmt.Println("allStockData = ",allStockData)
    //glog.Info("hello, glog")
    //return
    //getStockDataNew("sz002577")
    var stockList map[string] []string
    stockList = make(map[string] []string)
    //allList,_ := getAllStockList()
    stockList["20150506"] = []string{"sz002577","sz000019","sh600191"}
    stockList["20150507"] = []string{"sz002577","sz000019","sh600191"}
    averageResult,_ := computeAverageIncomeByStrategy(stockList,10)
    // stockList["20150116"] = []string{"sz000019"}
    fmt.Println("averageResult = ",averageResult)
    ret,e := checkStock("sh300191","20150506",10)
    fmt.Println("ret = ",ret,e)
    //dbRet,err := query(code)
    // allList,_ := getAllStockList()
    // fmt.Println( "allList =  ",allList)
    // //var stockData map[string] map[string] map[string]string
    // //stockData = make(map[string] map[string] map[string]string)
    // time0 := time.Now()
    // for _,stock := range(allList) {
    //     //data,_,_ := getStockData(stock)
    //     time1 := time.Now()
    //     getStockData(stock)
        
    //     time2 := time.Now()
    //     fmt.Println("getStockData time is ",stock,time2.Sub(time0).Seconds(),time2.Sub(time1).Seconds())
    //     //stockData[stock] = data
    // }
    //fmt.Println( "dbRet =  ",len(stockData))
    // for i := 0; i < 100; i++ {
    //     ret,_ := checkStock("sz002577","20150129",10)
    //     fmt.Println("ret is : ", ret)
    //     //sum += i 
    // }
    
    //ret,_ := checkStock(code,"20150522",10)
    

    //reduce = make(chan map[string] float64)

    //chanTest()
    //fmt.Println("now time is : ", time.Now().Unix())
    //computeAverageIncomeByStrategy(stockList1,10)
    // getMarketTimeList()
    // getAllStockList()
    //query("sh000001")
    //getStockData("sh000001")
    //getStockData("sh600191")
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



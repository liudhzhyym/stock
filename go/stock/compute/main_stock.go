package main

import (
    _ "fmt"
    "database/sql"
    _ "github.com/go-sql-driver/mysql"
    "github.com/bitly/go-simplejson"
    "github.com/golang/glog"
    "strings"
    "regexp"
    "errors"
    "os"
    "io/ioutil"
    "sort"
    "strconv"
    "time"
    "io"
    "net/http"
    "flag"
    "encoding/json"
)

var mysqlDB *sql.DB;
var allStockData map[string] map[string] map[string]float64

func isStock(code string) (ret bool,err error){
	code = strings.Replace(code, "sh", "", -1)
	code = strings.Replace(code, "sz", "", -1)
	reg0 := regexp.MustCompile("^0")
	reg6 := regexp.MustCompile("^6")
	if reg0.MatchString(code) || reg6.MatchString(code) {
        return true,nil            
    }
    return false,errors.New("code ["+code+"] is not a correct code, skip it ")
}


func dbInit() (db *sql.DB,err error) {
    db, err = sql.Open("mysql", "dog:123@tcp(127.0.0.1:3306)/test?charset=utf8")
    if err != nil {
        glog.Errorf("Open database error: ", err)
        return
    }
    err = db.Ping()
    if err != nil {
        glog.Errorf("Connect database error: ", err)
        return
    }
    return db,nil
}

func getAllStockList(count int) (stockList []string,err error) {
    stockListFile := "../data/stock_list"
    buf, err := ioutil.ReadFile(stockListFile)
    if err != nil {
        //glog.Infof("start ")
        glog.Errorf("Open File Error: ",os.Stderr, err)
        return
    }
    str := string(buf)
    codeArr := strings.Split(str, "\n")

    for _,code := range(codeArr) {
        stockCheck,_ := isStock(code)
        if stockCheck {
            stockList = append(stockList,code)
        }
        if count>0 && len(stockList) >= count {
            break
        }
        //fmt.Println("Split: ", code)
    }
    if len(stockList)==0 {
        err = errors.New("get stockList is null")
        glog.Errorf("get stockList is null")
        return
    }
    //fmt.Println("stockList: ", stockList,len(stockList))
    return stockList,nil
}


func query(code string) (ret [][]string,err error){
    sql := "select * from stock_data where stock='"+code+"'"
    rows, err := mysqlDB.Query(sql)
    //fmt.Println("rows =  ", sql,rows)
    if err != nil {
        glog.Errorf("Query error: ", err,sql)
        return
    }
    defer rows.Close()
    var stock,day,name,value,updateTime string
    for rows.Next() {
        var temp []string
        err := rows.Scan(&stock,&day,&name,&value,&updateTime)
        //fmt.Println("stock =  ",stock,day,name)
        if err != nil {
            glog.Errorf("Query error: ", err)
        }
        temp = append(temp,day)
        temp = append(temp,name)
        temp = append(temp,value)
        ret = append(ret,temp)
        //fmt.Println(temp)
    }
    if len(ret) == 0 {
        glog.Warningf("get stockData is null")
        err = errors.New("get stockData is null")
        return
    }
    return ret,nil
}

func queryNewStock(code string) (ret string,err error){
    sql := "select * from new_stock_data where stock='"+code+"'"
    rows, err := mysqlDB.Query(sql)
    //fmt.Println("rows =  ", sql,rows)
    if err != nil {
        glog.Errorf("Query error: ", err,sql)
        return
    }
    defer rows.Close()
    var stock,result,updateTime string
    for rows.Next() {
        err = rows.Scan(&stock,&result,&updateTime)
        //fmt.Println("stock =  ",stock,day,name)
        if err != nil {
            glog.Errorf("Query error: ", err)
            return
        }
        //return result
        //fmt.Println(temp)
    }
    if len(result) == 0 {
        err = errors.New("get result is null")
        glog.Warningf("get result of [%s] is null",code)
        return
    }
    return result,nil
}


func getStockData(code string) (ret map[string] map[string]string,days []string,err error){
    //fmt.Println("getStockData is ",code)
    stockData := make(map[string] map[string]string)
    var sortKeys []string

    dbRet,err := query(code)
    if err!=nil {
        return
    }
    
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
    if len(sortKeys) == 0 {
        err = errors.New("get stock data is null")
        glog.Errorf("get stock data is null")
        return
    }
    sort.Strings(sortKeys)
    //fmt.Println("ret = ",sortKeys)
    return stockData,sortKeys,nil
    //return
}


func getMarketTimeList(code string) (list []string,err error) {
    _,sortKeys,err := getStockData(code)
    if err != nil {
        glog.Errorf("get getMarketTimeList of [%s] failed",code)
        return
    }
    //fmt.Println("list is ", sortKeys)
    return sortKeys,nil
}

func getAllStockData(stockList []string) (ret map[string] map[string] map[string]float64,err error){
    //code := "sh600191"
    var stockData map[string] map[string] map[string]float64
    stockData = make(map[string] map[string] map[string]float64)
    timeList,_ := getMarketTimeList("sh000001")
    if len(stockList) == 0 {
        stockList,err = getAllStockList(-1)
        if err!=nil {
            return
        }
    }
    //allList = []string{"sz002577","sz000019","sh600191"}
    for _,code := range(stockList) {
        result,err1 := queryNewStock(code)
        if err1!=nil {
            glog.Warningf("get result is null")
            continue
        }
        //fmt.Println( "result =  ",result,err)
        

        js, err1 := simplejson.NewJson([]byte(result))
        if err1 != nil {
            glog.Warningf("json format error",err1)
            //panic("json format error")
        }

        stockData[code] = make(map[string] map[string]float64)  
        //fmt.Println( "timeList =  ",timeList)
        for _,dayTime := range(timeList) {
            var dayData map[string]float64
            dayData = make(map[string]float64)
            s, err := js.Get(dayTime).Map()
            if err != nil {
                //glog.Warningf("no data of ",dayTime,err)
                continue
                //return
            }
            for k, v := range(s) {
                value,_ := strconv.ParseFloat(v.(string),64)
                dayData[k] = value
            }
            //fmt.Println("dayData",dayTime,dayData)
            stockData[code][dayTime] = dayData
            //fmt.Println( "data =  ",s,err)
        }
        glog.Infof(" get stockData is : %s,len = %d", code,len(stockData[code]))
        //break
    }
    if len(stockData) == 0 {
        err = errors.New("get stockData is null")
        glog.Errorf("get stockData is null")
        return
    }

    allStockData = stockData
    return stockData,nil
    // for stock,_ := range(stockData) {
    //     fmt.Println( "dayTime =  ",stock)
    // }
    //fmt.Println( "stockData =  ",stockData["sh600191"]["20140225"])
}

func getStockDataNew(code string) (ret map[string] map[string]float64,days []string,err error){
    stockData := allStockData[code]
    var sortKeys []string
    for day,_ := range(stockData) {
        if len(day) >0 {
            sortKeys = append(sortKeys,day)
        }
    }
    sort.Strings(sortKeys)
    if len(sortKeys) == 0 {
        err = errors.New("code ["+code+"] is null")
        glog.Errorf("getStockDataNew failed and err is ",err)
        return
    }
    //fmt.Println("ret = ",sortKeys)
    return stockData,sortKeys,nil
    //return
}

func checkStock(code string,day string,keepDays int) (ret map[string]string,errInfo error) {

    check,_ := isStock(code)
    if check!=true {
        glog.Warningf( "code is not a correct code, skip it",code)
        return nil,errors.New("code is not a correct code, skip it :"+code)
    }
    time1 := time.Now()
    //stockData,days,err := getStockData(code)
    stockData,days,err := getStockDataNew(code)
    time2 := time.Now()
    //fmt.Println("getStockData time is ",time2.Sub(time1).Seconds())
    if err != nil {
        glog.Warningf("getStockData failed , code = ", code)
        return nil,errors.New("getStockData failed , code = " + code)
    }
    //fmt.Println("days  ", stockData)
    // 判断是否有这一天的数据
    first,ok := stockData[day]
    if !ok || len(days)==0 {
        //没有这天的数据
        glog.Warningf("no data of time is "+day+", code = "+code+" , skip it")
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
        msg := "time is ["+ day +"], code = [" + code + "] startPrice is not ok, skip it!" 
        glog.Warningf(msg)
        return nil,errors.New(msg)
    }

    yesterdayClosingPrice := firstEndPrice/(1+firstChangePercent/100)
    startPercent := 100*(startPrice-yesterdayClosingPrice)/yesterdayClosingPrice
    //fmt.Println("startPrice is too high,startPrice , yesterdayClosingPrice , startPercent:",startPrice,yesterdayClosingPrice,startPercent);
    if startPercent>=8 {
        glog.Warningf("startPrice is too high,code ,startPrice, yesterdayClosingPrice, startPercent, skip it",code,startPrice,yesterdayClosingPrice,startPercent)
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
                glog.Infof("stop to loss!")
                break
            }
            //止盈
            if maxPercent >= stopWinPercent {
                volPercent = stopWinPercent
                glog.Infof("stop to win!")
                break
            }
        }
    }
    result["code"] = code
    result["firstDay"] = day
    result["lastDay"] = lastDay
    result["sellDay"] = sellDay
    result["volPercent"] = strconv.FormatFloat(volPercent, 'f', -1, 64)
    //fmt.Println("result: ", result)
    time3 := time.Now()
    glog.Infof("compute time is,total time is ",time3.Sub(time2).Seconds(),time3.Sub(time1).Seconds())
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
        glog.Warningf("error! get data is null,result = ", result)
        return nil,errors.New("error! get data is null")
    }
    //fmt.Println("computeAverageIncomeByStrategy result,averageResult is  ", result,averageResult)
    //time.Sleep(1e9)
    time2 := time.Now()
    glog.Infof("run time is ",time2.Sub(time1).Seconds())
    return averageResult,nil
}

type Income struct {
    Code   string    `json:"code"`
    FirstDay  string   `json:"firstDay"`
    LastDay   string    `json:"lastDay"`
    SellDay  string   `json:"sellDay"`
    VolPercent  string   `json:"volPercent"`
}

func helloHandler(w http.ResponseWriter, r *http.Request){
    //io.WriteString(w, "Hello, world!")
    r.ParseForm()  //解析参数，默认是不会解析的

    stock := r.Form["stock"][0]
    dayTime := r.Form["dayTime"][0]
    keepDaysStr := r.Form["keepDays"][0]
    keepDays,_ :=  strconv.Atoi(keepDaysStr)
    result,_ := checkStock(stock,dayTime,keepDays)
    glog.Infof("form data is ,result is ,",stock,dayTime,keepDays,result)  //这些信息是输出到服务器端的打印信息

    var incomeRet Income

    incomeRet.Code  = result["code"]
    incomeRet.FirstDay  = result["firstDay"]
    incomeRet.LastDay  = result["lastDay"]
    incomeRet.SellDay  = result["sellDay"]
    incomeRet.VolPercent  = result["volPercent"]

    body, err := json.Marshal(incomeRet)
    if err != nil {
        glog.Errorf("json encode failed ",err)
        return
    }
    ret := string(body)
    glog.Infof("body is ",ret)
    io.WriteString(w, ret)
}

func reloadData() {
    //allList := []string{"sz002577","sz002252","sh600191","sz002415"}
    allList := []string{}
    getAllStockData(allList) 
}

func reloadHandler(w http.ResponseWriter, r *http.Request){
    reloadData()
}

func httpServerInit() {
    //fmt.Println("all stock data is ,",allStockData)

    http.HandleFunc("/hello", helloHandler)
    http.HandleFunc("/reload", reloadHandler)
    err := http.ListenAndServe(":8085", nil)
    if err != nil {
        glog.Errorf("ListenAndServe: ", err.Error())
    }
    glog.Infof("allStockData length is : ", len(allStockData))
}

func httpClient(stock string,dayTime string,keepDays int) (ret string,err error) {
    url := "http://10.38.150.14:8085/hello?stock=" + stock + "&dayTime=" + dayTime + "&keepDays="+strconv.Itoa(keepDays);
    resp, err := http.Get(url)
    glog.Infof("httpClient url is, ret is ",url,resp)
    if err != nil {
        glog.Errorf("httpClient call failed: ", err.Error())
        // 处理错误 ...
        return
    }
    defer resp.Body.Close()
    body, err := ioutil.ReadAll(resp.Body)
    if err != nil {
        glog.Errorf("httpClient call failed,err is ",err)
        return
        // handle error
    }
    str := string(body)
    glog.Infof("httpClient url is, ret is ",url,str)
    return str,nil
}

func parseJson(jsonStr string) (ret map[string] string,err error) {
    //jsonStr := `{"host": "http://localhost:9090","port": 9090,"analytics_file": "","static_file_version": 1,"static_dir": "E:/Project/goTest/src/","templates_dir": "E:/Project/goTest/src/templates/","serTcpSocketHost": ":12340","serTcpSocketPort": 12340,"fruits": ["apple", "peach"]}`
    //json str 转map  
    var dat map[string] string 
    err = json.Unmarshal([]byte(jsonStr), &dat)
    if err != nil {  
        glog.Warningf("parseJson1 failed, err is ", err.Error())
        return
    } 
    glog.Infof("parseJson1 input is , and ret is ",jsonStr,dat)
    return dat,nil 
}


func end() {
    mysqlDB.Close()
}

func logFlush() {
    for {
        time.Sleep(1*time.Second);
        //glog.Infof("flush log")
        //fmt.Println("stockData is ")
        glog.Flush()
    }
    
}


func main() {
    flag.Parse()
    //setFlags()
    var err error
    glog.Infof("start ")
    mysqlDB,err=dbInit()
    go logFlush()
    //defer mysqlDB.Close()
    defer end()

    if err != nil {
        glog.Errorf( "db init failed ",err)
        return
    }

    reloadData()
    
    httpServerInit()
}


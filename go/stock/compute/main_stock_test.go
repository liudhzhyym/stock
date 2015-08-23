package main

import (
    "testing"
    "fmt"
    "strconv"
)

type stockTest struct {
    stock string
    ret bool
}


var stockCheckTest = []stockTest{
    stockTest{"sh000001",true},
    stockTest{"sh100001",false},
    stockTest{"sz000001",true},
    stockTest{"sz100001",false},
    stockTest{"000001",true},
    stockTest{"300001",false},
}

func TestNew(t *testing.T) {
    	//t.Errorf("aaaaaaaaaaa")
    for _,v := range stockCheckTest {
    	//t.Errorf("sssfsdfs%s",v)
        ret,_ := isStock(v.stock)
        if ret != v.ret {
            t.Errorf("%s check,want %s,but get %s",v.stock,v.ret,ret)
        }
    }
}

func TestGetAllStockList(t *testing.T) {
    list,err := getAllStockList(-1)
    cnt := len(list)
    expectCnt := 2396
    if err!=nil {
        t.Errorf("getAllStockList failed")
    }

    if expectCnt!=cnt {
        t.Errorf("getAllStockList check,want %d,but get %d",expectCnt,cnt)
    }

    expectCnt = 10
    list,err = getAllStockList(expectCnt)
    cnt = len(list)

    if expectCnt!=cnt {
        t.Errorf("getAllStockList check,want %d,but get %d",expectCnt,cnt)
    }
}



func TestMain(t *testing.T) {
    var err error

    mysqlDB,err=dbInit()
    defer mysqlDB.Close()
    if err != nil {
        t.Errorf( "db init failed ",err)
    }

    // 测试query
    codeGood := "sh000001"
    codeBad := "ss0001"
    stockData,ok := query(codeGood)
    cnt := len(stockData)
    expectCnt := 12959 
    if expectCnt!=cnt || ok !=nil {
        t.Errorf("query [%s] data failed,want %d,but get %d",codeGood,expectCnt,cnt)
    }

    stockData,ok = query(codeBad) 
    if ok == nil {
        t.Errorf("query [%s] data failed,want %d,but get %d",codeBad,"err",ok)
    }

    //测试getStockData
    stockData1,days1,err1 := getStockData(codeGood)
    if err1 != nil {
        t.Errorf("getStockData [%s] stock data failed,want %s,but get %s",codeGood,nil,err1)
    }

    stockCnt := len(stockData1)
    stockCntExpect := 393
    if stockCntExpect!=stockCnt {
        t.Errorf("getStockData [%s] stock data failed,want %d,but get %d",codeGood,stockCntExpect,stockCnt)
    }

    firstDay := days1[0]
    dayExpect := "20140102"
    if firstDay!=dayExpect {
        t.Errorf("getStockData [%s] days data failed,want %d,but get %d",codeGood,dayExpect,firstDay)
    }

    // 测试getStockData false
    
    _,_,err2 := getStockData(codeBad)
    if err2 == nil {
        t.Errorf("getStockData [%s] stock data failed,want %s,but get %s",codeBad,nil,err2)
    }

    // 测试getMarketTimeList
    daysList,ok := getMarketTimeList(codeGood)
    daysCnt := len(daysList)
    daysExpect := 393
    if daysCnt!=daysExpect || ok!=nil {
        t.Errorf("getMarketTimeList [%s] failed,want %d,but get %d,ok is [%s]",codeGood,daysExpect,daysCnt,ok)
    }

    daysList,ok = getMarketTimeList(codeBad)
    daysCnt = len(daysList)
    daysExpect = 0
    if daysCnt!=0 || ok==nil {
        t.Errorf("getMarketTimeList [%s] failed,want %d,but get %d,ok is [%s]",codeBad,daysExpect,daysCnt,ok)
    }

}


func TestQueryNewStock(t *testing.T) { 
    var err error

    mysqlDB,err=dbInit()
    defer mysqlDB.Close()
    if err != nil {
        t.Errorf( "db init failed ",err)
    }
    // 测试query
    codeGood := "sh000001"
    codeBad := "ss0001"
    stockData,ok := queryNewStock(codeGood)
    //fmt.Println("stockData is ", stockData[0:10])
    expectStr := "{\"20140102";
    str :=  stockData[0:10]
    if str!=expectStr {
        fmt.Println("stockData is ", stockData[0:10])
        t.Errorf("queryNewStock [%s] data failed,want %s,but get %s",codeGood,expectStr,str)
    }

    stockData,ok = queryNewStock(codeBad) 
    if ok == nil {
        t.Errorf("query [%s] data failed,want %s,but get %s",codeBad,"err",ok)
    }

}

func TestGetAllStockData(t *testing.T) { 
    var err error

    mysqlDB,err=dbInit()
    defer mysqlDB.Close()
    if err != nil {
        t.Errorf( "db init failed ",err)
    }

    allList := []string{}
    allStockData,ok := getAllStockData(allList)
    expectCnt := 2306
    cnt := len(allStockData)
    if cnt!=expectCnt {
        //fmt.Println("stockData is ", stockData[0:10])
        t.Errorf("getAllStockData data failed,want %d,but get %d",expectCnt,cnt)
    }

    // bad
    allList = []string{"sz0025771","sz0022521","ss6001911"}
    allStockData,ok = getAllStockData(allList)
    //fmt.Println("stockData is ", stockData)
    if ok == nil {
        t.Errorf("getAllStockData data failed,want %s,but get %s","err",ok)
    }

    allList = []string{"sz002577","sz002252","sh600191"}
    allStockData,_ = getAllStockData(allList)
    //fmt.Println("stockData is ", allStockData)
    expectCnt = 3
    cnt = len(allStockData)
    if cnt!=expectCnt {
        //fmt.Println("stockData is ", stockData[0:10])
        t.Errorf("getAllStockData data failed,want %d,but get %d",expectCnt,cnt)
    }

    codeGood := "sz002577"
    codeBad := "ss0001" 
    _,_,err = getStockDataNew(codeBad) 
    if err == nil {
        t.Errorf("queryNewStock [%s] data failed,want %s,but get %s",codeBad,"err",err)
    }

    stockData,_,err1 := getStockDataNew(codeGood) 
    //fmt.Println("stockData is ", stockData["20140102"])

    if err1 != nil {
        t.Errorf("getStockDataNew [%s] data failed,want %s,but get %s",codeGood,nil,err1)
    }
    value := stockData["20140102"]["max_price"]
    expectValue := 16.97
    if value != expectValue {
        t.Errorf("queryNewStock [%s] data failed,want [%f],but get [%f]",codeGood,expectValue,value)
    }
}

func abs(a float64) (ret float64){
    if a>=0.0 {
        return a
    } else {
        return -a
    }
}


func TestCheckStock(t *testing.T) { 
    var err error

    mysqlDB,err=dbInit()
    defer mysqlDB.Close()
    if err != nil {
        t.Errorf( "db init failed ",err)
    }


    allList := []string{"sz002577","sz002252","sh600191","sz002415"}
    allStockData,_ = getAllStockData(allList)
    //fmt.Println("stockData is ", allStockData)
    expectCnt := 4
    cnt := len(allStockData)
    if cnt!=expectCnt {
        //fmt.Println("stockData is ", stockData[0:10])
        t.Errorf("getAllStockData data failed,want %d,but get %d",expectCnt,cnt)
    }

    codeGood := "sz002252"

    result,err1 := checkStock(codeGood,"20150629",10) 
    fmt.Println("stockData is ", result)

    if err1 != nil {
        t.Errorf("checkStock [%s] data failed,want %s,but get %s",codeGood,nil,err1)
    }

    value,_ := strconv.ParseFloat(result["volPercent"],64)
    expectValue := 36.9
    //fmt.Println("stockData is ", value-expectValue,abs(value-expectValue))
    if abs(value-expectValue)>0.1 {
        t.Errorf("queryNewStock [%s] data failed,want [%f],but get [%f]",codeGood,expectValue,value)
    }

    codeGood = "sz002415"

    result,err1 = checkStock(codeGood,"20150625",10) 
    fmt.Println("stockData is ", result)

    if err1 != nil {
        t.Errorf("checkStock [%s] data failed,want %s,but get %s",codeGood,nil,err1)
    }

    value,_ = strconv.ParseFloat(result["volPercent"],64)
    expectValue = -10.0
    //fmt.Println("stockData is ", value-expectValue,abs(value-expectValue))
    if abs(value-expectValue)>0.1 {
        t.Errorf("queryNewStock [%s] data failed,want [%f],but get [%f]",codeGood,expectValue,value)
    }

    var stockList map[string] []string
    stockList = make(map[string] []string)
    stockList["20150629"] = []string{"sz002252","sz300019"}
    stockList["20150625"] = []string{"sz002415","sz002252"}

    ret,_ := computeAverageIncomeByStrategy(stockList,10)
    fmt.Println("ret is ", ret)
    if abs(ret["20150625"]-2.422)>0.1 || abs(ret["20150629"]-36.884)>0.1 {
        t.Errorf("computeAverageIncomeByStrategy check failed")
    }

    
}


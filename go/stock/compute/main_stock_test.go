package main

import (
    "testing"
    "fmt"
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

    // bad
    allList := []string{"sz0025771","sz0000191","ss6001911"}
    allStockData,ok := getAllStockData(allList)
    //fmt.Println("stockData is ", stockData)
    if ok == nil {
        t.Errorf("getAllStockData data failed,want %s,but get %s","err",ok)
    }

    allList = []string{"sz002577","sz000019","sh600191"}
    allStockData,_ = getAllStockData(allList)
    //fmt.Println("stockData is ", allStockData)
    expectCnt := 3
    cnt := len(allStockData)
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




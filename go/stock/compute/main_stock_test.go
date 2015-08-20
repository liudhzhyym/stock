package main

import (
    "testing"
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
    list,err := getAllStockList()
    cnt := len(list)
    expectCnt := 2396
    if err!=nil {
        t.Errorf("getAllStockList failed")
    }

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
    code := "sh000001"
    stockData,_ := query(code)
    //stockData1,_ := query(code)
    cnt := len(stockData)
    expectCnt := 12959 
    if expectCnt!=cnt {
        t.Errorf("query [%s] data failed,want %d,but get %d",code,expectCnt,cnt)
    }

    //测试getStockData
    stockData1,days1,err1 := getStockData(code)
    if err1 != nil {
        t.Errorf("getStockData [%s] stock data failed,want %s,but get %s",code,nil,err1)
    }

    stockCnt := len(stockData1)
    stockCntExpect := 393
    if stockCntExpect!=stockCnt {
        t.Errorf("getStockData [%s] stock data failed,want %d,but get %d",code,stockCntExpect,stockCnt)
    }

    firstDay := days1[0]
    dayExpect := "20140102"
    if firstDay!=dayExpect {
        t.Errorf("getStockData [%s] days data failed,want %d,but get %d",code,dayExpect,firstDay)
    }

    // 测试getStockData false
    code2 := "ss0001"
    _,_,err2 := getStockData(code2)
    if err2 == nil {
        t.Errorf("getStockData [%s] stock data failed,want %s,but get %s",code2,nil,err2)
    }

    // 测试getMarketTimeList
    daysList,ok := getMarketTimeList("sh000001")
    daysCnt := len(daysList)
    daysExpect := 393
    if daysCnt!=daysExpect || ok!=nil {
        t.Errorf("getMarketTimeList [%s] failed,want %d,but get %d,ok is [%s]",code,daysExpect,daysCnt,ok)
    }

    daysList,ok = getMarketTimeList("")
    daysCnt = len(daysList)
    daysExpect = 0
    if daysCnt!=0 || ok==nil {
        t.Errorf("getMarketTimeList [%s] failed,want %d,but get %d,ok is [%s]",code,daysExpect,daysCnt,ok)
    }
}





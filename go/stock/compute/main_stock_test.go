package main

import (
    "testing"
    "fmt"
    "strconv"
    "github.com/stretchr/testify/assert"
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
        assert.Equal(t, ret, v.ret, "ret should be equal")
    }
}

func TestGetAllStockList(t *testing.T) {
    list,err := getAllStockList(-1)
    cnt := len(list)
    expectCnt := 2396
    assert.Nil(t,err,"getAllStockList ret should be nil")
    assert.Equal(t, cnt, expectCnt, "cnt should be equal")

    expectCnt = 10
    list,err = getAllStockList(expectCnt)
    cnt = len(list)
    assert.Equal(t, cnt, expectCnt, "cnt should be equal")
}



func TestMain(t *testing.T) {
    var err error

    mysqlDB,err=dbInit()
    defer mysqlDB.Close()
    assert.Nil(t,err,"dbInit ret should be nil")

    // 测试query
    codeGood := "sh000001"
    codeBad := "ss0001"
    stockData,ok := query(codeGood)
    cnt := len(stockData)
    expectCnt := 12959 
    assert.Equal(t, cnt, expectCnt, "cnt should be equal")

    stockData,ok = query(codeBad) 
    assert.NotNil(t,ok,"ret should not be nil")

    //测试getStockData
    stockData1,days1,err1 := getStockData(codeGood)
    assert.Nil(t,err1,"ret should be nil")

    stockCnt := len(stockData1)
    stockCntExpect := 393
    assert.Equal(t, stockCnt, stockCntExpect, "days should be equal")

    firstDay := days1[0]
    dayExpect := "20140102"
    assert.Equal(t, firstDay, dayExpect, "firstDay should be equal")

    // 测试getStockData false
    
    _,_,err2 := getStockData(codeBad)
    assert.NotNil(t,err2,"ret should not be nil")

    // 测试getMarketTimeList
    daysList,ok := getMarketTimeList(codeGood)
    daysCnt := len(daysList)
    daysExpect := 393
    assert.Equal(t, daysCnt, daysExpect, "days should be equal")
    assert.Nil(t,ok,"ret should be nil")

    daysList,ok = getMarketTimeList(codeBad)
    daysCnt = len(daysList)
    daysExpect = 0
    assert.Equal(t, daysCnt, daysExpect, "days should be equal")
    assert.NotNil(t,ok,"ret should not be nil")
}


func TestQueryNewStock(t *testing.T) { 
    var err error

    mysqlDB,err=dbInit()
    defer mysqlDB.Close()
    assert.Nil(t,err,"dbInit ret should be nil")

    // 测试query
    codeGood := "sh000001"
    codeBad := "ss0001"
    stockData,ok := queryNewStock(codeGood)
    fmt.Println("stockData is ", stockData[0:10])
    expectStr := "{\"20140102";
    str :=  stockData[0:10]
    assert.Equal(t, str, expectStr, "str should be equal")

    stockData,ok = queryNewStock(codeBad) 
    assert.NotNil(t,ok,"ret should not be nil")

}

func TestGetAllStockData(t *testing.T) { 
    var err error

    mysqlDB,err=dbInit()
    defer mysqlDB.Close()
    assert.Nil(t,err,"dbInit ret should be nil")

    //allList := []string{}
    allList := []string{"sz0025771","sz0022521","ss6001911"}
    allStockData,ok := getAllStockData(allList)
    expectCnt := 0
    cnt := len(allStockData)
    assert.Equal(t, cnt, expectCnt, "cnt should be equal")

    // bad
    allList = []string{"sz0025771","sz0022521","ss6001911"}
    allStockData,ok = getAllStockData(allList)
    //fmt.Println("stockData is ", stockData)
    assert.NotNil(t,ok,"ret should not be nil")

    allList = []string{"sz002577","sz002252","sh600191"}
    allStockData,_ = getAllStockData(allList)
    //fmt.Println("stockData is ", allStockData)
    expectCnt = 3
    cnt = len(allStockData)
    assert.Equal(t, cnt, expectCnt, "cnt should be equal")

    codeGood := "sz002577"
    codeBad := "ss0001" 
    _,_,err = getStockDataNew(codeBad) 
    assert.NotNil(t,err,"ret should not be nil")

    stockData,_,err1 := getStockDataNew(codeGood) 
    //fmt.Println("stockData is ", stockData["20140102"])
    assert.Nil(t,err1,"ret should be nil")
    value := stockData["20140102"]["max_price"]
    expectValue := 16.97
    assert.Equal(t, value, expectValue, "queryNewStock should be equal")
}

func TestCheckStock(t *testing.T) { 
    var err error

    mysqlDB,err=dbInit()
    defer mysqlDB.Close()
    assert.Nil(t,err,"db init ret should be nil")

    allList := []string{"sz002577","sz002252","sh600191","sz002415"}
    allStockData,_ = getAllStockData(allList)
    //fmt.Println("stockData is ", allStockData)
    expectCnt := 4
    cnt := len(allStockData)
    assert.Equal(t, cnt, expectCnt, "they should be equal")

    codeGood := "sz002252"

    result,err1 := checkStock(codeGood,"20150629",10) 
    //fmt.Println("stockData is ", result)

    assert.Nil(t,err1,"checkStock should be nil")

    value,_ := strconv.ParseFloat(result["volPercent"],64)
    expectValue := 36.9
    //fmt.Println("stockData is ", value-expectValue,abs(value-expectValue))
    assert.True(t, assert.InDelta(t,value,expectValue,0.1,"queryNewStock failed"))

    codeGood = "sz002415"
    // 止损价格测试
    result,err1 = checkStock(codeGood,"20150625",10) 
    //fmt.Println("stockData is ", result)

    assert.Nil(t,err1,"checkStock should be nil")

    value,_ = strconv.ParseFloat(result["volPercent"],64)
    expectValue = -10.0
    //fmt.Println("stockData is ", value-expectValue,abs(value-expectValue))
    assert.True(t, assert.InDelta(t,value,expectValue,0.1,"checkStock failed"))


    // 错误的股票代码测试
    codeBad := "sz0024151"
    result,err1 = checkStock(codeBad,"20150625",10) 
    //fmt.Println("checkStock is ", result,err1)

    assert.NotNil(t,err1,"checkStock should not be nil")

    // 策略测试
    var stockList map[string] []string
    stockList = make(map[string] []string)
    stockList["20150629"] = []string{"sz002252","sz300019"}
    stockList["20150625"] = []string{"sz002415","sz002252"}

    ret,_ := computeAverageIncomeByStrategy(stockList,10)
    assert.True(t, assert.InDelta(t,ret["20150625"],2.422,0.01,"check failed"))
    assert.True(t, assert.InDelta(t,ret["20150629"],36.884,0.01,"check failed"))

    //InDelta(mockT, tc.a, tc.b, tc.delta)
    
}

// func TestHttpServer(t *testing.T) {
//     go httpServerInit()
//     httpClient()
// }

func TestParseIncome(t *testing.T) {
    str := `{"code":"sz002252","firstDay":"20150629","lastDay":"20150710","sellDay":"20150710","volPercent":"36.8849840255591"}`
    //expectStr = "123"
    dat,err := parseJson(str)
    //fmt.Printf("stockData is %s\n", dat["volPercent"])
    //fmt.Printf("stockData is %s\n", "36.8849840255591")
    assert.Equal(t, dat["volPercent"], "36.8849840255591", "they should be equal")

    str = `{"code":"sz002252","firstDay":"20150629","lastDay":"20150710","sellDay":"20150710","volPercent":"36.8849840255591"`
    dat,err = parseJson(str)
    assert.NotNil(t,err,"should not be nil")
    //fmt.Printf("stockData is %s\n", "36.8849840255591")
    //assert.Equal(t, dat["volPercent"], "36.8849840255591", "they should be equal")
}

func TestHttpCheckStock(t *testing.T) { 

    //mockT := new(testing.T)
    //assert := assert.New(t)

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
    assert.Equal(t,cnt, expectCnt, "they should be equal")

    go httpServerInit()
    ret,_:=httpClient("sz002252","20150629",10)
    expectStr := `{"code":"sz002252","firstDay":"20150629","lastDay":"20150710","sellDay":"20150710","volPercent":"36.8849840255591"}`
    //expectStr = "123"
    assert.Equal(t,ret, expectStr, "they should be equal")

    ret,_=httpClient("sz0022521","20150629",10)
    expectStr = `{"code":"","firstDay":"","lastDay":"","sellDay":"","volPercent":""}`
    assert.Equal(t,ret, expectStr, "they should be equal")
}


// func TestStdlibInterfaces(t *testing.T) {
//     val := new(struct {
//         Name   string `json:"name"`
//         Params *Json  `json:"params"`
//     })
//     val2 := new(struct {
//         Name   string `json:"name"`
//         Params *Json  `json:"params"`
//     })

//     raw := `{"name":"myobject","params":{"string":"simplejson"}}`

//     assert.Equal(t, nil, json.Unmarshal([]byte(raw), val))

//     assert.Equal(t, "myobject", val.Name)
//     assert.NotEqual(t, nil, val.Params.data)
//     s, _ := val.Params.Get("string").String()
//     assert.Equal(t, "simplejson", s)

//     p, err := json.Marshal(val)
//     assert.Equal(t, nil, err)
//     assert.Equal(t, nil, json.Unmarshal(p, val2))
//     assert.Equal(t, val, val2) // stable
// }




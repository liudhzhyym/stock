package main

import (
    "fmt"
//    "time"
//    "math/rand"
    "os"
    "io/ioutil"
    "flag"  //命令行选项解析器
    "strings"
    "net/http"
    "regexp"
    "errors"
    "sort"
    "strconv"
//    "log"
)


// func Count(ch chan int) {
//     r := rand.New(rand.NewSource(time.Now().UnixNano()))
//     ch <- r.Intn(100)
// }

func Usage() {
    fmt.Println("go run main.go -[h|s|c]")
    fmt.Println("go run main.go getStockData                     : get all stock data")
    fmt.Println("go run main.go checkStock 300473 20150716 10    : check stock data pain ")
    fmt.Println("go run main.go getStockByStrategy kdj002.st     : get_stock_by_strategy ")
    fmt.Println("go run main.go checkIncomeByStrategy kdj002.st  : check_income_by_strategy ")
    fmt.Println("go run main.go computeWinRandom                 : compute_win_random ")
    fmt.Println("go run main.go compare                          : compare ")
    fmt.Println("go run main.go computeAverageRandom 3")
}

// func getHtmlByStrategy (strategy,time){
//     return file_get_contents('0629kdj.html');
// }

// func getCodeFromHtml($html)
// {
//     $rule = '/stockpick\/search\?tid=stockpick&qs=stockpick_diag&ts=1&w=([0-9]+)/';  
//     preg_match_all($rule,$html,$result);  
//     return $result[1];
// }

func getCodeByStrategy(strategyName string,time string)(ret []string){
    strategyFile := "strategy/"+strategyName
    buf, err := ioutil.ReadFile(strategyFile)
    if err != nil {
        fmt.Fprintf(os.Stderr, "read strategyFile failed: %s\n", err)
        return
    }
    content := string(buf)
    content = strings.Replace(content, "${time}", time, -1)
    content = strings.Trim(content,"\n")
    content = strings.Replace(content, "\n", "；", -1)
    postData :=map[string]string{
        "typed":"1",
        "preParams":"",
        "ts":"1",
        "f":"1",
        "qs":"1",
        "selfsectsn":"",
        "querytype":"",
        "searchfilter":"",
        "tid":"stockpick",
        "w":content,
    }
    var postDataArr []string
    for key,value := range(postData) {
        postDataArr = append(postDataArr,key+"="+value)
    }
    postStr := strings.Join(postDataArr,"&")
    fmt.Println("postData = ",postStr)

    url := "http://www.iwencai.com/stockpick/search?" + postStr
    htmlContent := getHtmlByUrl(url)
    //获取token
    tokenRegexp := regexp.MustCompile(`"token":"([0-9a-z]+)","staticList"`)
    matchArr := tokenRegexp.FindAllString(htmlContent,-1)
    temp := tokenRegexp.FindStringSubmatch(matchArr[0])
    token := temp[1]
    if len(token) ==0 {
        //通过 api接口查询数据
        return
    }
    fmt.Println("matchArr = ",temp)
    apiUrl := "http://www.iwencai.com/stockpick/cache?token="+token+"&p=1&perpage=30&showType=[%22%22,%22%22,%22onTable%22,%22onTable%22,%22%22,%22%22]";
    dataStr := getHtmlByUrl(apiUrl)
    fmt.Println("dataStr = ",dataStr)
    // for _,str := range(matchArr) {
    //     temp := ruleRegexp.FindStringSubmatch(str)
    //     code := temp[1]
    //     if len(code)>0 {
    //         codeList = append(codeList,code)
    //     }    
    // }

    //$rule = '/stockpick\/search\?tid=stockpick&qs=stockpick_diag&ts=1&w=([0-9]+)/';  
    ruleRegexp := regexp.MustCompile(`stockpick\/search\?tid=stockpick&qs=stockpick_diag&ts=1&w=([0-9]+)`)
    matchStringArr := ruleRegexp.FindAllString(htmlContent,-1)

    var codeList []string
    for _,str := range(matchStringArr) {
        temp := ruleRegexp.FindStringSubmatch(str)
        code := temp[1]
        if len(code)>0 {
            codeList = append(codeList,code)
        }
    }
    fmt.Println("codeList = ",codeList)
    return codeList;

// $urlBase = 'http://www.iwencai.com/stockpick/search';
//     $url = $urlBase."?".implode('&',$dataArr);
//     //echo $url."\n";
//     // curl抓取网页
//     $htmlContent = file_get_contents($url);
//     //$htmlContent = getHtmlByStrategy($strategyStr,$time);
//     $codeList = getCodeFromHtml($htmlContent);

}

func getStockByStrategy(strategyName string) {
    getCodeByStrategy(strategyName,"20150701")
}

func getAllStockList() (ret []string) {
    stockListFile := "data/stock_list"
    buf, err := ioutil.ReadFile(stockListFile)
    if err != nil {
        fmt.Fprintf(os.Stderr, "File Error: %s\n", err)
    }
    str := string(buf)
    codeArr := strings.Split(str, "\n")
    cnt := len(codeArr)
    stockList := make([]string, cnt)

    for index,code := range(codeArr) {
        tmpcode := code
        //tmpcode = strings.Replace(tmpcode, "sh", "", -1)
        //tmpcode = strings.Replace(tmpcode, "sz", "", -1)
        stockList[index] = tmpcode
        //fmt.Println("Split: ", code)
    }
    //fmt.Println("Split: ", mySlice1)
    //fmt.Printf("%s\n", string(buf))
    return stockList
}

func getHtmlByUrl(url string) (ret string) {
    //url14 := "http://data.gtimg.cn/flashdata/hushen/daily/14/"+code+".js"
    resp, err := http.Get(url)
    if err != nil {
        // handle error
    }
 
    defer resp.Body.Close()
    body, err := ioutil.ReadAll(resp.Body)
    if err != nil {
        // handle error
    }
    return string(body)
}

func writeFile(fileName string,content string) {
    file,err := os.Create(fileName)
    defer file.Close()
    if err != nil {
        fmt.Println(fileName + " write failed ",err)
        return
    }

    file.WriteString(content)
}

func getStockDataFromFile(fileName string) (ret map[string] []string,err error){
    buf, err := ioutil.ReadFile(fileName)
    if err != nil {
        //fmt.Fprintf(os.Stderr, "File Error: %s\n", err)
        return
    }
    content := string(buf)
    codeArr := strings.Split(content, "\n")
    stockData := make(map[string] []string)
    reg := regexp.MustCompile("^20")
    for _,value := range(codeArr) {
        value = strings.Trim(value,"\n")
        dayData := strings.Split(value, " ")
        if reg.MatchString(dayData[0]) {
            stockData[dayData[0]] = dayData            
        }
    }   
    //fmt.Println("stockData: ", stockData)
    //fmt.Printf("fileName = %s\n", string(buf))
    return stockData,nil
}

func getStockData(code string) (ret map[string] []string,length int,err error) {

    fileName := "data/stock_data/" + code
    stockData,err := getStockDataFromFile(fileName)
    if err != nil {
        fmt.Println("read data from online")
        //fmt.Fprintf(os.Stderr, "read online data: %s\n", err)
        url14 := "http://data.gtimg.cn/flashdata/hushen/daily/14/"+code+".js"
        url15 := "http://data.gtimg.cn/flashdata/hushen/daily/15/"+code+".js"

        body := getHtmlByUrl(url14) + getHtmlByUrl(url15)

        codeArr := strings.Split(body, "\\n\\")
        content := ""
        reg := regexp.MustCompile("^1")
        for _,value := range(codeArr) {
            value = strings.Trim(value,"\n")
            dayData := strings.Split(value, " ")
            if reg.MatchString(dayData[0]) {
                value = "20" + value
                content = content + value + "\n"
                //fmt.Println("codeArr: ", dayData)
            }
        }   
        writeFile(fileName,content)
        stockData,err= getStockDataFromFile(fileName)
    } 
    cnt := len(stockData)
    if cnt == 0 {
        err= errors.New("stock data is null!")
        return
    } 
    //fmt.Println("codeArr: ", stockData)
    return stockData,cnt,nil
}

func getAllStockData() {
    codeList := getAllStockList()
    for _,code := range(codeList) {
        getStockData(code)
    } 
}

func getSortKeys(data map[string] []string) (ret []string) {
    var sortKeys []string
    for key,_ := range(data) {
        if len(key) > 0 {
            sortKeys = append(sortKeys,key)
        }
    }
    sort.Strings(sortKeys)
    return sortKeys
}

func checkStock(code string,time string,keepDays int) (ret map[string]string,errInfo error) {
    var stockData map[string] []string
    retData,_,err := getStockData("sh" + code)
    if err != nil {
        retData,_,err := getStockData("sz" + code)
        if err != nil {
            errInfo = errors.New("stock data is null!")
            return
        } else {
            stockData = retData
        }
    } else {
        stockData = retData
    }
    //fmt.Println("len : ", len(stockData))
    //对key进行排序
    sortKeys := getSortKeys(stockData)
    //fmt.Println("sortKeys 0 : ", sortKeys[0],len(sortKeys[0]))
    if(time<sortKeys[0]) {
        errInfo = errors.New("there is no stock data at ["+time+"] of stock "+code)
        return
    }

    stopLossPercent := -9.9
    stopWinPercent := 100.0

    //第一天的时间
    firstDay := ""
    //最后一天的时间
    lastDay := ""
    //止损、止盈的时间
    sellDay := ""
    volPercent := 0.0
    maxPercent := 0.0
    minPercent := 0.0
    index := 0
    result := make(map[string]string)
    for _,dayTime := range(sortKeys) {
        if dayTime>=time {
            if firstDay == "" {
                firstDay = dayTime
            }
            index++
            lastDay = dayTime
            sellDay = dayTime
            // 开盘价
            startPrice,_ := strconv.ParseFloat(stockData[firstDay][1],64)
            // // 当天收盘价
            endPrice,_ := strconv.ParseFloat(stockData[dayTime][2],64)
            // 当天最高价 
            maxPrice,_ := strconv.ParseFloat(stockData[dayTime][3],64)
            // 当天最低价 
            minPrice,_ := strconv.ParseFloat(stockData[dayTime][4],64)
            // //计算收益
            volPercent = 100*(endPrice - startPrice)/startPrice;
            maxPercent = 100*(maxPrice - startPrice)/startPrice;
            minPercent = 100*(minPrice - startPrice)/startPrice;
            //volPercentStr = strconv.FormatFloat(volPercent, 'f', -1, 64)
            fmt.Println("sortKeys: ",startPrice, endPrice,volPercent)
            if index >= keepDays {
                break
            }
            //止损
            if minPercent < stopLossPercent {
                volPercent = stopLossPercent
                fmt.Println("stop to loss!")
                break
            }
            //止盈
            if maxPercent >= stopWinPercent {
                volPercent = stopWinPercent
                fmt.Println("stop to win!")
                break
            }
        }
    }

    startPrice,_ := strconv.ParseFloat(stockData[firstDay][1],64)
    //价格过于小的，过于大的，都过滤掉开盘直接涨停的，开盘=收盘
    if startPrice<3|| startPrice>300 {
        errInfo = errors.New("startPrice is not in [3,300]")
        return
    }
    if stockData[firstDay][1] == stockData[firstDay][2] {
        errInfo = errors.New("this stock is surged limit")
        return
    }

    result["firstDay"] = firstDay
    result["lastDay"] = lastDay
    result["sellDay"] = sellDay
    result["volPercent"] = strconv.FormatFloat(volPercent, 'f', -1, 64)
    //stockData = getStockData("sh" + code)
    //fmt.Println("sortKeys: ", sortKeys)
    //fmt.Println("result: ", result)
    return result,nil
}

func computeWinByRandom() {
    //ret := getAllStockList()
    getAllStockData()
    //fmt.Println("Split: ", ret)
}

func main() {
    flag.Parse()   // Scans the arg list and sets up flags
    command := flag.Arg(0)
    //fmt.Println("command = %s",command)
    switch command {
        case "computeWinByRandom":
            computeWinByRandom()
        case "checkStock":
            code := flag.Arg(1)
            time := flag.Arg(2)
            day,_ := strconv.Atoi(flag.Arg(3))
            result,err:=checkStock(code,time,day)
            if err != nil {
                fmt.Println( "checkStock stock failed ",err)
            }
            fmt.Println("result: ", result)
        case "getStockByStrategy":
            strategyName := flag.Arg(1)
            // 获取最近一年策略锁对应的股票列表
            getStockByStrategy(strategyName)
        default:
            Usage()
    }

    // chs := make([]chan int,10)
    // for i:=0;i<10;i++{
    //     chs[i] = make(chan int)
    //     go Count(chs[i])
    // }
    // for _,ch := range(chs) {
    //     a := <-ch
    //     fmt.Printf("a = %d\n",a)
    // }
    // println("Hello", "world")
}

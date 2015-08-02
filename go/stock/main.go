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

func checkStock(code string,time string,keepDays int) {
    var stockData map[string] []string
    retData,_,err := getStockData("sh" + code)
    if err != nil {
        retData,_,err := getStockData("sz" + code)
        if err != nil {
            err= errors.New("stock data is null!")
            //return
        } else {
            stockData = retData
        }
    } else {
        stockData = retData
    }
    fmt.Println("len : ", len(stockData))
    //对key进行排序
    sortKeys := getSortKeys(stockData)
    fmt.Println("sortKeys 0 : ", sortKeys[0],len(sortKeys[0]))
    if(time<sortKeys[0]) {
        err = errors.New("there is no stock data at ["+time+"] of stock "+code)
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
    result := make(map[string]string)
    index := 0
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
    result["firstDay"] = firstDay
    result["lastDay"] = lastDay
    result["sellDay"] = sellDay
    result["volPercent"] = strconv.FormatFloat(volPercent, 'f', -1, 64)
    //stockData = getStockData("sh" + code)
    //fmt.Println("sortKeys: ", sortKeys)
    fmt.Println("Split: ", result)
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
            code := flag.Arg(1);
            time := flag.Arg(2);
            day,_ := strconv.Atoi(flag.Arg(3));
            checkStock(code,time,day)
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

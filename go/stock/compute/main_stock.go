package main

import (
    "fmt"
    "database/sql"
    _ "github.com/go-sql-driver/mysql"
    "strings"
    "regexp"
    "errors"
    "os"
    "io/ioutil"
    "sort"
)

var mysqlDB *sql.DB;

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

func getAllStockList(count int) (stockList []string,err error) {
    stockListFile := "../data/stock_list"
    buf, err := ioutil.ReadFile(stockListFile)
    if err != nil {
        fmt.Println(os.Stderr, "File Error: %s\n", err)
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
        //fmt.Println("get stockList failed")
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
        fmt.Println("Query error: %s\n", err,sql)
        return
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
    if len(ret) == 0 {
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
        fmt.Println("Query error: %s\n", err,sql)
        return
    }
    defer rows.Close()
    var stock,result,updateTime string
    for rows.Next() {
        err = rows.Scan(&stock,&result,&updateTime)
        //fmt.Println("stock =  ",stock,day,name)
        if err != nil {
            fmt.Println("Query error: %s\n", err)
            return
        }
        //return result
        //fmt.Println(temp)
    }
    if len(result) == 0 {
        err = errors.New("get result is null")
        //fmt.Println("get result is null")
        return
    }
    return result,nil
}


func getStockData(code string) (ret map[string] map[string]string,days []string,err error){
    fmt.Println("getStockData is ",code)
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
        fmt.Println("get getMarketTimeList of [%s] failed",code)
        return
    }
    //fmt.Println("list is ", sortKeys)
    return sortKeys,nil
}




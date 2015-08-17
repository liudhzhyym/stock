package main

import (
    "fmt"
    "database/sql"
    _ "github.com/go-sql-driver/mysql"
//    "time"
//    "math/rand"
//     "os"
//     "io/ioutil"
//     "flag"  //命令行选项解析器
     "strings"
//     "net/http"
     "regexp"
//     "errors"
     "sort"
//     "strconv"
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
		fmt.Println( "code is not a correct code, skip it",code)
		return
	}

	stockData,days,err := getStockData(code)
	if err != nil {
    	fmt.Println("getStockData failed , code = ", code)
    }
    //fmt.Println("days  ", stockData)
	// 判断是否有这一天的数据
	_,ok := stockData[time]
	if !ok || len(days)==0 {
        //没有这天的数据
        fmt.Println("no data of time is "+time+", code = "+code+" , skip it")
	    return
	}

	return ret,nil
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
    code := "sz002577"
    //dbRet,err := query(code)
    //stockData,days,err := getStockData(code)
    //fmt.Println( "dbRet =  ",stockData,days,err)
    checkStock(code,"20150129",10)
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



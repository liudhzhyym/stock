package main

import (
    "fmt"
)

func Add(a,b int) int {
    return a+b 
}

func Max(a,b int) (ret int) {
    ret = a 
    if b > a { 
        ret = b 
    }   
    return
}

func Min(a,b int) (ret int) {
    ret = a 
    if b < a { 
        ret = b 
    }   
    return
}

var base = 100

func Init() (ret int){
    base = 200
    ret = base
    return
}

func addNew(a int) (ret int) {
    ret = a + 1
    ret = ret + base
    fmt.Println("ret = ",ret)
    return
}
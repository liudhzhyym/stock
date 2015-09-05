package main

import "fmt"
import "time"
import "strconv"

var c chan map[string] string

func ready(w string,sec int) {
    time.Sleep(time.Duration(sec)*1e9)
    mapCreated := make(map[string] string)
    mapCreated["key"] = strconv.Itoa(sec)
    c <- mapCreated
    //fmt.Println(w," is ready")
    //c <- sec
}

func main() {
    c = make(chan map[string] string)
    go ready("Tee",3)
    go ready("Coffee",1)
    fmt.Println("wait ......")
    d1 := <- c
    d2 := <- c
    fmt.Println("d1 = ",d1)
    fmt.Println("d2 = ",d2)
}

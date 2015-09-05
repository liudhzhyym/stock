package main

import "fmt"
import "time"
import "math/rand"
import "strconv"

func Count(ch chan map[string] string) {

    r := rand.New(rand.NewSource(time.Now().UnixNano()))
    str := strconv.Itoa(r.Intn(100))
    mapCreated := make(map[string] string)
    mapCreated["key"] = str
    ch <- mapCreated
    //ch <- r.Intn(100)
}

func main() {
    chs := make([]chan map[string] string,10)
    for i:=0;i<10;i++{
        chs[i] = make(chan map[string] string)
        go Count(chs[i])
    }
    for _,ch := range(chs) {
        a := <-ch
        fmt.Println("a = ", a)
    }
    println("Hello", "world")
}

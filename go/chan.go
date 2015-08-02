package main

import "fmt"
import "time"
import "math/rand"

func Count(ch chan int) {
    r := rand.New(rand.NewSource(time.Now().UnixNano()))
    ch <- r.Intn(100)
}

func main() {
    chs := make([]chan int,10)
    for i:=0;i<10;i++{
        chs[i] = make(chan int)
        go Count(chs[i])
    }
    for _,ch := range(chs) {
        a := <-ch
        fmt.Printf("a = %d\n",a)
    }
    println("Hello", "world")
}

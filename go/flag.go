/**
 * Created with IntelliJ IDEA.
 * User: liaojie
 * Date: 12-9-8
 * Time: 下午3:53
 * To change this template use File | Settings | File Templates.
 */
package main
 
 
import (
    "os"
    "flag"  //命令行选项解析器
    "log"
 )
 
 
var omitNewline = flag.Bool("\n", false, "换行打印")
 
const (
    Space =""
    Newline = "\n"
)
 
func main() {
    //解析解析命令行标志,必须调用
    flag.Parse()   // Scans the arg list and sets up flags
    var s string = ""
    log.Print( os.Args[1:]);
    log.Printf(flag.Arg(1))   ;
 
    for i := 0; i < flag.NArg(); i++ {
        if i > 0 {
            s += Space
        }
        s += flag.Arg(i)
    }
    if !*omitNewline {
        s += Newline
    }
    os.Stdout.WriteString(s)
 
}


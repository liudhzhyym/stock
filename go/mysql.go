package main
import (
    "database/sql"
    _ "github.com/go-sql-driver/mysql"
    "log"
    "time"
)
/*
    create table t(
        id int primary key auto_increment,
        name varchar(20) not null,
        ts timestamp
    );
*/
func insert(db *sql.DB) {
    stmt, err := db.Prepare("insert into t(name,ts) values(?,?)")
    defer stmt.Close()
    if err != nil {
        log.Println(err)
        return
    }
    ts, _ := time.Parse("2006-01-02 15:04:05", "2014-08-28 15:04:00")
    stmt.Exec("edmond", ts)
}
func main() {
    db, err := sql.Open("mysql", "dog:123@tcp(127.0.0.1:3306)/test?charset=utf8")
    if err != nil {
        log.Fatalf("Open database error: %s\n", err)
    }
    defer db.Close()
    err = db.Ping()
    if err != nil {
        log.Fatal(err)
    }
    insert(db)
    rows, err := db.Query("select id,name,ts from t where id>?", 1)
    if err != nil {
        log.Println(err)
    }
    defer rows.Close()
    var id int
    var name string
    var ts string
    for rows.Next() {
        err := rows.Scan(&id, &name,&ts)
        if err != nil {
            log.Fatal(err)
        }
        log.Println(id, name,ts)
    }
    err = rows.Err()
    if err != nil {
        log.Fatal(err)
    }
}
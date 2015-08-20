package main

import (
    "testing"
)
type calcTest struct {
    a,b,ret int
}

var addTests = []calcTest{
    calcTest{4,6,10},
    calcTest{5,6,11},
    calcTest{8,-10,-2},
}

func TestAdd(t *testing.T) {
    for _,v := range addTests {
        ret := Add(v.a,v.b)
        if ret != v.ret {
            t.Errorf("%d add %d,want %d,but get %d",v.a,v.b,v.ret,ret)
        }
    }

}

func TestMax(t *testing.T){
    a,b := 100,300
    ret := Max(a,b)
    if ret != b {
        t.Errorf("%d is bigger than %d",b,a)
    }
}

func TestMin(t *testing.T) {
    a,b := 100,300
    ret := Min(a,b)
    if ret != a {
        t.Errorf("%d is smaller than %d",a,b)
    }
}

func TestAddNew(t *testing.T) {
    a,b := 100,301
    base := Init()
    //t.Errorf("base is not correct %d",base)
    if base != 200 {
        t.Errorf("base is not correct %d",base)
    }
    ret := addNew(a)
    if ret != b {
        t.Errorf("%d a is not correct %d",a,b)
    }
    //t.Errorf("%d a is not correct %d",ret,b)
}


func TestNew(t *testing.T) {
    t.Errorf("base is not correct")
}

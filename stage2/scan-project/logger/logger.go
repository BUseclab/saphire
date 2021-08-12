package logger

import (
	"path"
	"runtime"

	"github.com/fatih/color"
)

type colorPrintFunc func(format string, a ...interface{})

const (
	Debug = iota
	Info
	Notice
	Warning
	Error
	Critical
)

var Level int = Notice

var prefix = []string{
	"debu",
	"info",
	"noti",
	"warn",
	"erro",
	"crit",
}

var printFuncs = []colorPrintFunc{
	color.Cyan,
	color.White,
	color.Green,
	color.Yellow,
	color.Red,
	color.Magenta,
}

func Log(l int, format string, a ...interface{}) {
	if l >= Level && l < len(printFuncs) {
		pc, file, no, _ := runtime.Caller(1)
		details := runtime.FuncForPC(pc)
		if len(a) == 0 {
			printFuncs[l]("[%s] %s {%s@%s:%d} ", prefix[l], format, path.Base(details.Name()), path.Base(file), no)
		} else {
			a = append([]interface{}{prefix[l]}, a...)
			a = append(a, []interface{}{path.Base(details.Name()), path.Base(file), no}...)
			printFuncs[l]("[%s] "+format+" [%s@%s:%d]", a...)
		}
	}
}

func ShortLog(l int, format string, a ...interface{}) {
	if l >= Level && l < len(printFuncs) {
		if len(a) == 0 {
			printFuncs[l]("%s", format)
		} else {
			printFuncs[l](format, a...)
		}
	}
}

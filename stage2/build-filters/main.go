package main

import (
	"fmt"
	"os"
	"php-syscalls/php-api-deps/db"
)

func main() {
	if len(os.Args) < 2 || len(os.Args) > 3 {
		fmt.Printf("Usage: ./build-filters DB \n")
		os.Exit(0)
	}
	names := false
	if len(os.Args) == 3 {
		names = true
	}

	dbpath := os.Args[1]

	Db, _ := db.OpenDb(dbpath)

	if names {
		syscalls := Db.GetSyscallNamesForFile()
		for k, v := range syscalls {
			fmt.Printf("%s", k)
			for _, vv := range v {
				fmt.Printf(" %s", vv)
			}
			fmt.Printf("\n")
		}
	} else {
		syscalls := Db.GetSyscallIdsForFile()
		for k, v := range syscalls {
			fmt.Printf("%s", k)
			for _, vv := range v {
				fmt.Printf(" %d", vv)
			}
			fmt.Printf("\n")
		}
	}
}

package main

import (
	"bufio"
	"fmt"
	"os"
	"php-syscalls/php-api-deps/db"
	"strings"
)

func main() {
	if len(os.Args) != 3 {
		fmt.Printf("Usage: ./register-functions DB FUNC_SC_FILE \n")
		os.Exit(0)
	}

	dbpath := os.Args[1]
	path := os.Args[2]

	Db, err := db.OpenDb(dbpath)
	file, err := os.Open(path)
	if err != nil {
		panic(err)
	}
	defer file.Close()

	syscalls := Db.GetSyscallNames()
	tx, err := Db.Begin()
	scanner := bufio.NewScanner(file)
	for scanner.Scan() {
		function := strings.Fields(scanner.Text())
		if len(function) > 1 {
			function_name := function[0][0:(len(function[0]) - 1)]
			tx.CreateFunction(function_name)
			if function[1] == "*" {
				for _, sc := range syscalls {
					err = tx.CreateSyscallRequirement(function_name, sc)
				}
			} else {
				for _, sc := range function[1:] {
					err = tx.CreateSyscallRequirement(function_name, sc)
					if err != nil {
						panic(err)
					}
				}
			}
		}
	}
	err = tx.Commit()
}

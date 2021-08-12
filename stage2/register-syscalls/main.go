package main

import (
	"bufio"
	"fmt"
	"os"
	"php-syscalls/php-api-deps/db"
	"strconv"
	"strings"
)

func main() {
	if len(os.Args) < 2 {
		fmt.Printf("Usage: ./register-syscalls DB [HEADER] \n")
		os.Exit(0)
	}

	header := "/usr/include/x86_64-linux-gnu/asm/unistd_64.h"
	dbpath := os.Args[1]
	if len(os.Args) >= 3 {
		header = os.Args[2]
	}

	Db, err := db.OpenDb(dbpath)
	file, err := os.Open(header)
	if err != nil {
		panic(err)
	}
	defer file.Close()

	tx, err := Db.Begin()
	scanner := bufio.NewScanner(file)
	for scanner.Scan() {
		syscall := strings.Split(scanner.Text(), "__NR_")
		if len(syscall) == 2 {
			syscall = strings.Fields(syscall[1])
			syscall_number, err := strconv.Atoi(syscall[1])
			if err != nil {
				panic(err)
			}
			tx.CreateSyscall(syscall[0], syscall_number)
		}
	}
	err = tx.Commit()

}

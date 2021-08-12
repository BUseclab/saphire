package main

import (
	"bufio"
	"fmt"
	"os"
	"path/filepath"
	"strconv"
	"strings"
)

var functions map[string]map[string]bool
var children map[int]string

func processFile(path string) {
	f, err := os.Open(path)
	if err != nil {
		panic(err)
	}
	defer f.Close()

	splitPath := strings.Split(path, ".")
	pid, _ := strconv.Atoi(splitPath[len(splitPath)-1])
	inCallstack := false
	callstack := []string{}
	scn := ""
	scc := -1
	scp := true
	scanner := bufio.NewScanner(f)
	for scanner.Scan() {
		l := scanner.Text()
		if l == " --- php call stack starts ---" {
			inCallstack = true
			callstack = []string{}
		} else if l == " --- php call stack ends ---" {
			inCallstack = false
			if len(callstack) > 0 && scn != "" {
				fn := callstack[0]
				if _, ok := functions[fn]; !ok {
					functions[fn] = make(map[string]bool)
				}
				functions[fn][scn] = true
				if scn == "clone" {
					children[scc] = fn
				}
			}
			scn = ""
			scp = true
		} else if inCallstack {
			s := strings.Split(l, " ")
			if len(s) >= 3 {
				callstack = append(callstack, s[2])
			}
		} else {
			if _, err := strconv.Atoi(l); err != nil {
				s := strings.Split(l, " ")
				if len(s) > 0 {
					scn = strings.Split(s[0], "(")[0]

					if pf, ok := children[pid]; ok && !scp {
						functions[pf][scn] = true
					}
					if scn == "clone" {
						scc, _ = strconv.Atoi(s[len(s)-1])
						if pf, ok := children[pid]; ok {
							functions[pf][scn] = true
						}
					}
				}
			}
			scp = false
		}
	}
}

func main() {
	if len(os.Args) != 2 {
		fmt.Printf("Usage: ./process-traces TRACE_DIR \n")
		os.Exit(0)
	}

	path := os.Args[1]
	fileList := []string{}

	functions = make(map[string]map[string]bool)
	children = make(map[int]string)

	filepath.Walk(path, func(path string, f os.FileInfo, err error) error {
		fileList = append(fileList, path)
		return nil
	})
	for _, f := range fileList {
		processFile(f)

	}
	for fn, scs := range functions {
		fmt.Printf("%s: ", fn)
		for sc, _ := range scs {
			fmt.Printf("%s ", sc)
		}
		fmt.Printf("\n")
	}
}

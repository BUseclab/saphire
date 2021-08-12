package main

import (
	"fmt"
	"os"
	"php-syscalls/php-api-deps/db"
	"reflect"
	"sort"
	"strings"
)

// Outputs a graphviz dot file

var seen map[string]bool
var been map[string]bool
var count int = 0

var includeSetsComplete = [][]string{}

var dangerous []string

func main() {
	var dbpath, filepath string
	switch {
	case len(os.Args) == 2:
		dbpath = os.Args[1]
	case len(os.Args) == 3:
		dbpath = os.Args[1]
		filepath = os.Args[2]
	default:
		fmt.Printf("usage: ./build-filters db [filepath]\n")
		os.Exit(0)
	}

	fmt.Printf("digraph G{\n")
	Db, _ := db.OpenDb(dbpath)
	dangerous = Db.GetDangerousFunctions()
	if len(os.Args) == 2 {
		includes := Db.GetAllIncludes()
		for _, inc := range includes {
			if inc.File.String != "" && inc.Resolved_include.String != "" {
				fmt.Printf(`"%s" -> "%s"`+"\n", inc.File.String, inc.Resolved_include.String)
			}
		}
	} else {
		seen = make(map[string]bool)
		been = make(map[string]bool)
		fmt.Printf("%s", dfs(filepath, Db))
	}
	fmt.Printf("\n}")

}

func dfs(f string, Db *db.DB) string {
	if seen[f] {
		if been[f] {
			return fmt.Sprintf(`"%s"`, f)
		}
		return ""
	}
	seen[f] = true
	r := ""
	funccalls := Db.GetFunctionCallsForFileImmediate(f)

	for _, fn := range funccalls {
		for _, df := range dangerous {
			if fn == df {
				r = fmt.Sprintf(`%s%d[label="%s",fillcolor="#ff0000", style="filled,solid"]`+"\n", r, count, fn)
				r = fmt.Sprintf(`%s"%s" -> %d`+"\n", r, f, count)
				count++
			}
		}
	}

	includes := Db.GetIncludesForFile(f)
	files := []string{}
	for file, _ := range includes {
		files = append(files, file)
	}
	files_sorted := sort.StringSlice(files)
	sort.Sort(files_sorted)
	recurse := true
	for _, set := range includeSetsComplete {
		if reflect.DeepEqual(set, files_sorted) {
			recurse = false
		}
	}
	includeSetsComplete = append(includeSetsComplete, files_sorted[0:])
	if recurse || !recurse {
		for file, str := range includes {
			if ret := dfs(file, Db); ret != "" {
				r = fmt.Sprintf(`%s"%s" -> "%s"[label="%s", style="filled,solid"]`+"\n", r, f, file, strings.Replace(str, `"`, `\"`, -1))
				r = fmt.Sprintf(`%s%s`, r, ret)
			}
		}
	}
	if r != "" {
		been[f] = true
	}
	return r
}

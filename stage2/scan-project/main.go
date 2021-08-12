package main

import (
	"bytes"
	"fmt"
	"io/ioutil"
	"log"
	"os"
	"path/filepath"
	"php-syscalls/php-api-deps/db"
	"regexp"

	"github.com/z7zmey/php-parser/php7"

	l "php-syscalls/php-api-deps/scan-project/logger"
	visit "php-syscalls/php-api-deps/scan-project/visitor"
	"php-syscalls/php-api-deps/scan-project/visitor/include_string"

	"strings"
)

var numUnresolvedIncludes = 0
var numMultiresolvedIncludes = 0
var numResolvedIncludes = 0

var fqnClassDefinitions = make(map[string][]string)
var classDefinitions = make(map[string][]string)
var classInstances = make(map[string][]string)
var classInstanceCounts = make(map[string]int)
var fileList []string
var otherFileList []string
var Constants = make(map[string]includestring.StringTrie)

func printSet(m *map[string]bool) {
	for k, _ := range *m {
		fmt.Printf("%s\n", k)
	}
}

func recordResult(path string, basepath string, tx *db.Tx) {
	relativePath := strings.TrimPrefix(path, basepath)
	err := tx.CreateFile(relativePath)
	if err != nil {
		l.Log(l.Warning, "Bad File statement")
	}

	for k, _ := range visit.ClassDefinitions {
		name := strings.Split(k, `\`)
		shortname := name[len(name)-1]
		err = tx.CreateClass(k, shortname, relativePath)
		if err != nil {
			l.Log(l.Warning, "Bad Class statement", k)
		}
		classDefinitions[shortname] = append(classDefinitions[shortname], relativePath)
		fqnClassDefinitions[k] = append(classDefinitions[k], relativePath)
	}
	for k, _ := range visit.FunctionCalls {
		if classname := strings.Split(k, `::`); len(classname) > 1 {
			name := strings.Split(classname[0], `\`)
			tx.CreateClassInstance(relativePath, name[len(name)-1], classname[0])
			classInstances[name[len(name)-1]] = append(classInstances[name[len(name)-1]], relativePath)
		}
		name := strings.Split(k, `\`)
		shortname := name[len(name)-1]
		err = tx.CreateFunctionCall(relativePath, shortname)
		if err != nil {
			l.Log(l.Warning, "Bad FunctionCall statement", k)
		}
	}
	err = tx.CreateInclude(relativePath, relativePath, 0, 0, "")
	for k, v := range visit.Includes {
		if !strings.ContainsAny(strings.Replace(k, "php", "", -1), visit.Alpha) {
			for _, _ = range v {
				l.Log(l.Warning, "Unresolved Include: %s(%+v)", k, v)
				numUnresolvedIncludes++
			}
			continue
		}
		reps := -1
		for reps != 0 {
			l.Log(l.Notice, "Analyzing : %s", k)
			reps = 0
			if strings.Contains(k, basepath) {
				reps++
				k = strings.Replace(k, basepath, "", -1) 
			}
			if strings.Contains(k, "//") {
				reps++
				k = strings.Replace(k, "//", "/", -1)
			}
			if strings.Contains(k, "/./") {
				reps++
				k = strings.Replace(k, "/./", "/", -1)
			}
			if strings.Contains(k, ".*./") {
				reps++
				k = strings.Replace(k, ".*./", ".*/", -1) 
			}
			if strings.Contains(k, "(./") {
				reps++
				k = strings.Replace(k, "(./", "(/", -1) 
			}
			if strings.Contains(k, "|./") {
				reps++
				k = strings.Replace(k, "|./", "|/", -1) 
			}
			var re = regexp.MustCompile(`\/(\w+)\/\.\.`)
			if r := re.MatchString(k); r {
				reps++
				k = re.ReplaceAllString(k, "") 
			}
		}
		reps = -1
		for reps != 0 {
			l.Log(l.Notice, "Analyzing: %s", k)
			reps = 0
			if strings.Contains(k, "/../") {
				reps++
				k = strings.Replace(k, "/../", "/", -1) 
			}
		}
		reps = -1
		for reps != 0 {
			l.Log(l.Notice, "Analyzing: %s", k)
			reps = 0
			if strings.Contains(k, "../") {
				reps++
				k = strings.Replace(k, "../", "/", -1) 
			}
		}

		count := 0
		regex := regexp.MustCompile(k)
		for _, p := range fileList {
			trimmed := strings.TrimPrefix(p, basepath)
			if regex.MatchString(trimmed) {
				l.Log(l.Notice, "Match: %s -> %s", k, trimmed)
				for _, includeString := range v {
					err = tx.CreateInclude(relativePath, trimmed, count, 0, includeString)
				}
				count++
			}
		}
		if count == 0 {
			for _, p := range otherFileList {
				trimmed := strings.TrimPrefix(p, basepath)
				if regex.MatchString(trimmed) {
					l.Log(l.Notice, "Unscanned Match: %s -> %s", k, trimmed)
					count++
				}
			}
		}
		if count == 0 {
			if !strings.ContainsAny(k, ")(") {
				for i := 0; i < len(k); i++ {
					mutated := ".*" + k[i:]
					if !strings.ContainsAny(mutated, visit.Alpha) {
						break
					}
					regex = regexp.MustCompile(mutated)
					for _, p := range fileList {
						trimmed := strings.TrimPrefix(p, basepath)
						if regex.MatchString(trimmed) {
							l.Log(l.Notice, "Mutated Match: %s -> %s", mutated, trimmed)
							for _, includeString := range v {
								err = tx.CreateInclude(relativePath, trimmed, count, 0, includeString)
							}
							count++
						}
					}
					for _, p := range otherFileList {
						trimmed := strings.TrimPrefix(p, basepath)
						if regex.MatchString(trimmed) {
							l.Log(l.Notice, "Mutated Unscanned Match: %s -> %s", mutated, trimmed)
							count++
						}
					}
					if count >= 0 {
						break
					}
				}
			}
		}
		if count == 0 {
			l.Log(l.Warning, "Unresolved Include: %s(%+v)", k, v)
			numUnresolvedIncludes += len(v)
		} else if count > 1 {
			numMultiresolvedIncludes += len(v)
			l.Log(l.Notice, "Multiple matches: %s", k)
		} else {
			numResolvedIncludes += len(v)
		}
	}
	for k, _ := range visit.ClassInstances {
		name := strings.Split(k, `\`)
		shortname := name[len(name)-1]
		if shortname != "self" &&
			shortname != "parent" &&
			shortname != "static" {
			err = tx.CreateClassInstance(relativePath, k, shortname)
			if err != nil {
				panic(err)
			}
			classInstances[k] = append(classInstances[k], relativePath)
			classInstanceCounts[k] += visit.ClassInstanceCounts[k]
		}
	}
}

func preprocessFile(path string, basepath string) {
	l.Log(l.Info, "Preprocessing file: %s", path)
	fileContents, _ := ioutil.ReadFile(path)
	parser := php7.NewParser(bytes.NewBufferString(string(fileContents)), path)
	parser.Parse()

	rootNode := parser.GetRootNode()
	if rootNode == nil {
		return
	}

	visitor := visit.ConstWalker{
		Writer: os.Stdout,
		Indent: "",
	}
	Constants["DIRECTORY_SEPARATOR"] = includestring.StringTrie{Content: "/"}
	visit.Constants = &Constants
	visit.ClearAssigns()
	visit.File = path

	rootNode.Walk(visitor)
}
func processFile(path string, basepath string, tx *db.Tx) {
	fileContents, _ := ioutil.ReadFile(path)
	l.Log(l.Info, "Processing file: %s", path)
	parser := php7.NewParser(bytes.NewBufferString(string(fileContents)), path)
	parser.Parse()

	rootNode := parser.GetRootNode()
	if rootNode == nil {
		return
	}

	nsResolver := visit.NewNamespaceResolver()
	rootNode.Walk(nsResolver)

	visitor := visit.Dumper{
		Writer:     os.Stdout,
		Indent:     "",
		NsResolver: nsResolver,
	}
	visit.FunctionCalls = make(map[string]bool)
	visit.Includes = make(map[string][]string)
	visit.StaticIncludes = make(map[string]bool)
	visit.DynamicIncludes = make(map[string]bool)
	visit.SemiDynamicIncludes = make(map[string]bool)
	visit.ClassInstances = make(map[string]bool)
	visit.ClassInstanceCounts = make(map[string]int)
	visit.ClassDefinitions = make(map[string]bool)
	visit.ClearAssigns()

	visit.File = path
	visit.FunctionCalls["printf"] = true
	Constants["DIRECTORY_SEPARATOR"] = includestring.StringTrie{Content: "/"}
	visit.Constants = &Constants

	rootNode.Walk(visitor)

	recordResult(path, basepath, tx)
}

func main() {
	l.Level = l.Info
	project_path := os.Args[1]
	project_path = strings.TrimSuffix(project_path, "/")
	err := filepath.Walk(project_path, func(path string, f os.FileInfo, err error) error {
		if filepath.Ext(path) == ".php" ||
			filepath.Ext(path) == ".install" ||
			filepath.Ext(path) == ".engine" ||
			filepath.Ext(path) == ".module" ||
			filepath.Ext(path) == ".theme" ||
			filepath.Ext(path) == ".html" ||
			filepath.Ext(path) == ".inc" {
			fileList = append(fileList, path)
		} else {
			otherFileList = append(otherFileList, path)
		}
		return nil
	})

	if err != nil {
		log.Fatal(err)
	}
	Db, err := db.OpenDb(os.Args[2])
	if err != nil {
		panic(err)
	}

	tx, err := Db.Begin()
	for _, file := range fileList {
		preprocessFile(file, project_path)
	}
	for _, file := range fileList {
		processFile(file, project_path, tx)
	}
	err = tx.Commit()

	ResolveClassInstances(Db, &fqnClassDefinitions, &classDefinitions, &classInstances, &classInstanceCounts)
	l.Log(l.Critical, "Total Includes:\t\t%d", visit.NumIncludes)
	l.Log(l.Critical, "Static Includes:\t\t%d", visit.NumStaticIncludes)
	l.Log(l.Critical, "Dynamic Includes:\t%d", visit.NumDynamicIncludes)
	l.Log(l.Critical, "Resolved Includes:\t%d", numResolvedIncludes)
	l.Log(l.Critical, "Multiresolved Includes:\t%d", numMultiresolvedIncludes)
	l.Log(l.Critical, "Unresolved Includes:\t%d", numUnresolvedIncludes)
	l.Log(l.Critical, "Resolved Classes:\t%d", ResolutionCt)
	l.Log(l.Critical, "Unresolved Classes:\t%d", FailureCt)
}

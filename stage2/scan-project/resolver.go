package main

import (
	"php-syscalls/php-api-deps/db"
)

var ignoreStrings = []string{
	"__LINE__",
	"__FILE__",
	"__DIR__",
	"__FUNCTION__",
	"__CLASS__",
	"__TRAIT__",
	"__METHOD__",
	"__NAMESPACE__",
	"'",
	"`",
	"\"",
}

var ResolutionCt = 0
var FailureCt = 0

func ResolveClassInstances(Db *db.DB,
	fqnClassDefinitions *map[string][]string,
	classDefinitions *map[string][]string,
	classInstances *map[string][]string,
	classInstanceCounts *map[string]int) error {
	tx, _ := Db.Begin()
	for k, v := range *classInstances {
		resolutions := []string{}
		if _, ok := (*fqnClassDefinitions)[k]; ok {
			ResolutionCt++
			resolutions = append(resolutions, (*fqnClassDefinitions)[k]...)

		} else if _, ok := (*classDefinitions)[k]; ok {
			ResolutionCt++
			resolutions = append(resolutions, (*classDefinitions)[k]...)
		} else {
			FailureCt++
		}
		for _, m := range resolutions {
			for _, f := range v {
				tx.CreateResolvedClassInstance(f, m, k)
			}
		}
	}
	return tx.Commit()
}

# Recursive Copy in GoLang
This is a library to recursively copy structs in goLang

It is a slight modification taken from: https://gist.github.com/hvoecking/10772475

## Usage

```
import "github.com/adam-hanna/recusiveCopy"

type MyFirstType {
	foobar string
}

type MySecondType struct {
	foo string
	bar int
	myStruct MyFirstType
}

first := MyFirstType { "hello" }
second := MySecondType { "good bye", 30, first }

myCopy := recusiveCopy.Copy(second)
log.Println(myCopy)
```
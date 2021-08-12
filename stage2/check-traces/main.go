package main

import (
	"bufio"
	"fmt"
	"io/ioutil"
	"os"
	"path/filepath"
	"regexp"
	"strings"
)

var whitelist = make(map[string]map[string]bool)

var base = map[string]bool{
	"accept":       true,
	"poll":         true,
	"times":        true,
	"brk":          true,
	"chdir":        true,
	"rt_sigaction": true,
	"setitimer":    true,
}

func loadWhitelist(path string) {
	fileContentBytes, _ := ioutil.ReadFile(path)
	fileContent := strings.Split(string(fileContentBytes[:]), "\n")
	for _, l := range fileContent {
		script := strings.Split(l, " ")[0]
		whitelist[script] = make(map[string]bool)
		if len(strings.Split(l, " ")) > 1 {
			for _, sc := range strings.Split(l, " ")[1:] {
				whitelist[script][sc] = true
			}
		}
	}
}

func dumpSyscalls(syscalls map[string]string, script string, fn string, trace string, line int) {
	if script == "/home/append.php" {
		return
	}
	for sc, name := range syscalls {
		if _, ok := base[name]; !ok {
			if scs, ok := whitelist[script]; ok {
				if _, ok := scs[sc]; !ok {
					fmt.Printf("%s : %s not in whitelist for %s @ %s\n", name, fn, script, trace)
				}
			} else {
				fmt.Printf("%s: %s file not in whitelist: %s @ %s\n", name, fn, script, trace)
			}
		}
	}

}
func checkTests(path string, trim string) {
	lnr := 0
	file, _ := os.Open(path)
	scanner := bufio.NewScanner(file)
	defer file.Close()

	re := regexp.MustCompile(".*{.*}.*->(?P<Path>.*):.*")
	refn := regexp.MustCompile(".* (?P<Function>.*) ->.*")
	resc := regexp.MustCompile(`^(?P<Syscall>[a-z0-9A-Z_]*)\(.*\).*=`)
	scriptPath := ""
	function := ""
	fileContent := []string{}
	syscalls := make(map[string]string)
	for scanner.Scan() {
		l := scanner.Text()
		if len(fileContent) >= 100 {
			fileContent = fileContent[1:]
		}
		fileContent = append(fileContent, l)
		i := len(fileContent) - 1
		if i > 0 && strings.Contains(fileContent[i-1], "call stack starts") {
			matches := refn.FindStringSubmatch(fileContent[i])
			if function == "" && len(matches) > 1 {
				if !strings.Contains(fileContent[i], "phpunit") {
					function = matches[1]
				}
			}
		} else if strings.Contains(l, "call stack ends") {
			for ii := i - 1; ii >= 0; ii-- {
				if !strings.Contains(strings.ToLower(fileContent[ii]), "phpunit") && !strings.Contains(strings.ToLower(fileContent[ii]), "test") {
					matches := re.FindStringSubmatch(fileContent[ii])
					if len(matches) > 1 {
						scriptPath = strings.TrimPrefix(matches[1], trim)
					}
					break
				}
			}
			if function != "" {
				dumpSyscalls(syscalls, scriptPath, function, filepath.Base(path), lnr)
				function = ""
			}
			for k := range syscalls {
				delete(syscalls, k)
			}
			fileContent = []string{}
		} else if resc.MatchString(l) {
			matches := resc.FindStringSubmatch(l)
			syscall := matches[1]
			scanner.Scan()
			syscallnr := scanner.Text()
			syscalls[syscallnr] = syscall
			lnr++
		}
		lnr++
	}
}

func checkFile(path string, trim string) {
	lnr := 0
	file, _ := os.Open(path)
	scanner := bufio.NewScanner(file)
	defer file.Close()

	re := regexp.MustCompile(".*{main}.*->(?P<Path>.*):.*")
	refn := regexp.MustCompile(".* (?P<Function>.*) ->.*")
	resc := regexp.MustCompile(`^(?P<Syscall>[a-z0-9A-Z_]*)\(.*\).*=`)
	scriptPath := ""
	function := ""
	fileContent := []string{}
	syscalls := make(map[string]string)
	accept := false
	for scanner.Scan() {
		l := scanner.Text()
		if len(fileContent) >= 100 {
			fileContent = fileContent[1:]
		}
		fileContent = append(fileContent, l)
		i := len(fileContent) - 1
		if i > 0 && strings.Contains(fileContent[i-1], "call stack starts") {
			matches := refn.FindStringSubmatch(fileContent[i])
			if len(matches) > 1 {
				function = matches[1]
				dumpSyscalls(syscalls, scriptPath, function, filepath.Base(path), lnr)
				for k := range syscalls {
					delete(syscalls, k)
				}
			}
		} else if strings.Contains(l, "call stack ends") {
			for ii := i - 1; ii >= 0; ii-- {
				if !strings.Contains(strings.ToLower(fileContent[ii]), "phpunit") && !strings.Contains(strings.ToLower(fileContent[ii]), "test") {
					matches := re.FindStringSubmatch(fileContent[ii])
					if len(matches) > 1 && accept {
						scriptPath = strings.TrimPrefix(matches[1], trim)
						accept = false
					}
					break
				}
			}
			fileContent = []string{}
		} else if resc.MatchString(l) {
			matches := resc.FindStringSubmatch(l)
			syscall := matches[1]
			scanner.Scan()
			syscallnr := scanner.Text()
			if !accept {
				syscalls[syscallnr] = syscall
			}
			if syscall == "accept" {
				for k := range syscalls {
					delete(syscalls, k)
				}
				accept = true
			}
			lnr++
		}
		lnr++
	}
}

func main() {
	whitelistPath := os.Args[1]
	tracePath := os.Args[2]
	trim := os.Args[3]
	mode := os.Args[4]

	loadWhitelist(whitelistPath)
	err := filepath.Walk(tracePath, func(path string, f os.FileInfo, err error) error {
		if mode == "0" {
			checkFile(path, trim)
		} else {
			checkTests(path, trim)
		}
		return nil
	})
	if err != nil {
		panic(err)
	}
}

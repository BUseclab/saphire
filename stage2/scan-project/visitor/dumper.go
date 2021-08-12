// Package visitor contains walker.visitor implementations
package visitor

import (
	"bytes"
	"io"
	"path"
	"path/filepath"
	"reflect"
	"strconv"
	"strings"

	//"github.com/z7zmey/php-parser/comment"
	"github.com/adam-hanna/copyRecursive"

	"github.com/z7zmey/php-parser/node"
	"github.com/z7zmey/php-parser/node/expr"
	"github.com/z7zmey/php-parser/node/expr/assign"
	"github.com/z7zmey/php-parser/node/expr/binary"
	"github.com/z7zmey/php-parser/node/name"
	"github.com/z7zmey/php-parser/node/scalar"
	"github.com/z7zmey/php-parser/node/stmt"
	"github.com/z7zmey/php-parser/parser"
	"github.com/z7zmey/php-parser/printer"

	l "php-syscalls/php-api-deps/scan-project/logger"
	"php-syscalls/php-api-deps/scan-project/visitor/include_string"

	"github.com/z7zmey/php-parser/walker"
)

const Alpha = "abcdefghijklmnopqrstuvwxyz"

var Exists = struct{}{}

// Dumper writes ast hierarchy to an io.Writer
// Also prints comments and positions attached to nodes

type Dumper struct {
	Writer     io.Writer
	Indent     string
	Comments   parser.Comments
	Positions  parser.Positions
	NsResolver *NamespaceResolver
}

type ConstWalker struct {
	Writer    io.Writer
	Indent    string
	Comments  parser.Comments
	Positions parser.Positions
}

var File string

var FunctionCalls map[string]bool
var StaticIncludes map[string]bool
var SemiDynamicIncludes map[string]bool
var DynamicIncludes map[string]bool
var ClassInstances map[string]bool
var ClassInstanceCounts map[string]int
var ClassDefinitions map[string]bool
var Assigns map[string]st

var Includes map[string][]string

var Constants *map[string]st

var includePath []string
var inInclude bool = false
var indexingIntoArray bool = false

type st = includestring.StringTrie

var containsDynamicIncludeItem bool = false
var containsStaticIncludeItem bool = false

var NumIncludes int = 0
var NumDynamicIncludes int = 0
var NumStaticIncludes int = 0

func ClearAssigns() {
	Assigns = make(map[string]st)
}

func processMagicConstant(n *scalar.MagicConstant) string {
	switch n.Value {
	case "__FILE__":
		return File
	case "__DIR__":
		return path.Dir(File)
	default:
		l.Log(l.Error, "Unhandled MagicConstant: %s", n.Value)
	}
	return ""
}

func processFunctionCall(n *expr.FunctionCall) st {
	result := st{Dynamic: true}
	if _, ok := n.Function.(*name.Name); !ok {
		l.Log(l.Debug, "Unhandled FunctionCall")
		return result
	}
	switch n.Function.(*name.Name).Parts[0].(*name.NamePart).Value {

	case "dirname":
		result := st{}
		dirs := processStringExpr(n.ArgumentList.Arguments[0].(*node.Argument).Expr)
		dirs.Consolidate()
		iters := 1
		if len(n.ArgumentList.Arguments) > 1 {
			striters := processStringExpr(n.ArgumentList.Arguments[1].(*node.Argument).Expr)
			striters.Consolidate()
			iters, _ = strconv.Atoi(striters.Content)
		}
		for _, pth := range dirs.DfsPaths() {
			pth.Consolidate()
			if len(pth.Children) == 0 {
				p := pth.Content
				for i := iters; i > 0; i-- {
					if len(p) > 1 {
						if p[len(p)-1] == '/' {
							p = p[:len(p)-1]
						}
					}
					p = path.Dir(p)
				}
				result.AddChild(st{Content: p})
			} else {
				l.Log(l.Info, "Not good...")
			}

		}
		return result
	case "realpath":
		result := st{}
		components := processStringExpr(n.ArgumentList.Arguments[0].(*node.Argument).Expr)
		for _, p := range components.DfsPaths() {
			if p.IsSimpleString() {
				fp, _ := filepath.Abs(p.Content)
				result.AddChild(st{Content: fp})
			}
		}
	default:
		// l.Log(l.Error, "Unhandled Function: %s", n.Function.(*name.Name).Parts[0].(*name.NamePart).Value)
	}
	return st{Dynamic: true}
}
func processStringExpr(n node.Node) st {
	switch v := n.(type) {
	case *scalar.String:
		result := st{}
		s := v.Value
		if len(s) > 0 && (s[0] == '"' || s[0] == '\'') {
			s = s[1:]
		}
		if len(s) > 0 && (s[len(s)-1] == '"' || s[len(s)-1] == '\'') {
			s = s[:len(s)-1]
		}
		result.Content = s
		return result
	case *binary.Concat:
		result := processStringExpr(v.Left)
		result2 := processStringExpr(v.Right)
		result2.Consolidate()
		result.AddLeaf(result2)
		result.Consolidate()
		return result
	case *scalar.Encapsed:
		result := st{}
		for _, part := range v.Parts {
			if p, ok := part.(*scalar.EncapsedStringPart); ok {
				result.AddLeaf(st{Content: p.Value})
			} else {
				result.AddLeaf(processStringExpr(part))
			}
		}
		result.Consolidate()
		return result
	case *expr.ConstFetch:
		if _, ok := v.Constant.(*name.Name); ok {
			constIdentifier := v.Constant.(*name.Name).Parts[0].(*name.NamePart).Value
			result := st{Constant: constIdentifier}
			return result
		}
	case *scalar.MagicConstant:
		return st{Content: processMagicConstant(v)}
	case *scalar.Lnumber:
		return st{Content: v.Value}
	case *expr.FunctionCall:
		return processFunctionCall(v)
	case *expr.Variable:
		if value, ok := v.VarName.(*node.Identifier); ok {
			if val, ok := Assigns[value.Value]; ok {
				return recursiveCopy.Copy(val).(st)
			}
		} else {
			l.Log(l.Warning, "Could not parse variable")
		}
	}
	return st{Dynamic: true}
}

func countInclude(n node.Node) {
	NumIncludes++
	switch n.(type) {
	case *scalar.String:
		NumStaticIncludes++
	default:
		NumDynamicIncludes++
	}
}

func resolveInclude(n node.Node) []string {
	path := st{}
	switch v := n.(type) {
	case *expr.Include:
		countInclude(v.Expr)
		path = processStringExpr(v.Expr)
	case *expr.IncludeOnce:
		countInclude(v.Expr)
		path = processStringExpr(v.Expr)
	case *expr.Require:
		countInclude(v.Expr)
		path = processStringExpr(v.Expr)
	case *expr.RequireOnce:
		countInclude(v.Expr)
		path = processStringExpr(v.Expr)
	}
	l.Log(l.Info, "%s resolved to %+v", nodeSource(&n), path)
	if path.IsSimpleString(Constants) {
		resolution := path.SimpleString()
		pattern := ".*" + resolution + ".*"
		if _, ok := Includes[pattern]; !ok {
			Includes[pattern] = []string{}
		}
		l.Log(l.Error, "%s patterened to SS %s", nodeSource(&n), pattern)
		Includes[pattern] = append(Includes[pattern], nodeSource(&n))
	} else {
		pattern := ".*("
		possibilities := path.DfsPaths()
		first := true
		for _, p := range possibilities {
			if !first {
				pattern += "|"
			}
			first = false
			trielink := p
			for skip := false; !skip; {
				if trielink.Dynamic || trielink.Constant != "" {
					pattern += ".*"
				} else {
					pattern += trielink.Content
				}
				if len(trielink.Children) == 1 {
					trielink = trielink.Children[0]
				} else {
					skip = true
				}
			}
		}
		pattern += ").*"
		if _, ok := Includes[pattern]; !ok {
			Includes[pattern] = []string{}
		}
		Includes[pattern] = append(Includes[pattern], nodeSource(&n))
		l.Log(l.Error, "%s patterened to DS %s", nodeSource(&n), pattern)
	}
	return []string{path.Content}
}

func nodeSource(n *node.Node) string {
	out := new(bytes.Buffer)
	p := printer.NewPrinter(out, "    ")
	p.Print(*n)
	return strings.Replace(out.String(), "\n", "\\ ", -1)
}

// EnterNode is invoked at every node in hierarchy
func (d Dumper) EnterNode(w walker.Walkable) bool {

	n := w.(node.Node)
	switch reflect.TypeOf(n).String() {
	case "*expr.Include", "*expr.IncludeOnce", "*expr.Require", "*expr.RequireOnce":
		inInclude = true
		resolveInclude(n)
		includePath = nil
	case "*assign.Assign":
		asn := n.(*assign.Assign)
		vn, ok := asn.Variable.(*expr.Variable)
		if ok {
			v, ok := vn.VarName.(*node.Identifier)
			if ok {
				Assigns[v.Value] = processStringExpr(asn.Expression)
				l.Log(l.Info, "Variable %s resolved to %+v", v.Value, Assigns[v.Value])
			}
		} else {
		}

	case "*expr.FunctionCall":
		// Record
		function := n.(*expr.FunctionCall)
		functionName, ok := function.Function.(*name.Name)

		if ok {
			if namespacedName, ok := d.NsResolver.ResolvedNames[functionName]; ok {
				FunctionCalls[namespacedName] = true
			} else {
				parts := functionName.Parts
				lastNamePart, ok := parts[len(parts)-1].(*name.NamePart)
				if ok {
					FunctionCalls[lastNamePart.Value] = true
				}
			}
		} else {
			l.Log(l.Warning, "FunctionCall not handled: %s", nodeSource(&n))
		}
	case "*expr.StaticCall":
		lastClassPart := ""
		variableClass := false
		class := n.(*expr.StaticCall).Class
		call := n.(*expr.StaticCall).Call
		if _, ok := class.(*expr.Variable); ok {
			variableClass = true
			lastClassPart = "$variable"
		}
		className, ok1 := class.(*name.Name)
		fqnClassName, ok2 := class.(*name.FullyQualified)
		classIdentifier, ok3 := class.(*node.Identifier)
		if !(ok1 || ok2 || ok3 || variableClass) {
			l.Log(l.Warning, "StaticCall classname not handled %s", nodeSource(&n))
			break
		}
		callName, ok := call.(*node.Identifier)
		if !ok {
			l.Log(l.Warning, "StaticCall callname not handled %s", nodeSource(&n))
			break
		}
		if ok1 {
			lastClassPart = className.Parts[len(className.Parts)-1].(*name.NamePart).Value
		} else if ok2 {
			lastClassPart = fqnClassName.Parts[len(fqnClassName.Parts)-1].(*name.NamePart).Value
		} else if ok3 {
			lastClassPart = classIdentifier.Value
		}
		FunctionCalls[lastClassPart+"::"+callName.Value] = true

	case "*expr.New":
		class := n.(*expr.New).Class
		className, ok := class.(*name.Name)
		if ok {
			if namespacedName, ok := d.NsResolver.ResolvedNames[className]; ok {
				ClassInstanceCounts[namespacedName]++
			} else {
				lastClassPart, ok := className.Parts[len(className.Parts)-1].(*name.NamePart)
				if !ok {
					l.Log(l.Warning, "expr.New not handled: %s", nodeSource(&n))
					break
				}
				ClassInstanceCounts[lastClassPart.Value]++
			}
		} else if className, ok := class.(*name.FullyQualified); ok {
			if namespacedName, ok := d.NsResolver.ResolvedNames[className]; ok {
				ClassInstances[namespacedName] = true
			} else {
				lastClassPart := className.Parts[len(className.Parts)-1].(*name.NamePart)
				ClassInstances[lastClassPart.Value] = true
				ClassInstanceCounts[lastClassPart.Value]++
			}
		} else {
			l.Log(l.Warning, "expr.New not handled: %s", nodeSource(&n))
			break
		}
	case "*stmt.Use":
                class := n.(*stmt.Use).Use
                className, ok := class.(*name.Name)
                if ok {
                        if namespacedName, ok := d.NsResolver.ResolvedNames[className]; ok {
                                ClassInstanceCounts[namespacedName]++
                        } else {
                                lastClassPart, ok := className.Parts[len(className.Parts)-1].(*name.NamePart)
                                if !ok {
                                        l.Log(l.Warning, "expr.New not handled: %s", nodeSource(&n))
                                        break
                                }
                                l.Log(l.Info, "stmt.Use handled: %s", lastClassPart)
                                ClassInstances[lastClassPart.Value] = true
                                ClassInstanceCounts[lastClassPart.Value]++
                        }
                }
	case "*stmt.Class":
		class := n.(*stmt.Class)
		if namespacedName, ok := d.NsResolver.ResolvedNames[class]; ok {
			ClassDefinitions[namespacedName] = true
		} else {
			className, ok := class.ClassName.(*node.Identifier)
			if !ok {
				l.Log(l.Warning, "Could not parse Class definition")
				break
			}
			ClassDefinitions[className.Value] = true
		}
		extends := n.(*stmt.Class).Extends
		if extends != nil {
			extendsName, ok := extends.ClassName.(*name.Name)
			if !ok {
				extendsName, ok := extends.ClassName.(*name.FullyQualified)
				if !ok {
					l.Log(l.Warning, "Could not parse Class definition")
					break
				}
				ClassInstances[extendsName.Parts[len(extendsName.Parts)-1].(*name.NamePart).Value] = true
				break
			}
			ClassInstances[extendsName.Parts[0].(*name.NamePart).Value] = true
			ClassInstanceCounts[extendsName.Parts[0].(*name.NamePart).Value]++
		}
	}

	return true
}

func (d Dumper) GetChildrenVisitor(key string) walker.Visitor {
	return Dumper{d.Writer, d.Indent + "    ", d.Comments, d.Positions, d.NsResolver}
}

func (d Dumper) LeaveNode(w walker.Walkable) {
	//parse := false
	n := w.(node.Node)

	switch reflect.TypeOf(n).String() {

	case "*expr.ArrayDimFetch":
		indexingIntoArray = false
	}
}

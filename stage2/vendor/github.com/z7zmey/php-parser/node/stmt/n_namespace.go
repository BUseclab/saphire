package stmt

import (
	"github.com/z7zmey/php-parser/node"
	"github.com/z7zmey/php-parser/walker"
)

// Namespace node
type Namespace struct {
	NamespaceName node.Node
	Stmts         []node.Node
}

// NewNamespace node constructor
func NewNamespace(NamespaceName node.Node, Stmts []node.Node) *Namespace {
	return &Namespace{
		NamespaceName,
		Stmts,
	}
}

// Attributes returns node attributes as map
func (n *Namespace) Attributes() map[string]interface{} {
	return nil
}

// Walk traverses nodes
// Walk is invoked recursively until v.EnterNode returns true
func (n *Namespace) Walk(v walker.Visitor) {
	if v.EnterNode(n) == false {
		return
	}

	if n.NamespaceName != nil {
		vv := v.GetChildrenVisitor("NamespaceName")
		n.NamespaceName.Walk(vv)
	}

	if n.Stmts != nil {
		vv := v.GetChildrenVisitor("Stmts")
		for _, nn := range n.Stmts {
			if nn != nil {
				nn.Walk(vv)
			}
		}
	}

	v.LeaveNode(n)
}

package expr

import (
	"github.com/z7zmey/php-parser/node"
	"github.com/z7zmey/php-parser/walker"
)

// ArrayItem node
type ArrayItem struct {
	Key node.Node
	Val node.Node
}

// NewArrayItem node constructor
func NewArrayItem(Key node.Node, Val node.Node) *ArrayItem {
	return &ArrayItem{
		Key,
		Val,
	}
}

// Attributes returns node attributes as map
func (n *ArrayItem) Attributes() map[string]interface{} {
	return nil
}

// Walk traverses nodes
// Walk is invoked recursively until v.EnterNode returns true
func (n *ArrayItem) Walk(v walker.Visitor) {
	if v.EnterNode(n) == false {
		return
	}

	if n.Key != nil {
		vv := v.GetChildrenVisitor("Key")
		n.Key.Walk(vv)
	}

	if n.Val != nil {
		vv := v.GetChildrenVisitor("Val")
		n.Val.Walk(vv)
	}

	v.LeaveNode(n)
}

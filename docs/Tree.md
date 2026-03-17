# Tree

This document covers the tree node types in Strux: `Node` and `NodeList`.

---

## Contents

- [Node](#node)
- [NodeList](#nodelist)

---

## Node

`Node` represents a single node in a **named tree**. Every node has:

- A **name** (a plain string identifier)
- A **qualified name** (dot-separated path from the root, e.g. `root.settings.database`)
- Arbitrary **content** (any PHP value)
- An ordered set of **child nodes** (`NodeList`)
- A reference to its **parent** (`null` for root nodes)

`Node` implements `ArrayAccess`, `IteratorAggregate`, and `JsonSerializable`.

### Construction

```php
use Wingman\Strux\Node;

$node = new Node();
$node = new Node(content: ['host' => 'localhost'], name: 'database');
$node = Node::from(content: 'hello', name: 'greeting');
```

### Name and Content

```php
$node->getName();             // 'database'
$node->getQualifiedName();    // 'root.settings.database'
$node->getContent();          // ['host' => 'localhost']
```

### Tree Position

```php
$node->isRoot();              // bool — no parent
$node->isLeaf();              // bool — no children
$node->getDepth();            // int — 0 for root
$node->getParent();           // ?Node
$node->getRoot();             // most-distant ancestor
```

### Managing Children

```php
// Add / replace a child:
$node->setChild('db', $dbNode);
$node['db'] = $dbNode;       // ArrayAccess (same effect)
$node->db = $dbNode;         // Magic setter (same effect)

// Get a child:
$node->getChild('db');        // ?Node
$node['db'];                  // ArrayAccess
$node->db;                    // Magic getter

// Check existence:
$node->hasChild('db');        // bool
isset($node['db']);
isset($node->db);

// Remove a child:
$node->removeChild('db');
unset($node['db']);
unset($node->db);

// Iterate children:
$node->getChildren();         // NodeList
foreach ($node as $child) { /* NodeList iterator */ }
```

All child-management methods that mutate the tree fire events via the Corvus event emitter:

| Event (Signal enum case) | When |
|---|---|
| `Signal::NODE_ADDED` | A child is added |
| `Signal::NODE_REMOVED` | A child is removed |

### Qualified-Path Traversal

Use dot-notation to reach deep descendants without manual child traversal:

```php
// Check existence:
$node->has('settings.database.host');   // bool

// Navigate to a descendant:
$node->get('settings.database');        // ?Node

// Create or update a value at a deep path:
$node->set('settings.database.host', 'localhost');
// Creates intermediate nodes automatically
```

### Import

`import()` populates the node from one or more associative iterables. Each entry becomes a child node whose name is the key:

```php
$node->import(
    ['host' => 'localhost', 'port' => 3306],
    ['timeout' => 30]
);

// $node now has children: host, port, timeout
```

### Export

```php
$node->export();              // ['root.settings.host' => 'localhost', ...]
// Flat array: qualified name → content for every descendant
```

### Clone (Deep Copy)

```php
$copy = clone $node;
// $copy is a full deep clone of the subtree
// $copy->getParent() === null (detached from original parent)
```

### Detach

```php
$node->detach();
// Removes the node from its parent's child list
// Does NOT auto-prune the parent
// Dispatches Signal::NODE_REMOVED
```

### JSON Serialisation

```php
json_encode($node);           // Recursive JSON representation of content + children
```

### Static Utilities

```php
Node::exportNode($node);                        // flat array of $node + descendants
Node::importIntoNode($node, $data);             // import helper for external use
Node::getQualifiedNameOfNode($node);            // qualified name of any node
```

---

## NodeList

`NodeList` is a concrete subclass of `TypedCollection` pre-configured to hold only `Node` instances. It is used internally as the child list of every `Node`, but can be used independently wherever a collection of `Node` objects is required.

### Construction

```php
use Wingman\Strux\NodeList;
use Wingman\Strux\Node;

$list = new NodeList();
$list = new NodeList([new Node('a'), new Node('b')]);
```

Attempting to add a non-`Node` item throws `InvalidArgumentException`:

```php
$list->add('not a node');    // throws InvalidArgumentException
```

Since `NodeList` extends `TypedCollection` which extends `Collection`, the full `Collection` API is available — `filter`, `map`, `find`, `sort`, `each`, `reduce`, etc.

### Example: Walking a Tree

```php
$root = new Node('application');
$root->set('database.host', 'localhost');
$root->set('database.port', 3306);
$root->set('cache.driver', 'redis');

// Navigate:
echo $root->get('database.host')?->getContent();   // 'localhost'

// Export flat:
print_r($root->export());
// [
//   'application.database.host' => 'localhost',
//   'application.database.port' => 3306,
//   'application.cache.driver'  => 'redis',
// ]

// Walk children via NodeList iteration:
foreach ($root as $section) {
    echo $section->getName() . "\n";  // 'database', 'cache'
}
```

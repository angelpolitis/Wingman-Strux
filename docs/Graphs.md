# Graphs

This document covers the graph types in Strux: `Graph`, `DirectedGraph`, and their `TypedGraph` / `TypedDirectedGraph` abstract variants.

---

## Contents

- [Graph (Undirected)](#graph-undirected)
- [DirectedGraph](#directedgraph)
- [Typed Variants](#typed-variants)
- [Functional Iterators](#functional-iterators)

---

## Graph (Undirected)

`Graph` represents an undirected graph. Internally it maintains:

- A **node registry** `id → value`
- A **symmetric adjacency list** `id → [neighbour → attributes]`

Because the adjacency list is symmetric, every undirected edge is retrievable in **O(1)** from either endpoint.

`Graph` implements `GraphInterface` (`Countable`, `IteratorAggregate`).

### Construction

```php
use Wingman\Strux\Graph;

$graph = new Graph();
$graph = new Graph(type: 'string');    // node values must be strings
$graph = Graph::withType('int');

// from() accepts a node map and a list of edge tuples:
$graph = Graph::from(
    nodes: ['a' => 'Paris', 'b' => 'London', 'c' => 'Berlin'],
    edges: [
        ['a', 'b', ['km' => 342]],  // [from, to, attributes (optional)]
        ['b', 'c', ['km' => 932]],
    ]
);
```

### Nodes

```php
$graph->addNode('a', 'Paris');        // adds or updates a node
$graph->getNode('a');                 // 'Paris', or null
$graph->hasNode('a');                 // bool
$graph->removeNode('a');              // also removes all edges incident to 'a'
$graph->getNodes();                   // ['a' => 'Paris', ...]
$graph->countNodes();                 // int
```

If `addEdge()` references a node ID that does not yet exist, that node is **auto-created** with a default value of `true`.

### Edges

```php
$graph->addEdge('a', 'b');                       // creates edge; stores empty attributes []
$graph->addEdge('a', 'b', ['km' => 342]);       // with attributes
$graph->getEdge('a', 'b');                       // ['km' => 342], or null
$graph->hasEdge('a', 'b');                       // bool (true from 'b' side too)
$graph->removeEdge('a', 'b');                    // removes from BOTH directions
$graph->getEdges();                              // [[from, to, attrs], ...]
$graph->countEdges();                            // int
```

### Neighbours

```php
$graph->getNeighbours('a');   // ['b' => ['km' => 342], 'c' => []] — adjacency map
```

### Querying

```php
$graph->getSize();         // node count
$graph->isEmpty();         // bool
$graph->getType();         // ?string (node value type)
```

---

## DirectedGraph

`DirectedGraph` extends `Graph` with **directed edge semantics**. `addEdge('a', 'b')` creates an edge `a → b` only — the reverse edge `b → a` is **not** created automatically. The class maintains an additional **reverse-adjacency index** for `getInNeighbours()` in **O(1)**.

`DirectedGraph` implements `DirectedGraphInterface` (which extends `GraphInterface`).

### Directed Edge Behaviour

```php
use Wingman\Strux\DirectedGraph;

$dg = new DirectedGraph();
$dg->addNode('a', 1)->addNode('b', 2)->addNode('c', 3);
$dg->addEdge('a', 'b')->addEdge('b', 'c');

$dg->hasEdge('a', 'b');   // true
$dg->hasEdge('b', 'a');   // false — not created automatically

$dg->removeEdge('a', 'b'); // removes a→b only; does NOT touch b→a if it existed
```

### In-Neighbours (Reverse Lookup)

```php
// Who points TO 'b'?
$dg->getInNeighbours('b');   // ['a' => []], i.e. 'a' has an edge pointing to 'b'

// What does 'b' point to? (outgoing)
$dg->getNeighbours('b');     // ['c' => []]
```

### Static Factory

```php
$dg = DirectedGraph::from(
    ['a' => null, 'b' => null, 'c' => null],
    [['a', 'b'], ['b', 'c'], ['a', 'c']]
);
```

### removeNode — Cascading Cleanup

When a node is removed, **both incoming and outgoing edges** are cleaned up:

```php
$dg->removeNode('b');
// Removes: a→b, b→c, and any edge from any other node pointing to 'b'
```

### `getEdges()` — Directed Only

Unlike the undirected `Graph`, `getEdges()` on a `DirectedGraph` returns each directed edge exactly once — no reverse duplicates.

---

## Typed Variants

`TypedGraph` and `TypedDirectedGraph` are abstract subclasses. Declare the `$type` property to constrain node values:

```php
use Wingman\Strux\TypedGraph;
use Wingman\Strux\TypedDirectedGraph;

class StringGraph extends TypedGraph {
    protected ?string $type = 'string';
}

class EntityGraph extends TypedDirectedGraph {
    protected ?string $type = Entity::class;
}

$g = new StringGraph();
$g->addNode('a', 'Paris');    // OK
$g->addNode('b', 42);         // throws InvalidArgumentException
```

Neither `TypedGraph` nor `TypedDirectedGraph` requires constructor arguments:

```php
$g = new StringGraph();
// or with initial type-checked nodes:
$g = StringGraph::from(['a' => 'Paris', 'b' => 'London'], [['a', 'b']]);
```

The `$type` constraint applies **to node values only** — edge attributes are always untyped arrays.

---

## Functional Iterators

Both `Graph` and `DirectedGraph` share the full functional iteration API operating over **nodes**:

```php
$graph->each(fn ($value, $id, $graph) => render($id, $value));
$graph->filter(fn ($value) => $value !== null); // new graph; retains edges between kept nodes
$graph->find(fn ($value) => $value === 'Paris');
$graph->reduce(fn ($carry, $value) => $carry . $value, '');
$graph->every(fn ($value) => is_string($value));
$graph->some(fn ($value) => strlen($value) > 5);
$graph->none(fn ($value) => is_null($value));
$graph->tap(fn ($g) => log($g->countNodes()));
```

Edge iteration:

```php
$graph->eachEdge(fn ($from, $to, $attrs, $graph) => record($from, $to, $attrs));
```

`filter()` returns a **subgraph** — only the matching nodes are included; edges are retained if both endpoints are in the filtered result.

---

## Summary: Graph vs DirectedGraph

| Feature | `Graph` | `DirectedGraph` |
| --- | --- | --- |
| Edge direction | Undirected (symmetric) | Directed (asymmetric) |
| `addEdge(a, b)` | Creates a↔b | Creates a→b only |
| `hasEdge(b, a)` after `addEdge(a,b)` | `true` | `false` |
| `removeEdge(a, b)` | Removes both directions | Removes a→b only |
| `getNeighbours(id)` | All adjacent nodes | Outgoing neighbours only |
| `getInNeighbours(id)` | Not available | Nodes pointing INTO id |
| `getEdges()` deduplication | Each undirected edge once | Each directed edge once |
| Interface | `GraphInterface` | `DirectedGraphInterface` |

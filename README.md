# Wingman — Strux

A library that provides a comprehensive, enterprise-grade suite of typed, immutable-capable data structures based on standard PHP primitives and SPL types. Every class follows identical conventions — named constructors, fluent mutation, optional type enforcement, functional iterators — so you can learn one and instantly understand all the others.

---

## Contents

- [Wingman — Strux](#wingman--strux)
  - [Contents](#contents)
  - [Requirements](#requirements)
  - [Installation](#installation)
  - [Quick Start](#quick-start)
  - [Structure Overview](#structure-overview)
  - [Typed Subclassing](#typed-subclassing)
  - [Functional Iterators](#functional-iterators)
  - [Documentation](#documentation)
  - [Changelog](#changelog)
  - [Licence](#licence)

---

## Requirements

- PHP 8.1 or higher
- No external runtime dependencies (the Argus bridge is optional)

---

## Installation

```bash
composer require wingman/strux
```

---

## Quick Start

```php
use Wingman\Strux\Collection;
use Wingman\Strux\Stack;
use Wingman\Strux\HashMap;
use Wingman\Strux\Graph;

// General-purpose ordered collection
$numbers = Collection::from([3, 1, 4, 1, 5, 9]);
$evens   = $numbers->filter(fn ($n) => $n % 2 === 0); // Collection([4])

// LIFO stack
$stack = new Stack();
$stack->push('a', 'b', 'c');
echo $stack->pop(); // 'c'

// Key-value map with any key type
$map = new HashMap();
$map->set('host', 'localhost')->set('port', 3306);
echo $map->get('host'); // 'localhost'

// Undirected graph
$graph = Graph::from(
    ['a' => 'Paris', 'b' => 'London', 'c' => 'Berlin'],
    [['a', 'b', ['km' => 340]], ['b', 'c', ['km' => 930]]]
);
var_dump($graph->getNeighbours('b')); // ['a' => [...], 'c' => [...]]
```

---

## Structure Overview

| Category | Classes |
| --- | --- |
| **Collections** | `Collection`, `TypedCollection`, `EnumCollection`, `LazyCollection`, `TypedLazyCollection` |
| **Sequences** | `Stack`, `Queue`, `PriorityQueue`, `CircularBuffer`, `SortedList` + Typed variants |
| **Sets** | `Set`, `TypedSet` |
| **Maps** | `HashMap`, `BidirectionalMap`, `MultiMap`, `WeakReferenceMap` + Typed variants |
| **Caches** | `LruCache`, `TypedLruCache` |
| **Prefix Structures** | `Trie`, `TypedTrie` |
| **Graphs** | `Graph`, `DirectedGraph`, `TypedGraph`, `TypedDirectedGraph` |
| **Tree Nodes** | `Node`, `NodeList` |

All concrete classes except `WeakReferenceMap` and `Node` support:

- **Type enforcement** — pass a type string (primitive or FQCN) to the constructor or use named constructors such as `withType('int')`.
- **Immutability** — call `freeze()` to permanently prevent mutations; check with `isFrozen()`.
- **Named constructors** — every class exposes a `static from()` method alongside any domain-specific variants.

---

## Typed Subclassing

When you want a reusable named type (e.g. `IntStack`, `UserCollection`) rather than a one-off runtime constraint, extend any `Typed*` abstract class and declare the `$type` property:

```php
use Wingman\Strux\TypedStack;
use Wingman\Strux\TypedCollection;

class IntStack extends TypedStack {
    protected ?string $type = 'int';
}

class UserCollection extends TypedCollection {
    protected ?string $type = User::class;
}

$stack = new IntStack();
$stack->push(1, 2, 3);        // OK
$stack->push('hello');         // throws InvalidArgumentException

$users = new UserCollection();
$users->add(new User('Alice')); // OK
$users->add(new stdClass());    // throws InvalidArgumentException
```

For classes whose constructor requires arguments (e.g. `TypedCircularBuffer`, `TypedLruCache`), pass them when instantiating:

```php
use Wingman\Strux\TypedLruCache;

class IntCache extends TypedLruCache {
    protected ?string $type = 'int';
}

$cache = new IntCache(capacity: 100);
```

See [docs/Typed-Subclassing.md](docs/Typed-Subclassing.md) for complete guidance.

---

## Functional Iterators

Every class exposes a consistent set of higher-order methods:

| Method | Description |
| --- | --- |
| `each(callable $cb)` | Iterates; returns `$this` for chaining |
| `filter(callable $predicate)` | New instance with matching items |
| `map(callable $cb)` | Transforms items; returns an array |
| `find(callable $predicate)` | First matching item, or `null` |
| `reduce(callable $cb, mixed $initial)` | Fold/accumulation |
| `every(callable $predicate)` | `true` if all items match |
| `some(callable $predicate)` | `true` if any item matches |
| `none(callable $predicate)` | `true` if no item matches |
| `tap(callable $cb)` | Side-effectful inspection; returns `$this` |

`LazyCollection` applies `filter`, `map`, `take`, `skip`, and `chunk` lazily — no items are evaluated until the pipeline is consumed.

---

## Documentation

| Document | Covers |
| --- | --- |
| [docs/Collections.md](docs/Collections.md) | `Collection`, `TypedCollection`, `EnumCollection`, `LazyCollection` |
| [docs/Sequences.md](docs/Sequences.md) | `Stack`, `Queue`, `PriorityQueue`, `CircularBuffer`, `SortedList` |
| [docs/Maps.md](docs/Maps.md) | `HashMap`, `BidirectionalMap`, `MultiMap`, `WeakReferenceMap` |
| [docs/Graphs.md](docs/Graphs.md) | `Graph`, `DirectedGraph` |
| [docs/Tree.md](docs/Tree.md) | `Node`, `NodeList` |
| [docs/Typed-Subclassing.md](docs/Typed-Subclassing.md) | Typed abstract subclasses, `LruCache`, `Trie` |
| [docs/API-Reference.md](docs/API-Reference.md) | Complete method signatures for every class |

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

---

## Licence

This project is licensed under the **Mozilla Public License 2.0 (MPL 2.0)**.

Wingman Strux is part of the **Wingman Framework**, Copyright (c) 2018–2026 Angel Politis.

For the full licence text, please see the [LICENSE](LICENSE) file.

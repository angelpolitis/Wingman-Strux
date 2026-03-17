# API Reference

Complete method signatures for every class in Strux. All classes reside in the `Wingman\Strux` namespace unless noted otherwise.

---

## Contents

- [API Reference](#api-reference)
  - [Contents](#contents)
  - [Interfaces](#interfaces)
    - [`SequenceInterface`](#sequenceinterface)
    - [`SetInterface`](#setinterface)
    - [`MapInterface`](#mapinterface)
    - [`GraphInterface`](#graphinterface)
    - [`DirectedGraphInterface`](#directedgraphinterface)
  - [Collection](#collection)
    - [Mutation](#mutation)
    - [Access](#access)
    - [Query](#query)
    - [Functional](#functional)
    - [Aggregation](#aggregation)
    - [Transformation](#transformation)
    - [Named Constructors](#named-constructors)
  - [TypedCollection](#typedcollection)
  - [EnumCollection](#enumcollection)
  - [LazyCollection](#lazycollection)
    - [Lazy Stages](#lazy-stages)
    - [Consuming Operations](#consuming-operations)
    - [Named Constructors](#named-constructors-1)
  - [TypedLazyCollection](#typedlazycollection)
  - [Stack](#stack)
  - [TypedStack](#typedstack)
  - [Queue](#queue)
  - [TypedQueue](#typedqueue)
  - [PriorityQueue](#priorityqueue)
  - [TypedPriorityQueue](#typedpriorityqueue)
  - [Set](#set)
  - [TypedSet](#typedset)
  - [SortedList](#sortedlist)
  - [TypedSortedList](#typedsortedlist)
  - [CircularBuffer](#circularbuffer)
  - [TypedCircularBuffer](#typedcircularbuffer)
  - [HashMap](#hashmap)
    - [Mutation](#mutation-1)
    - [Access](#access-1)
    - [Query](#query-1)
    - [Functional](#functional-1)
    - [Aggregation](#aggregation-1)
    - [Transformation](#transformation-1)
    - [Named Constructors](#named-constructors-2)
  - [TypedHashMap](#typedhashmap)
  - [BidirectionalMap](#bidirectionalmap)
  - [TypedBidirectionalMap](#typedbidirectionalmap)
  - [MultiMap](#multimap)
  - [TypedMultiMap](#typedmultimap)
  - [LruCache](#lrucache)
  - [TypedLruCache](#typedlrucache)
  - [Trie](#trie)
  - [TypedTrie](#typedtrie)
  - [WeakReferenceMap](#weakreferencemap)
  - [Graph](#graph)
    - [Mutation](#mutation-2)
    - [Access](#access-2)
    - [Query](#query-2)
    - [Functional](#functional-2)
    - [Named Constructors](#named-constructors-3)
  - [TypedGraph](#typedgraph)
  - [DirectedGraph](#directedgraph)
  - [TypedDirectedGraph](#typeddirectedgraph)
  - [Node](#node)
    - [Magic](#magic)
    - [Child Management](#child-management)
    - [Tree Position](#tree-position)
    - [Qualified-Path Traversal](#qualified-path-traversal)
    - [Content](#content)
    - [Bulk Operations](#bulk-operations)
    - [ArrayAccess](#arrayaccess)
    - [Named Constructors / Static Utilities](#named-constructors--static-utilities)
  - [NodeList](#nodelist)

---

## Interfaces

Namespace: `Wingman\Strux\Interfaces`

---

### `SequenceInterface`

Extends `Countable`, `IteratorAggregate`.

| Signature | Returns |
| ----------- | --------- |
| `getSize()` | `int` |
| `isEmpty()` | `bool` |

---

### `SetInterface`

Extends `Countable`, `IteratorAggregate`.

| Signature | Returns |
| ----------- | --------- |
| `add(mixed ...$items)` | `static` |
| `contains(mixed $item)` | `bool` |
| `diff(SetInterface $other)` | `static` |
| `getSize()` | `int` |
| `intersect(SetInterface $other)` | `static` |
| `isEmpty()` | `bool` |
| `remove(mixed $item)` | `static` |
| `union(SetInterface $other)` | `static` |

---

### `MapInterface`

Extends `Countable`.

| Signature | Returns |
| ----------- | --------- |
| `get(mixed $key)` | `mixed` |
| `getKeys()` | `array` |
| `getSize()` | `int` |
| `getValues()` | `array` |
| `has(mixed $key)` | `bool` |
| `remove(mixed $key)` | `static` |
| `set(mixed $key, mixed $value)` | `static` |

---

### `GraphInterface`

Extends `Countable`, `IteratorAggregate`.

| Signature | Returns |
| ----------- | --------- |
| `addEdge(int\|string $from, int\|string $to, array $attributes = [])` | `static` |
| `addNode(int\|string $id, mixed $value = true)` | `static` |
| `count()` | `int` |
| `countEdges()` | `int` |
| `countNodes()` | `int` |
| `each(callable $callback)` | `static` |
| `eachEdge(callable $callback)` | `static` |
| `every(callable $predicate)` | `bool` |
| `filter(callable $predicate)` | `static` |
| `find(callable $predicate)` | `mixed` |
| `getEdge(int\|string $from, int\|string $to)` | `?array` |
| `getEdges()` | `array` |
| `getIterator()` | `Traversable` |
| `getNeighbours(int\|string $id)` | `array` |
| `getNode(int\|string $id)` | `mixed` |
| `getNodes()` | `array` |
| `getSize()` | `int` |
| `getType()` | `?string` |
| `hasEdge(int\|string $from, int\|string $to)` | `bool` |
| `hasNode(int\|string $id)` | `bool` |
| `isEmpty()` | `bool` |
| `none(callable $predicate)` | `bool` |
| `reduce(mixed $initial, callable $callback)` | `mixed` |
| `removeEdge(int\|string $from, int\|string $to)` | `static` |
| `removeNode(int\|string $id)` | `static` |
| `some(callable $predicate)` | `bool` |
| `tap(callable $callback)` | `static` |

---

### `DirectedGraphInterface`

Extends `GraphInterface`.

| Signature | Returns |
| ----------- | --------- |
| `getInNeighbours(int\|string $id)` | `array` |

---

## Collection

**Extends:** —  
**Implements:** `ArrayAccess`, `Countable`, `IteratorAggregate`, `SequenceInterface`  
**Constructor:** `__construct(array $items = [], ?string $type = null, ?int $cap = null, bool $frozen = false)`

### Mutation

| Signature | Returns |
| ----------- | --------- |
| `add(mixed ...$items)` | `static` |
| `freeze()` | `static` |
| `offsetSet(mixed $offset, mixed $value)` | `void` |
| `offsetUnset(mixed $offset)` | `void` |
| `remove(int $index)` | `static` |
| `unfreeze()` | `static` |

### Access

| Signature | Returns |
| ----------- | --------- |
| `get(int $index)` | `mixed` |
| `getAll()` | `array` |
| `getCap()` | `?int` |
| `getFirst()` | `mixed` |
| `getIterator()` | `Traversable` |
| `getLast()` | `mixed` |
| `getSize()` | `int` |
| `getType()` | `?string` |
| `offsetExists(mixed $offset)` | `bool` |
| `offsetGet(mixed $offset)` | `mixed` |

### Query

| Signature | Returns |
| ----------- | --------- |
| `contains(mixed $item)` | `bool` |
| `count()` | `int` |
| `find(callable $predicate)` | `mixed` |
| `findLast(callable $predicate)` | `mixed` |
| `has(mixed $item, ?callable $comparator = null)` | `bool` |
| `hasCap()` | `bool` |
| `indexOf(mixed $item, ?callable $comparator = null)` | `int` |
| `isEmpty()` | `bool` |
| `isFrozen()` | `bool` |
| `lastIndexOf(mixed $item, ?callable $comparator = null)` | `int` |

### Functional

| Signature | Returns |
| ----------- | --------- |
| `each(callable $callback)` | `static` |
| `every(callable $predicate)` | `bool` |
| `filter(callable $predicate)` | `static` |
| `map(callable $callback)` | `array` |
| `none(callable $predicate)` | `bool` |
| `reduce(callable $callback, mixed $initial = null)` | `mixed` |
| `reduceRight(callable $callback, mixed $initial = null)` | `mixed` |
| `some(callable $predicate)` | `bool` |
| `tap(callable $callback)` | `static` |

### Aggregation

| Signature | Returns |
| ----------- | --------- |
| `getMax(?callable $keySelector = null)` | `mixed` |
| `getMin(?callable $keySelector = null)` | `mixed` |
| `sum(?callable $callback = null)` | `int\|float` |

### Transformation

| Signature | Returns |
| ----------- | --------- |
| `chunk(int $size)` | `array` |
| `deduplicate(?callable $keySelector = null)` | `static` |
| `groupBy(callable $callback)` | `array` |
| `orderBy(callable $callback)` | `static` |
| `orderByInPlace(callable $callback)` | `static` |
| `partition(callable $predicate)` | `array` |
| `reverse()` | `static` |
| `slice(int $offset, ?int $length = null)` | `static` |
| `sort(callable $comparator)` | `static` |
| `sortInPlace(callable $comparator)` | `static` |
| `with(mixed ...$items)` | `static` |
| `without(mixed ...$targets)` | `static` |
| `withoutIndices(int ...$indices)` | `static` |

### Named Constructors

| Signature | Returns |
| ----------- | --------- |
| `static from(array $items, ?string $type = null, ?int $cap = null, bool $frozen = false)` | `static` |
| `static withCap(int $cap)` | `static` |
| `static withItems(array $items)` | `static` |
| `static withType(string $type)` | `static` |

---

## TypedCollection

**Extends:** `Collection`  
**Constructor:** `__construct(array $items = [], ?int $cap = null, bool $frozen = false)`  
**Abstract property:** `protected ?string $type`

All `Collection` methods inherited. No additional public methods.

---

## EnumCollection

**Extends:** `Collection`  
**Constructor:** `__construct(string $enumClass, array $items = [], ?int $cap = null, bool $frozen = false)`

Inherits all `Collection` methods. Additional methods:

| Signature | Returns |
| ----------- | --------- |
| `addValue(int\|string ...$values)` | `static` |
| `getEnumClass()` | `string` |
| `getNames()` | `array` |
| `getValues()` | `array` |
| `hasValue(int\|string $value)` | `bool` |
| `static forEnum(string $enumClass)` | `static` |
| `static from(array $items, ?string $type = null, ?int $cap = null, bool $frozen = false)` | `static` |
| `static fromEnum(string $enumClass, array $items = [])` | `static` |

---

## LazyCollection

**Implements:** `IteratorAggregate`  
**Constructor:** `__construct(callable|iterable $source)`

### Lazy Stages

These return a new `LazyCollection` instance; no work is done until the pipeline is consumed.

| Signature | Returns |
| ----------- | --------- |
| `chunk(int $size)` | `static` |
| `filter(callable $predicate)` | `static` |
| `map(callable $callback)` | `static` |
| `skip(int $offset)` | `static` |
| `take(int $limit)` | `static` |

### Consuming Operations

| Signature | Returns |
| ----------- | --------- |
| `collect()` | `Collection` |
| `count()` | `int` |
| `each(callable $callback)` | `static` |
| `every(callable $predicate)` | `bool` |
| `find(callable $predicate)` | `mixed` |
| `getFirst()` | `mixed` |
| `getIterator()` | `Traversable` |
| `getSize()` | `int` |
| `isEmpty()` | `bool` |
| `none(callable $predicate)` | `bool` |
| `reduce(callable $callback, mixed $initial = null)` | `mixed` |
| `some(callable $predicate)` | `bool` |
| `tap(callable $callback)` | `static` |
| `toArray()` | `array` |

### Named Constructors

| Signature | Returns |
| ----------- | --------- |
| `static make(callable\|iterable $source)` | `static` |
| `static range(int $start = 0, int $step = 1)` | `static` |
| `static repeat(mixed $value, int $times = -1)` | `static` |

---

## TypedLazyCollection

**Extends:** `LazyCollection`  
**Abstract property:** `protected string $type`

Overrides `getIterator()` to validate each yielded item against `$type`. All other methods inherited.

---

## Stack

**Implements:** `SequenceInterface`  
**Constructor:** `__construct(array $items = [], ?string $type = null, ?int $cap = null, bool $frozen = false)`

| Signature | Returns |
| ----------- | --------- |
| `count()` | `int` |
| `each(callable $callback)` | `static` |
| `every(callable $predicate)` | `bool` |
| `find(callable $predicate)` | `mixed` |
| `freeze()` | `static` |
| `getCap()` | `?int` |
| `getIterator()` | `Traversable` |
| `getSize()` | `int` |
| `getType()` | `?string` |
| `hasCap()` | `bool` |
| `isEmpty()` | `bool` |
| `isFrozen()` | `bool` |
| `none(callable $predicate)` | `bool` |
| `peek()` | `mixed` |
| `pop()` | `mixed` |
| `push(mixed ...$items)` | `static` |
| `reduce(callable $callback, mixed $initial = null)` | `mixed` |
| `some(callable $predicate)` | `bool` |
| `tap(callable $callback)` | `static` |
| `toArray()` | `array` |
| `unfreeze()` | `static` |
| `static from(array $items, ?string $type = null, ?int $cap = null, bool $frozen = false)` | `static` |
| `static withCap(int $cap)` | `static` |
| `static withType(string $type)` | `static` |

---

## TypedStack

**Extends:** `Stack`  
**Constructor:** `__construct(array $items = [], ?int $cap = null, bool $frozen = false)`  
**Abstract property:** `protected ?string $type`

`from()` omits `$type`. `withType()` is disabled. All `Stack` methods inherited.

---

## Queue

**Implements:** `SequenceInterface`  
**Constructor:** `__construct(array $items = [], ?string $type = null, ?int $cap = null, bool $frozen = false)`

| Signature | Returns |
| ----------- | --------- |
| `count()` | `int` |
| `dequeue()` | `mixed` |
| `each(callable $callback)` | `static` |
| `enqueue(mixed ...$items)` | `static` |
| `every(callable $predicate)` | `bool` |
| `find(callable $predicate)` | `mixed` |
| `freeze()` | `static` |
| `getCap()` | `?int` |
| `getIterator()` | `Traversable` |
| `getSize()` | `int` |
| `getType()` | `?string` |
| `hasCap()` | `bool` |
| `isEmpty()` | `bool` |
| `isFrozen()` | `bool` |
| `none(callable $predicate)` | `bool` |
| `peek()` | `mixed` |
| `reduce(callable $callback, mixed $initial = null)` | `mixed` |
| `some(callable $predicate)` | `bool` |
| `tap(callable $callback)` | `static` |
| `toArray()` | `array` |
| `unfreeze()` | `static` |
| `static from(array $items, ?string $type = null, ?int $cap = null, bool $frozen = false)` | `static` |
| `static withCap(int $cap)` | `static` |
| `static withType(string $type)` | `static` |

---

## TypedQueue

**Extends:** `Queue`  
**Constructor:** `__construct(array $items = [], ?int $cap = null, bool $frozen = false)`  
**Abstract property:** `protected ?string $type`

`from()` omits `$type`. `withType()` is disabled. All `Queue` methods inherited.

---

## PriorityQueue

**Implements:** `SequenceInterface`  
**Constructor:** `__construct(?string $type = null)`

| Signature | Returns |
| ----------- | --------- |
| `count()` | `int` |
| `dequeue()` | `mixed` |
| `each(callable $callback)` | `static` |
| `enqueue(mixed $item, int $priority = 0)` | `static` |
| `every(callable $predicate)` | `bool` |
| `find(callable $predicate)` | `mixed` |
| `getIterator()` | `Traversable` |
| `getSize()` | `int` |
| `getType()` | `?string` |
| `isEmpty()` | `bool` |
| `none(callable $predicate)` | `bool` |
| `peek()` | `mixed` |
| `reduce(callable $callback, mixed $initial = null)` | `mixed` |
| `some(callable $predicate)` | `bool` |
| `tap(callable $callback)` | `static` |
| `toArray()` | `array` |
| `static from(array $items, ?string $type = null)` | `static` |
| `static withType(string $type)` | `static` |

---

## TypedPriorityQueue

**Extends:** `PriorityQueue`  
**Constructor:** `__construct()`  
**Abstract property:** `protected ?string $type`

`from()` omits `$type`. `withType()` is disabled. All `PriorityQueue` methods inherited.

---

## Set

**Implements:** `SetInterface` (`Countable`, `IteratorAggregate`)  
**Constructor:** `__construct(array $items = [], ?string $type = null, bool $frozen = false)`

| Signature | Returns |
| ----------- | --------- |
| `add(mixed ...$items)` | `static` |
| `contains(mixed $item)` | `bool` |
| `count()` | `int` |
| `diff(SetInterface $other)` | `static` |
| `each(callable $callback)` | `static` |
| `every(callable $predicate)` | `bool` |
| `filter(callable $predicate)` | `static` |
| `freeze()` | `static` |
| `getIterator()` | `Traversable` |
| `getSize()` | `int` |
| `getType()` | `?string` |
| `intersect(SetInterface $other)` | `static` |
| `isEmpty()` | `bool` |
| `isFrozen()` | `bool` |
| `none(callable $predicate)` | `bool` |
| `reduce(callable $callback, mixed $initial = null)` | `mixed` |
| `remove(mixed $item)` | `static` |
| `some(callable $predicate)` | `bool` |
| `tap(callable $callback)` | `static` |
| `toArray()` | `array` |
| `unfreeze()` | `static` |
| `union(SetInterface $other)` | `static` |
| `static from(array $items, ?string $type = null, bool $frozen = false)` | `static` |
| `static withType(string $type)` | `static` |

---

## TypedSet

**Extends:** `Set`  
**Constructor:** `__construct(array $items = [], bool $frozen = false)`  
**Abstract property:** `protected ?string $type`

`from()` omits `$type`. `withType()` is disabled. All `Set` methods inherited.

---

## SortedList

**Implements:** `SequenceInterface`  
**Constructor:** `__construct(array $items = [], ?callable $comparator = null, ?string $type = null)`

Items are kept in ascending sorted order at all times. The default comparator uses the spaceship operator (`<=>`).

| Signature | Returns |
| ----------- | --------- |
| `add(mixed ...$items)` | `static` |
| `count()` | `int` |
| `each(callable $callback)` | `static` |
| `every(callable $predicate)` | `bool` |
| `filter(callable $predicate)` | `static` |
| `find(callable $predicate)` | `mixed` |
| `getComparator()` | `?callable` |
| `getFirst()` | `mixed` |
| `getIterator()` | `Traversable` |
| `getLast()` | `mixed` |
| `getSize()` | `int` |
| `getType()` | `?string` |
| `has(mixed $item)` | `bool` |
| `indexOf(mixed $item)` | `int` |
| `isEmpty()` | `bool` |
| `map(callable $callback)` | `array` |
| `none(callable $predicate)` | `bool` |
| `reduce(mixed $initial, callable $callback)` | `mixed` |
| `remove(mixed $item)` | `static` |
| `removeAt(int $index)` | `static` |
| `some(callable $predicate)` | `bool` |
| `tap(callable $callback)` | `static` |
| `toArray()` | `array` |
| `static from(array $items, ?callable $comparator = null, ?string $type = null)` | `static` |
| `static withComparator(callable $comparator)` | `static` |
| `static withType(string $type)` | `static` |

---

## TypedSortedList

**Extends:** `SortedList`  
**Abstract property:** `protected ?string $type`

`from()` omits `$type`. `withType()` is disabled. All `SortedList` methods inherited.

---

## CircularBuffer

**Implements:** `SequenceInterface`  
**Constructor:** `__construct(int $cap, ?string $type = null)`

A fixed-capacity ring buffer. Writing to a full buffer overwrites the oldest entry.

| Signature | Returns |
| ----------- | --------- |
| `count()` | `int` |
| `each(callable $callback)` | `static` |
| `every(callable $predicate)` | `bool` |
| `find(callable $predicate)` | `mixed` |
| `flush()` | `static` |
| `getCap()` | `int` |
| `getIterator()` | `Traversable` |
| `getSize()` | `int` |
| `getType()` | `?string` |
| `isEmpty()` | `bool` |
| `isFull()` | `bool` |
| `none(callable $predicate)` | `bool` |
| `peek()` | `mixed` |
| `read()` | `mixed` |
| `reduce(callable $callback, mixed $initial = null)` | `mixed` |
| `some(callable $predicate)` | `bool` |
| `tap(callable $callback)` | `static` |
| `toArray()` | `array` |
| `write(mixed $item)` | `static` |
| `static from(array $items, ?string $type = null, ?int $cap = null)` | `static` |

---

## TypedCircularBuffer

**Extends:** `CircularBuffer`  
**Constructor:** `__construct(int $cap)`  
**Abstract property:** `protected ?string $type`

`from()` omits `$type`. All `CircularBuffer` methods inherited.

---

## HashMap

**Implements:** `ArrayAccess`, `Countable`, `Iterator`, `JsonSerializable`, `MapInterface`  
**Constructor:** `__construct(array $data = [], ?string $type = null)`

Keys may be of any type; non-scalar keys are serialised internally.

### Mutation

| Signature | Returns |
| ----------- | --------- |
| `clear()` | `static` |
| `flip()` | `static` |
| `mapKeys(callable $callback)` | `static` |
| `offsetSet(mixed $offset, mixed $value)` | `void` |
| `offsetUnset(mixed $offset)` | `void` |
| `remove(mixed $key)` | `static` |
| `set(mixed $key, mixed $value)` | `static` |

### Access

| Signature | Returns |
| ----------- | --------- |
| `current()` | `mixed` |
| `get(mixed $key)` | `mixed` |
| `getKeys(bool $serialised = false)` | `array` |
| `getSize()` | `int` |
| `getValues()` | `array` |
| `key()` | `mixed` |
| `next()` | `void` |
| `offsetExists(mixed $offset)` | `bool` |
| `offsetGet(mixed $offset)` | `mixed` |
| `rewind()` | `void` |
| `valid()` | `bool` |

### Query

| Signature | Returns |
| ----------- | --------- |
| `contains(mixed $value)` | `bool` |
| `count()` | `int` |
| `find(callable $predicate)` | `mixed` |
| `findKey(callable $predicate)` | `mixed` |
| `has(mixed $key)` | `bool` |
| `isEmpty()` | `bool` |

### Functional

| Signature | Returns |
| ----------- | --------- |
| `each(callable $callback)` | `static` |
| `every(callable $predicate)` | `bool` |
| `filter(callable\|null $predicate = null)` | `static` |
| `filterKey(callable\|null $predicate = null)` | `static` |
| `map(callable $callback)` | `static` |
| `none(callable $predicate)` | `bool` |
| `reduce(callable $callback, mixed $initialValue = null)` | `mixed` |
| `some(callable $predicate)` | `bool` |
| `tap(callable $callback)` | `static` |

### Aggregation

| Signature | Returns |
| ----------- | --------- |
| `getMax(?callable $keySelector = null)` | `mixed` |
| `getMin(?callable $keySelector = null)` | `mixed` |
| `sum(?callable $callback = null)` | `int\|float` |

### Transformation

| Signature | Returns |
| ----------- | --------- |
| `deduplicate(?callable $keySelector = null)` | `static` |
| `groupBy(callable $callback)` | `array` |
| `jsonSerialize()` | `array` |
| `merge(HashMap ...$others)` | `static` |
| `partition(callable $predicate)` | `array` |

### Named Constructors

| Signature | Returns |
| ----------- | --------- |
| `static from(array $data, ?string $type = null)` | `static` |

---

## TypedHashMap

**Extends:** `HashMap`  
**Constructor:** `__construct(array $data = [])`  
**Abstract property:** `protected ?string $type`

`from()` omits `$type`. All `HashMap` methods inherited.

---

## BidirectionalMap

**Implements:** `Countable`, `IteratorAggregate`  
**Constructor:** `__construct(array $data = [], ?string $type = null)`

Maintains a one-to-one relationship between keys and values. Both forward (`key → value`) and reverse (`value → key`) lookups run in O(1).

| Signature | Returns |
| ----------- | --------- |
| `count()` | `int` |
| `each(callable $callback)` | `static` |
| `every(callable $predicate)` | `bool` |
| `find(callable $predicate)` | `mixed` |
| `get(int\|string $key)` | `mixed` |
| `getIterator()` | `Traversable` |
| `getKey(int\|string $value)` | `mixed` |
| `getKeys()` | `array` |
| `getSize()` | `int` |
| `getType()` | `?string` |
| `getValues()` | `array` |
| `has(int\|string $key)` | `bool` |
| `hasValue(int\|string $value)` | `bool` |
| `isEmpty()` | `bool` |
| `none(callable $predicate)` | `bool` |
| `remove(int\|string $key)` | `static` |
| `removeByValue(int\|string $value)` | `static` |
| `set(int\|string $key, int\|string $value)` | `static` |
| `some(callable $predicate)` | `bool` |
| `tap(callable $callback)` | `static` |
| `toArray()` | `array` |
| `static from(array $data, ?string $type = null)` | `static` |
| `static withType(string $type)` | `static` |

---

## TypedBidirectionalMap

**Extends:** `BidirectionalMap`  
**Abstract property:** `protected ?string $type`

`from()` omits `$type`. `withType()` is disabled. All `BidirectionalMap` methods inherited.

---

## MultiMap

**Implements:** `Countable`, `IteratorAggregate`  
**Constructor:** `__construct(array $data = [], ?string $type = null)`

Each key maps to a list of values. `set()` appends; `replace()` overwrites the entire list for a key.

| Signature | Returns |
| ----------- | --------- |
| `count()` | `int` |
| `countAll()` | `int` |
| `each(callable $callback)` | `static` |
| `every(callable $predicate)` | `bool` |
| `find(callable $predicate)` | `?array` |
| `flatten()` | `Collection` |
| `get(int\|string $key)` | `array` |
| `getFirst(int\|string $key)` | `mixed` |
| `getIterator()` | `Traversable` |
| `getKeys()` | `array` |
| `getSize()` | `int` |
| `getType()` | `?string` |
| `getValues()` | `array` |
| `has(int\|string $key)` | `bool` |
| `hasValue(int\|string $key, mixed $value)` | `bool` |
| `isEmpty()` | `bool` |
| `none(callable $predicate)` | `bool` |
| `remove(int\|string $key)` | `static` |
| `removeValue(int\|string $key, mixed $value)` | `static` |
| `replace(int\|string $key, mixed ...$values)` | `static` |
| `set(int\|string $key, mixed ...$values)` | `static` |
| `some(callable $predicate)` | `bool` |
| `tap(callable $callback)` | `static` |
| `toArray()` | `array` |
| `static from(array $data, ?string $type = null)` | `static` |
| `static withType(string $type)` | `static` |

---

## TypedMultiMap

**Extends:** `MultiMap`  
**Abstract property:** `protected ?string $type`

`from()` omits `$type`. `withType()` is disabled. All `MultiMap` methods inherited.

---

## LruCache

**Implements:** `Countable`, `IteratorAggregate`  
**Constructor:** `__construct(int $cap, ?string $type = null)`

Least-recently-used cache. When the capacity is exceeded, the least recently accessed entry is evicted automatically.

| Signature | Returns |
| ----------- | --------- |
| `count()` | `int` |
| `each(callable $callback)` | `static` |
| `evict(int\|string $key)` | `static` |
| `every(callable $predicate)` | `bool` |
| `find(callable $predicate)` | `mixed` |
| `get(int\|string $key)` | `mixed` |
| `getCap()` | `int` |
| `getIterator()` | `Traversable` |
| `getKeys()` | `array` |
| `getSize()` | `int` |
| `getType()` | `?string` |
| `getValues()` | `array` |
| `has(int\|string $key)` | `bool` |
| `isEmpty()` | `bool` |
| `isFull()` | `bool` |
| `none(callable $predicate)` | `bool` |
| `put(int\|string $key, mixed $value)` | `static` |
| `some(callable $predicate)` | `bool` |
| `tap(callable $callback)` | `static` |
| `toArray()` | `array` |
| `static from(array $data, ?int $cap = null, ?string $type = null)` | `static` |
| `static withCap(int $cap)` | `static` |

---

## TypedLruCache

**Extends:** `LruCache`  
**Constructor:** `__construct(int $cap)`  
**Abstract property:** `protected ?string $type`

`from()` omits `$type`. All `LruCache` methods inherited.

---

## Trie

**Implements:** `Countable`, `IteratorAggregate`  
**Constructor:** `__construct(?string $type = null)`

A prefix tree for string keys with associated values.

| Signature | Returns |
| ----------- | --------- |
| `count()` | `int` |
| `each(callable $callback)` | `static` |
| `every(callable $predicate)` | `bool` |
| `find(callable $predicate)` | `mixed` |
| `get(string $word)` | `mixed` |
| `getIterator()` | `Traversable` |
| `getKeys()` | `array` |
| `getSize()` | `int` |
| `getType()` | `?string` |
| `getValues()` | `array` |
| `has(string $word)` | `bool` |
| `hasPrefix(string $prefix)` | `bool` |
| `isEmpty()` | `bool` |
| `none(callable $predicate)` | `bool` |
| `remove(string $word)` | `static` |
| `set(string $word, mixed $value = true)` | `static` |
| `some(callable $predicate)` | `bool` |
| `tap(callable $callback)` | `static` |
| `toArray()` | `array` |
| `withPrefix(string $prefix)` | `static` |
| `static from(array $data, ?string $type = null)` | `static` |
| `static withType(string $type)` | `static` |

---

## TypedTrie

**Extends:** `Trie`  
**Abstract property:** `protected ?string $type`

`from()` omits `$type`. `withType()` is disabled. All `Trie` methods inherited.

---

## WeakReferenceMap

**Implements:** `Countable`, `IteratorAggregate`, `MapInterface`  
**Constructor:** `__construct()`

Keys must be objects. Entries do not prevent garbage collection of their keys. Passing a non-object key throws `InvalidArgumentException`.

| Signature | Returns |
| ----------- | --------- |
| `clear()` | `static` |
| `contains(mixed $value)` | `bool` |
| `count()` | `int` |
| `each(callable $callback)` | `static` |
| `get(mixed $key)` | `mixed` |
| `getIterator()` | `Traversable` |
| `getKeys()` | `array` |
| `getSize()` | `int` |
| `getValues()` | `array` |
| `has(mixed $key)` | `bool` |
| `isEmpty()` | `bool` |
| `remove(mixed $key)` | `static` |
| `set(mixed $key, mixed $value)` | `static` |
| `tap(callable $callback)` | `static` |
| `static from(array $entries)` | `static` |

---

## Graph

**Implements:** `GraphInterface` (`Countable`, `IteratorAggregate`)  
**Constructor:** `__construct(?string $type = null)`

An undirected graph. Node values may be typed.

### Mutation

| Signature | Returns |
| ----------- | --------- |
| `addEdge(int\|string $from, int\|string $to, array $attributes = [])` | `static` |
| `addNode(int\|string $id, mixed $value = true)` | `static` |
| `removeEdge(int\|string $from, int\|string $to)` | `static` |
| `removeNode(int\|string $id)` | `static` |

### Access

| Signature | Returns |
| ----------- | --------- |
| `getEdge(int\|string $from, int\|string $to)` | `?array` |
| `getEdges()` | `array` |
| `getIterator()` | `Traversable` |
| `getNeighbours(int\|string $id)` | `array` |
| `getNode(int\|string $id)` | `mixed` |
| `getNodes()` | `array` |
| `getSize()` | `int` |
| `getType()` | `?string` |

### Query

| Signature | Returns |
| ----------- | --------- |
| `count()` | `int` |
| `countEdges()` | `int` |
| `countNodes()` | `int` |
| `hasEdge(int\|string $from, int\|string $to)` | `bool` |
| `hasNode(int\|string $id)` | `bool` |
| `isEmpty()` | `bool` |

### Functional

| Signature | Returns |
| ----------- | --------- |
| `each(callable $callback)` | `static` |
| `eachEdge(callable $callback)` | `static` |
| `every(callable $predicate)` | `bool` |
| `filter(callable $predicate)` | `static` |
| `find(callable $predicate)` | `mixed` |
| `none(callable $predicate)` | `bool` |
| `reduce(mixed $initial, callable $callback)` | `mixed` |
| `some(callable $predicate)` | `bool` |
| `tap(callable $callback)` | `static` |

### Named Constructors

| Signature | Returns |
| ----------- | --------- |
| `static from(array $nodes, array $edges = [], ?string $type = null)` | `static` |
| `static withType(string $type)` | `static` |

---

## TypedGraph

**Extends:** `Graph`  
**Constructor:** `__construct(?string $type = null)`  
**Abstract property:** `protected ?string $type`

`from()` omits `$type`. `withType()` is disabled. All `Graph` methods inherited.

---

## DirectedGraph

**Extends:** `Graph`  
**Implements:** `DirectedGraphInterface`  
**Constructor:** `__construct(?string $type = null)`

Edges are directional. Removing a node also removes all edges pointing to it.

Overrides:

| Signature | Returns |
| ----------- | --------- |
| `addEdge(int\|string $from, int\|string $to, array $attributes = [])` | `static` |
| `getEdges()` | `array` |
| `removeEdge(int\|string $from, int\|string $to)` | `static` |
| `removeNode(int\|string $id)` | `static` |

Adds:

| Signature | Returns |
| ----------- | --------- |
| `getInNeighbours(int\|string $id)` | `array` |

All other `Graph` methods inherited unchanged.

---

## TypedDirectedGraph

**Extends:** `DirectedGraph`  
**Constructor:** `__construct(?string $type = null)`  
**Abstract property:** `protected ?string $type`

`from()` omits `$type`. `withType()` is disabled. All `DirectedGraph` methods inherited.

---

## Node

**Implements:** `ArrayAccess`, `IteratorAggregate`, `JsonSerializable`  
**Constructor:** `__construct(mixed $content = null, ?string $name = null, ?self $parent = null)`

A tree node carrying arbitrary content. Supports dot-notation qualified-path traversal.

### Magic

| Signature | Returns |
| ----------- | --------- |
| `__clone()` | `void` |
| `__get(string $name)` | `?static` |
| `__isset(string $name)` | `bool` |
| `__set(string $name, self $child)` | `void` |
| `__unset(string $name)` | `void` |

### Child Management

| Signature | Returns |
| ----------- | --------- |
| `getChild(string $name)` | `?Node` |
| `getChildren()` | `NodeList` |
| `hasChild(string $name)` | `bool` |
| `removeChild(string $name)` | `static` |
| `setChild(self $child, ?string $name = null)` | `static` |

### Tree Position

| Signature | Returns |
| ----------- | --------- |
| `detach()` | `static` |
| `getDepth()` | `int` |
| `getParent()` | `?static` |
| `getRoot()` | `static` |
| `isLeaf()` | `bool` |
| `isRoot()` | `bool` |

### Qualified-Path Traversal

| Signature | Returns |
| ----------- | --------- |
| `get(string $qualifiedName)` | `?static` |
| `has(string $path)` | `bool` |
| `set(string $qualifiedName, mixed $value)` | `static` |

### Content

| Signature | Returns |
| ----------- | --------- |
| `getAutoPrune()` | `bool` |
| `getContent()` | `mixed` |
| `getName()` | `string` |
| `getQualifiedName()` | `string` |
| `setAutoPrune(bool $autoPrune)` | `static` |
| `setContent(mixed $content)` | `static` |
| `setName(string $name)` | `static` |
| `setParent(self $parent)` | `static` |

### Bulk Operations

| Signature | Returns |
| ----------- | --------- |
| `export()` | `array` |
| `filter(?callable $callback = null, int $mode = 0)` | `static` |
| `import(iterable ...$data)` | `static` |
| `jsonSerialize()` | `mixed` |
| `merge(iterable ...$lists)` | `static` |
| `mergeRecursive(iterable ...$lists)` | `static` |
| `walk(callable $callback)` | `void` |

### ArrayAccess

| Signature | Returns |
| ----------- | --------- |
| `offsetExists(mixed $name)` | `bool` |
| `offsetGet(mixed $name)` | `mixed` |
| `offsetSet(mixed $name, mixed $child)` | `void` |
| `offsetUnset(mixed $name)` | `void` |

### Named Constructors / Static Utilities

| Signature | Returns |
| ----------- | --------- |
| `static exportNode(self $node)` | `array` |
| `static from(mixed $content, ?string $name = null, ?self $parent = null)` | `static` |
| `static getQualifiedNameOfNode(self $node)` | `string` |
| `static importIntoNode(self $node, iterable ...$data)` | `void` |

---

## NodeList

**Extends:** `TypedCollection`  
**Pre-configured:** `protected ?string $type = Node::class`

No additional public methods. Full `Collection` / `TypedCollection` API available.

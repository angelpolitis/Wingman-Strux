# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.0] — 2026-03-16

### Added

- `Collection` — general-purpose ordered list with type enforcement, optional capacity cap, freeze/unfreeze immutability, and a full functional-iterator API (`each`, `filter`, `map`, `find`, `reduce`, `every`, `some`, `none`, `tap`, `groupBy`, `partition`, `orderBy`, `chunk`, `slice`, `unique`, `sort`, `min`, `max`, `sum`, etc.)
- `TypedCollection` — abstract subclass of `Collection`; type declared via a `$type` property rather than a constructor argument
- `NodeList` — concrete `TypedCollection` pre-configured for `Node` instances
- `EnumCollection` — `Collection` variant restricted to PHP 8.1+ enum cases, with `addValue`, `hasValue`, `names`, `values`, `forEnum`, and `fromEnum` helpers
- `LazyCollection` — generator-backed lazy pipeline; `filter`, `map`, `take`, `skip`, and `chunk` stages are deferred until the pipeline is consumed; ships with `range` and `repeat` infinite-source factories
- `TypedLazyCollection` — abstract subclass of `LazyCollection`; validates each yielded item against `$type` at iteration time
- `Stack` — LIFO structure backed by `SplDoublyLinkedList`; O(1) `push`/`pop`
- `TypedStack` — abstract typed variant of `Stack`
- `Queue` — FIFO structure backed by `SplQueue`; O(1) `enqueue`/`dequeue`
- `TypedQueue` — abstract typed variant of `Queue`
- `PriorityQueue` — max-heap priority queue backed by `SplPriorityQueue`; monotone serial counter guarantees stable FIFO ordering for equal-priority items
- `TypedPriorityQueue` — abstract typed variant of `PriorityQueue`
- `Set` — unordered collection with no duplicate elements; O(1) membership checks via an internal hash-table lookup
- `TypedSet` — abstract typed variant of `Set`
- `SortedList` — always-sorted list using binary-search insertion (O(log n)); supports custom comparators
- `TypedSortedList` — abstract typed variant of `SortedList`
- `CircularBuffer` — fixed-capacity ring buffer; O(1) `write`/`read`/`peek`; silently overwrites the oldest entry when full
- `TypedCircularBuffer` — abstract typed variant of `CircularBuffer`
- `HashMap` — key-value map accepting any key type including objects (keyed by SPL object hash); implements `ArrayAccess`, `Iterator`, and `JsonSerializable`
- `TypedHashMap` — abstract typed variant of `HashMap`
- `BidirectionalMap` — bijective one-to-one map with O(1) forward and reverse lookup; bijectivity is maintained automatically on every `set`
- `TypedBidirectionalMap` — abstract typed variant of `BidirectionalMap`
- `MultiMap` — one-to-many map where a single key holds an ordered bucket of values; `set` appends, `replace` overwrites, `removeValue` removes a single entry
- `TypedMultiMap` — abstract typed variant of `MultiMap`
- `LruCache` — fixed-capacity key-value cache with least-recently-used eviction; `get` and `put` promote to MRU in O(1); `has` is a pure read that does not affect LRU order
- `TypedLruCache` — abstract typed variant of `LruCache`
- `Trie` — prefix tree mapping string words to associated values; `has` and `hasPrefix` run in O(k) regardless of stored word count
- `TypedTrie` — abstract typed variant of `Trie`
- `WeakReferenceMap` — `WeakMap`-backed map; keys must be objects; stale entries are removed automatically when key objects are garbage-collected
- `Graph` — undirected graph with a symmetric adjacency list; typed node values; O(1) edge lookup from either endpoint; `edges` deduplicates to return each logical edge once
- `TypedGraph` — abstract typed variant of `Graph`
- `DirectedGraph` — directed graph extending `Graph`; `addEdge(a, b)` creates a → b only; carries a reverse-adjacency index for O(1) `inNeighbours`
- `TypedDirectedGraph` — abstract typed variant of `DirectedGraph`
- `Node` — named tree node with qualified dot-notation path traversal (`get`, `set`, `has`), `import`/`export`, deep `clone`, `detach`, and Corvus event integration via `Signal` enum cases
- `SequenceInterface`, `SetInterface`, `MapInterface`, `GraphInterface`, `DirectedGraphInterface` — structural contracts in `Wingman\Strux\Interfaces`
- Argus test suite covering all 34 classes
- Documentation suite: `docs/Collections.md`, `docs/Sequences.md`, `docs/Maps.md`, `docs/Graphs.md`, `docs/Tree.md`, `docs/Typed-Subclassing.md`, `docs/API-Reference.md`

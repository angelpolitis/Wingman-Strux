# Sequences

This document covers the sequence types in Strux: `Stack`, `Queue`, `PriorityQueue`, `CircularBuffer`, and `SortedList`, together with their `Typed*` abstract variants.

All sequence types implement `SequenceInterface` (which extends `Countable` and `IteratorAggregate`) and share the same functional-iterator API (`each`, `filter`, `find`, `reduce`, `every`, `some`, `none`, `tap`).

---

## Contents

- [Stack](#stack)
- [Queue](#queue)
- [PriorityQueue](#priorityqueue)
- [CircularBuffer](#circularbuffer)
- [SortedList](#sortedlist)
- [Typed Variants](#typed-variants)

---

## Stack

A LIFO (Last-In, First-Out) structure backed by `SplDoublyLinkedList`. Both `push` and `pop` run in **O(1)**.

### Construction

```php
use Wingman\Strux\Stack;

$stack = new Stack();
$stack = new Stack(items: [1, 2, 3], type: 'int', cap: 100);
$stack = Stack::withType('int');
$stack = Stack::withCap(50);
$stack = Stack::from([1, 2, 3], type: 'int');
```

### Core Operations

```php
$stack->push(1, 2, 3);   // push multiple at once; last arg ends on top
$stack->pop();            // removes and returns top item; null if empty
$stack->peek();           // returns top without removing; null if empty
```

### Querying

```php
$stack->getSize();        // int
$stack->isEmpty();        // bool
$stack->getCap();         // ?int
$stack->hasCap();         // bool
$stack->getType();        // ?string
$stack->isFrozen();       // bool
$stack->toArray();        // plain array, top-to-bottom
```

### Functional Iterators

All iterators traverse the stack **top-to-bottom** (LIFO order).

```php
$stack->each(fn ($item) => print($item));
$stack->find(fn ($x) => $x > 5);
$stack->reduce(fn ($carry, $x) => $carry + $x, 0);
$stack->every(fn ($x) => is_int($x));
$stack->some(fn ($x) => $x < 0);
$stack->none(fn ($x) => is_null($x));
$stack->tap(fn ($s) => log($s->getSize()));
```

### Immutability

```php
$stack->freeze();         // throws LogicException on any mutation
$stack->unfreeze();       // re-enables mutations
```

---

## Queue

A FIFO (First-In, First-Out) structure backed by `SplQueue`. Both `enqueue` and `dequeue` run in **O(1)**.

### Construction

```php
use Wingman\Strux\Queue;

$queue = new Queue();
$queue = new Queue(items: ['a', 'b', 'c'], type: 'string', cap: 200);
$queue = Queue::withType('string');
$queue = Queue::withCap(100);
$queue = Queue::from(['a', 'b'], type: 'string');
```

### Core Operations

```php
$queue->enqueue('x', 'y', 'z'); // adds items at the back
$queue->dequeue();               // removes and returns front item; null if empty
$queue->peek();                  // returns front without removing; null if empty
```

### Querying

```php
$queue->getSize();
$queue->isEmpty();
$queue->getCap();
$queue->hasCap();
$queue->getType();
$queue->isFrozen();
$queue->toArray();               // front-to-back
```

### Functional Iterators

All iterators traverse **front-to-back** (FIFO order). The iterator works on a snapshot clone of the internal `SplQueue`, so `dequeue` is never called during iteration.

```php
$queue->each(fn ($item) => process($item));
$queue->filter(fn ($x) => $x !== 'skip');
$queue->find(fn ($x) => strlen($x) > 3);
$queue->reduce(fn ($carry, $x) => $carry . $x, '');
$queue->every(fn ($x) => is_string($x));
$queue->some(fn ($x) => $x === 'urgent');
$queue->none(fn ($x) => is_null($x));
```

### Immutability

```php
$queue->freeze();
$queue->unfreeze();
```

---

## PriorityQueue

A max-heap priority queue backed by `SplPriorityQueue`. Items with **higher priority values** are dequeued first. When two items share the same priority, they are dequeued in **FIFO order** (insertion order is preserved for equal-priority items via an internal serial counter).

- `enqueue` runs in **O(log n)**
- `dequeue` runs in **O(log n)**
- `peek` runs in **O(1)** on a clone

### Construction

```php
use Wingman\Strux\PriorityQueue;

$pq = new PriorityQueue();
$pq = new PriorityQueue(type: 'string');
$pq = PriorityQueue::withType('string');

// from() expects [priority => item] pairs:
$pq = PriorityQueue::from([100 => 'high', 10 => 'low', 50 => 'medium']);
```

### Core Operations

```php
$pq->enqueue('task', priority: 10);   // default priority = 0
$pq->dequeue();                        // removes and returns highest-priority item
$pq->peek();                           // returns highest-priority item without removal
```

### Querying

```php
$pq->getSize();
$pq->isEmpty();
$pq->getType();
$pq->toArray();   // all items, highest-priority first
```

### Functional Iterators

Iterators work on a clone of the internal heap and traverse items in **highest-priority-first** order.

```php
$pq->each(fn ($item) => dispatch($item));
$pq->find(fn ($x) => str_starts_with($x, 'auth'));
$pq->every(fn ($x) => is_string($x));
$pq->some(fn ($x) => $x === 'shutdown');
$pq->none(fn ($x) => $x === null);
$pq->reduce(fn ($carry, $x) => $carry . ' ' . $x, '');
```

### Example: Middleware dispatcher

```php
$pq = new PriorityQueue();
$pq->enqueue('AuthMiddleware',  100);
$pq->enqueue('RateLimiter',      90);
$pq->enqueue('LogMiddleware',    10);

while (!$pq->isEmpty()) {
    $middleware = $pq->dequeue();
    $middleware->handle($request);
}
```

---

## CircularBuffer

A fixed-capacity ring buffer. When the buffer is full, `write()` silently overwrites the **oldest** entry rather than throwing. All operations run in **O(1)** — no element shifting, no reallocation.

### Construction

```php
use Wingman\Strux\CircularBuffer;

$buf = new CircularBuffer(cap: 5);
$buf = new CircularBuffer(cap: 10, type: 'string');

// from() defaults cap to item count (min 1):
$buf = CircularBuffer::from(['a', 'b', 'c']);
```

### Core Operations

```php
$buf->write('item');    // appends; overwrites oldest when full
$buf->read();           // removes and returns oldest item; null if empty
$buf->peek();           // returns oldest item without removing; null if empty
$buf->flush();          // discards all items; preserves capacity
```

### Querying

```php
$buf->getSize();
$buf->getCap();
$buf->isEmpty();
$buf->isFull();
$buf->getType();
$buf->toArray();        // all items, oldest-to-newest
```

### Functional Iterators

Iterators traverse items **oldest-to-newest**.

```php
$buf->each(fn ($x) => record($x));
$buf->filter(fn ($x) => $x !== null);
$buf->find(fn ($x) => $x > 100);
$buf->every(fn ($x) => is_numeric($x));
$buf->some(fn ($x) => $x < 0);
$buf->none(fn ($x) => is_null($x));
$buf->reduce(fn ($carry, $x) => $carry + $x, 0);
```

### Example: Last-N-queries debug panel

```php
$queryLog = new CircularBuffer(50);

$queryLog->write(['sql' => $sql, 'ms' => $elapsed]);

// Display last 50 queries, oldest first:
foreach ($queryLog as $entry) {
    echo "({$entry['ms']}ms) {$entry['sql']}\n";
}
```

---

## SortedList

An always-sorted list that maintains ascending order by using **binary-search insertion** (**O(log n)**). A custom comparator can be provided for any ordering. Membership checks via `has()` also use binary search.

### Construction

```php
use Wingman\Strux\SortedList;

$list = new SortedList();
$list = new SortedList(items: [5, 2, 8], type: 'int');
$list = SortedList::withType('int');
$list = SortedList::withComparator(fn ($a, $b) => strlen($a) <=> strlen($b));
$list = SortedList::from([5, 2, 8], type: 'int');
```

### Core Operations

```php
$list->add(3, 7, 1);     // inserts each into correct sorted position
$list->remove(3);         // removes first occurrence; O(log n) search + O(n) shift
$list->removeAt(0);       // removes by 0-based index; O(n) shift
```

### Querying

```php
$list->has(5);            // bool — binary search
$list->indexOf(5);        // 0-based index of first occurrence, or -1
$list->getFirst();        // smallest element
$list->getLast();         // largest element
$list->getSize();
$list->isEmpty();
$list->getType();
$list->getComparator();   // ?callable
$list->toArray();         // sorted array
```

### Functional Iterators

Iterators traverse in **sorted order**.

```php
$list->each(fn ($x) => print($x));
$list->filter(fn ($x) => $x > 5);  // returns new SortedList
$list->map(fn ($x) => $x * 2);     // returns plain array
$list->find(fn ($x) => $x > 3);
$list->reduce(fn ($carry, $x) => $carry + $x, 0);
$list->every(fn ($x) => $x > 0);
$list->some(fn ($x) => $x < 0);
$list->none(fn ($x) => is_null($x));
```

### Example: Custom descending order

```php
$scores = SortedList::withComparator(fn ($a, $b) => $b <=> $a);
$scores->add(42, 99, 17, 55);
echo $scores->getFirst(); // 99 (largest first)
```

---

## Typed Variants

Every sequence type has an abstract `Typed*` counterpart. Subclasses declare the `$type` property:

```php
use Wingman\Strux\TypedStack;
use Wingman\Strux\TypedQueue;
use Wingman\Strux\TypedPriorityQueue;
use Wingman\Strux\TypedSortedList;
use Wingman\Strux\TypedCircularBuffer;

class IntStack extends TypedStack {
    protected ?string $type = 'int';
}

class StringQueue extends TypedQueue {
    protected ?string $type = 'string';
}

class JobQueue extends TypedPriorityQueue {
    protected ?string $type = Job::class;
}

class FloatSortedList extends TypedSortedList {
    protected ?string $type = 'float';
}

// TypedCircularBuffer requires the cap at construction:
class LogBuffer extends TypedCircularBuffer {
    protected ?string $type = 'string';
}
$log = new LogBuffer(100);
```

For classes that take constructor arguments (`TypedCircularBuffer` takes `int $cap`), pass them when instantiating. See [Typed-Subclassing.md](Typed-Subclassing.md) for details.

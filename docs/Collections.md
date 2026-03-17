# Collections

This document covers the collection types in Strux: `Collection`, `TypedCollection`, `EnumCollection`, `LazyCollection`, and `TypedLazyCollection`.

---

## Contents

- [Collection](#collection)
- [TypedCollection](#typedcollection)
- [EnumCollection](#enumcollection)
- [LazyCollection](#lazycollection)
- [TypedLazyCollection](#typedlazycollection)

---

## Collection

`Collection` is the foundational ordered-list type in Strux. It wraps a plain PHP array and adds type enforcement, an optional capacity cap, immutability, and a rich functional-iterator API.

### Construction

```php
use Wingman\Strux\Collection;

// Empty
$c = new Collection();

// Pre-loaded items
$c = Collection::withItems([1, 2, 3]);

// With type enforcement
$c = Collection::withType('int');

// With capacity
$c = Collection::withCap(10);

// Full control
$c = Collection::from(
    items:  [1, 2, 3],
    type:   'int',
    cap:    100,
    frozen: false
);
```

### Adding Items

```php
$c->add(4, 5, 6);        // fluent; multiple values at once
$c[] = 7;                // ArrayAccess; appends
$c->with(8, 9);          // returns a new collection, does not mutate $c
```

### Accessing Items

```php
$c->get(0);              // item at index 0
$c->getFirst();          // first item
$c->getLast();           // last item
$c->getAll();            // plain PHP array
```

### Mutation

```php
$c->remove(2);           // removes item at index 2
$c->without($a, $b);     // new collection with $a and $b excluded
$c->withoutIndices(0, 3); // new collection with indices 0 and 3 excluded
```

### Ordering

```php
// Returns a new, sorted collection using a comparator:
$sorted = $c->sort(fn ($a, $b) => $a <=> $b);

// Sort in-place:
$c->sortInPlace(fn ($a, $b) => $a <=> $b);

// Sort by a projected key (ascending):
$byLength = $words->orderBy(fn ($w) => strlen($w));

// Sort in-place by projected key:
$words->orderByInPlace(fn ($w) => strlen($w));

// Reverse order:
$reversed = $c->reverse();
```

### Searching

```php
$c->contains($item);                          // O(1) strict equality
$c->has($item);                               // alias; supports custom comparator
$c->indexOf($item);                           // 0-based index, or -1
$c->lastIndexOf($item);                       // last occurrence index, or -1
$c->find(fn ($x) => $x > 5);                 // first match, or null
$c->findLast(fn ($x) => $x > 5);             // last match, or null
```

### Functional Iterators

```php
$c->each(function ($item, $index) { /* ... */ });
$c->filter(fn ($x) => $x > 0);               // new Collection
$c->map(fn ($x) => $x * 2);                  // plain array
$c->reduce(fn ($carry, $x) => $carry + $x, 0);
$c->reduceRight(fn ($carry, $x) => $carry . $x, '');
$c->every(fn ($x) => $x > 0);                // bool
$c->some(fn ($x) => $x > 0);                 // bool
$c->none(fn ($x) => $x < 0);                 // bool
$c->tap(function ($coll) { log($coll); });    // returns $this
```

### Aggregation

```php
$c->sum();                             // sum of all values
$c->sum(fn ($x) => $x->price);        // projected sum
$c->getMin();                          // item with smallest value
$c->getMax(fn ($x) => $x->score);     // item with largest projected value
```

### Grouping & Partitioning

```php
// Groups items into [key => Collection]:
$byCategory = $c->groupBy(fn ($x) => $x->category);

// Splits into [passing, failing]:
[$active, $inactive] = $c->partition(fn ($x) => $x->isActive());
```

### Slicing

```php
$c->slice(2);          // from index 2 to end
$c->slice(1, 5);       // items 1–5
$c->chunk(3);          // array of Collection, each of at most 3 items
$c->deduplicate();    // new Collection with duplicate values removed
```

### Immutability

```php
$c->freeze();          // prevents all future mutations; returns $this
$c->isFrozen();        // bool
$c->unfreeze();        // re-enables mutations; returns $this
```

Any mutation attempt on a frozen collection throws `LogicException`.

### Capacity

```php
$c->getCap();          // int|null
$c->hasCap();          // bool
```

Attempting to add items beyond the cap throws `LogicException`.

### Implementing `SequenceInterface`

`Collection` implements `SequenceInterface`, `ArrayAccess`, `Countable`, and `IteratorAggregate`. It is safe to pass wherever those contracts are accepted.

---

## TypedCollection

`TypedCollection` is an abstract subclass of `Collection` for creating **named typed collection classes** without repeating constructor arguments. Subclasses declare the `$type` property:

```php
use Wingman\Strux\TypedCollection;
use App\Models\User;

class UserCollection extends TypedCollection {
    protected ?string $type = User::class;
}

$users = new UserCollection();
$users->add(new User('Alice')); // OK
$users->add(new stdClass());    // throws InvalidArgumentException
```

The `$type` property accepts any primitive name (`'int'`, `'float'`, `'string'`, `'bool'`, `'array'`, `'object'`, `'null'`) or any fully-qualified class or interface name.

The constructor of `TypedCollection` omits the `$type` parameter since the type is baked into the class:

```php
__construct(array $items = [], ?int $cap = null, bool $frozen = false)
```

All other `Collection` methods are inherited unchanged. `NodeList` is a concrete subclass pre-configured for `Node` objects.

---

## EnumCollection

`EnumCollection` restricts items to cases of a specific PHP 8.1+ enum. It extends `Collection` with additional enum-aware helpers.

### Construction

```php
use Wingman\Strux\EnumCollection;

enum Status: string {
    case Active   = 'active';
    case Inactive = 'inactive';
    case Pending  = 'pending';
}

// Empty collection restricted to Status cases:
$statuses = EnumCollection::forEnum(Status::class);

// Pre-loaded:
$statuses = EnumCollection::fromEnum(Status::class, [Status::Active, Status::Pending]);
```

### Adding Cases

```php
// Add a case directly:
$statuses->add(Status::Inactive);

// Add a case by its backing value (BackedEnum only):
$statuses->addValue('active');  // equivalent to add(Status::Active)
```

Attempting to add an item that is not a case of the declared enum throws `InvalidArgumentException`.

### Querying

```php
$statuses->hasValue('active');    // bool — checks by backing value
$statuses->getNames();            // ['Active', 'Pending', 'Inactive']
$statuses->getValues();           // ['active', 'pending', 'inactive'] (BackedEnum)
                                  // ['Active', 'Pending', 'Inactive'] (UnitEnum)
$statuses->getEnumClass();        // 'Status'
```

All `Collection` methods (`filter`, `map`, `find`, etc.) work normally.

---

## LazyCollection

`LazyCollection` is a **generator-backed pipeline** — no items are held in memory and no work is done until the pipeline is consumed. This makes it suitable for very large or infinite sequences.

### Construction

```php
use Wingman\Strux\LazyCollection;

// From an iterable (array, generator, another collection, etc.):
$lc = LazyCollection::make([1, 2, 3, 4, 5]);

// Infinite integer range starting at 0, stepping by 2:
$evens = LazyCollection::range(start: 0, step: 2);

// Infinite constant stream:
$ones = LazyCollection::repeat(1);

// Finite constant stream:
$fiveHis = LazyCollection::repeat('hi', 5);

// From a custom generator:
$lc = new LazyCollection(function () {
    yield from range(1, 1_000_000);
});
```

### Lazy Pipeline Stages

These operations return a **new** `LazyCollection` instance with a stage appended to the pipeline; no items are evaluated yet:

```php
$lc->filter(fn ($x) => $x % 2 === 0);
$lc->map(fn ($x) => $x * $x);
$lc->take(100);          // at most 100 items
$lc->skip(10);           // skip first 10 items
$lc->chunk(5);           // lazy batches of up to 5
```

### Consuming the Pipeline

```php
$lc->toArray();           // forces full evaluation
$lc->collect();           // returns an in-memory Collection
$lc->each(fn ($x) => print($x));  // eagerly runs, returns $this
$lc->find(fn ($x) => $x > 100);   // stops at first match
$lc->first();             // stops after first item
$lc->reduce(fn ($c, $x) => $c + $x, 0); // accumulates
$lc->every(fn ($x) => $x > 0);   // short-circuits on false
$lc->some(fn ($x) => $x > 99);   // short-circuits on first true
$lc->none(fn ($x) => $x < 0);    // short-circuits on first true
```

### Size Queries

```php
$lc->isEmpty();           // peeks only one item — efficient
$lc->count();             // forces full evaluation — use sparingly
$lc->getSize();           // alias of count()
```

### Example: First 5 squares of even numbers

```php
$result = LazyCollection::range(1)
    ->filter(fn ($n) => $n % 2 === 0)
    ->map(fn ($n) => $n ** 2)
    ->take(5)
    ->toArray();

// [4, 16, 36, 64, 100]
```

---

## TypedLazyCollection

`TypedLazyCollection` is an abstract subclass that validates each item **at iteration time** (not at insertion time, since items are generated lazily). Declare the `$type` property in subclasses:

```php
use Wingman\Strux\TypedLazyCollection;

class IntLazyCollection extends TypedLazyCollection {
    protected ?string $type = 'int';
}

$lc = new IntLazyCollection(fn () => yield from [1, 2, 'oops', 4]);
$lc->toArray(); // throws InvalidArgumentException on 'oops'
```

The exception is thrown the moment the non-conforming item is produced by the generator, which means a `toArray()` or any other consuming call will fail at the first bad value. Items before the bad value will already have been produced.

# Typed Subclassing

Strux provides two mechanisms for type-enforcing data structures:

1. **Runtime constructor argument** — pass a type string at instantiation
2. **Named typed subclass** — extend a `Typed*` abstract class and declare the `$type` property

The `Typed*` classes are designed for scenario 2, where you want a **reusable, named type** that compiles the constraint into the class definition rather than relying on every call site to remember the `type:` argument.

---

## Contents

- [Typed Subclassing](#typed-subclassing)
  - [Contents](#contents)
  - [The Pattern](#the-pattern)
  - [Supported Type Strings](#supported-type-strings)
  - [Verix Schema Expressions](#verix-schema-expressions)
    - [Detection](#detection)
    - [Array Types](#array-types)
    - [Struct (Object Shape) Types](#struct-object-shape-types)
    - [Named Schema References](#named-schema-references)
    - [Arrays of Named Schemas](#arrays-of-named-schemas)
    - [Fallback Behaviour](#fallback-behaviour)
  - [Classes Without Constructor Arguments](#classes-without-constructor-arguments)
  - [Classes With Constructor Arguments](#classes-with-constructor-arguments)
    - [`TypedCircularBuffer`](#typedcircularbuffer)
    - [`TypedLruCache`](#typedlrucache)
  - [Combining With Named Constructors](#combining-with-named-constructors)
  - [Anonymous Subclasses](#anonymous-subclasses)
  - [TypedLruCache](#typedlrucache-1)
  - [TypedTrie](#typedtrie)
  - [Full Reference](#full-reference)

---

## The Pattern

Every `Typed*` abstract class declares:

```php
protected ?string $type = null;
```

Subclasses override this with a concrete type:

```php
use Wingman\Strux\TypedStack;

class IntStack extends TypedStack {
    protected ?string $type = 'int';
}

$stack = new IntStack();
$stack->push(1, 2, 3);    // OK
$stack->push('hello');     // throws InvalidArgumentException
```

---

## Supported Type Strings

| Category | Examples |
|---|---|
| PHP primitives | `'int'`, `'float'`, `'string'`, `'bool'`, `'array'`, `'object'`, `'null'` |
| Fully-qualified class names | `'App\\Models\\User'`, `User::class` |
| Fully-qualified interface names | `'App\\Contracts\\Renderable'`, `Renderable::class` |
| Verix schema expressions | `'string[]'`, `'{name: string}'`, `'@MySchema'` |

Type checking uses `instanceof` for classes and interfaces, and `get_debug_type()` / `gettype()` normalisation for primitives. Comparisons are **case-insensitive** for primitive names.

Verix schema expressions are automatically detected when the type string contains any of the characters `< > { } [ ]` or starts with `@`. When detected, validation is delegated to the [Verix](#verix-schema-expressions) package. If Verix is not installed and a schema expression is encountered, a `LogicException` is thrown.

---

## Verix Schema Expressions

When the [Wingman Verix](../../Verix) package is installed, the `$type` property accepts the full Verix DSL in addition to plain PHP types. This unlocks structural and composite type constraints that are impossible to express with a single class or primitive name.

### Detection

Strux routes a type string to Verix when it contains any of: `<`, `>`, `{`, `}`, `[`, `]`, or starts with `@`. Plain types (`int`, `string`, `App\Models\User`) are never routed and continue to be handled by Strux's built-in enforcement.

### Array Types

```php
class StringCollection extends TypedCollection {
    // Every item must be a string
    protected ?string $type = 'string[]';
}

class UserCollection extends TypedCollection {
    // Every item must be a User instance
    protected ?string $type = 'App\\Models\\User[]';
}
```

### Struct (Object Shape) Types

```php
class PointCollection extends TypedCollection {
    // Every item must be an array { x: float, y: float }
    protected ?string $type = '{x: float, y: float}';
}

class RecordCollection extends TypedCollection {
    // Required fields + one optional field
    protected ?string $type = '{id: int, name: string, bio?: string}';
}
```

### Named Schema References

Schemas registered via `Schema::register()` can be referenced with the `@` sigil. This is the recommended way to express union or nullable types, since bare `string | null` does not contain a trigger character and will not be routed to Verix.

```php
use Wingman\Verix\Facades\Schema;

// Register schemas once at application boot:
Schema::register('NullableString', 'string | null');
Schema::register('Tag', "'draft' | 'published' | 'archived'");
Schema::register('Coordinate', '{lat: float, lng: float}');

class NullableStringStack extends TypedStack {
    protected ?string $type = '@NullableString';
}

class TagSet extends TypedSet {
    protected ?string $type = '@Tag';
}

class CoordinateList extends TypedSortedList {
    protected ?string $type = '@Coordinate';
}
```

### Arrays of Named Schemas

Schema references can be combined with the `[]` array suffix:

```php
Schema::register('Point', '{x: float, y: float}');

class PointCollection extends TypedCollection {
    protected ?string $type = '@Point[]';
}
```

### Fallback Behaviour

If a Verix schema expression is used but the Verix package is not installed, Strux throws a `LogicException` on the first item insertion with a message indicating that Verix must be installed.

---

## Classes Without Constructor Arguments

Most `Typed*` classes require no constructor arguments. Simply `new` them:

```php
use Wingman\Strux\TypedCollection;
use Wingman\Strux\TypedStack;
use Wingman\Strux\TypedQueue;
use Wingman\Strux\TypedPriorityQueue;
use Wingman\Strux\TypedSet;
use Wingman\Strux\TypedSortedList;
use Wingman\Strux\TypedHashMap;
use Wingman\Strux\TypedBidirectionalMap;
use Wingman\Strux\TypedMultiMap;
use Wingman\Strux\TypedTrie;
use Wingman\Strux\TypedGraph;
use Wingman\Strux\TypedDirectedGraph;
use Wingman\Strux\TypedLazyCollection;

class FloatCollection extends TypedCollection      { protected ?string $type = 'float'; }
class StringStack     extends TypedStack           { protected ?string $type = 'string'; }
class IntQueue        extends TypedQueue           { protected ?string $type = 'int'; }
class JobQueue        extends TypedPriorityQueue   { protected ?string $type = Job::class; }
class TagSet          extends TypedSet             { protected ?string $type = 'string'; }
class ScoreList       extends TypedSortedList      { protected ?string $type = 'int'; }
class ConfigMap       extends TypedHashMap         { protected ?string $type = 'string'; }
class LocaleMap       extends TypedBidirectionalMap{ protected ?string $type = 'string'; }
class EventListeners  extends TypedMultiMap        { protected ?string $type = 'callable'; }
class WordIndex       extends TypedTrie            { protected ?string $type = 'int'; }
class RouteGraph      extends TypedGraph           { protected ?string $type = Route::class; }
class DepGraph        extends TypedDirectedGraph   { protected ?string $type = 'string'; }
class IntStream       extends TypedLazyCollection  { protected ?string $type = 'int'; }
```

---

## Classes With Constructor Arguments

Two `Typed*` classes require constructor arguments:

### `TypedCircularBuffer`

```php
use Wingman\Strux\TypedCircularBuffer;

class LogBuffer extends TypedCircularBuffer {
    protected ?string $type = 'string';
}

// Pass capacity at instantiation:
$log = new LogBuffer(100);    // capacity = 100 entries
$log->write('line 1');
$log->write('line 2');
```

### `TypedLruCache`

```php
use Wingman\Strux\TypedLruCache;

class IntCache extends TypedLruCache {
    protected ?string $type = 'int';
}

// Pass capacity at instantiation:
$cache = new IntCache(500);
$cache->put('key', 42);
```

---

## Combining With Named Constructors

`Typed*` classes expose a `from()` static named constructor that accepts items but not a `type` argument (the type is already baked in):

```php
class IntStack extends TypedStack {
    protected ?string $type = 'int';
}

$stack = IntStack::from([1, 2, 3]);  // type = 'int', no type parameter
```

`withType()` is **disabled** on all `Typed*` classes — calling it throws `LogicException`, because the type is already declared at the class level.

---

## Anonymous Subclasses

For one-off use in tests or isolated code, anonymous classes avoid the need to declare a named class:

```php
// One-off typed stack in a test:
$stack = new class extends TypedStack {
    protected ?string $type = 'int';
};

// One-off typed circular buffer (requires cap):
$buf = new class(50) extends TypedCircularBuffer {
    protected ?string $type = 'string';
};

// One-off typed LRU cache (requires cap):
$cache = new class(100) extends TypedLruCache {
    protected ?string $type = 'int';
};
```

---

## TypedLruCache

```php
abstract class TypedLruCache extends LruCache {
    protected ?string $type = null;
}
```

Constructor: `__construct(int $cap)` — pass capacity when instantiating.

`get()` promotes to MRU exactly as `LruCache::get()` does; type enforcement applies only at `put()` time.

---

## TypedTrie

```php
abstract class TypedTrie extends Trie {
    protected ?string $type = null;
}
```

Constructor: `__construct()` — no arguments needed (unlike `TypedLruCache`/`TypedCircularBuffer`).

Type enforcement applies to **values**, not to words (words are always strings):

```php
class IntTrie extends TypedTrie {
    protected ?string $type = 'int';
}

$trie = new IntTrie();
$trie->set('apple', 42);     // OK
$trie->set('banana', 'x');   // throws InvalidArgumentException
```

---

## Full Reference

| Abstract Class | Required Constructor Args | `$type` targets |
|---|---|---|
| `TypedCollection` | none | item values |
| `TypedStack` | none | pushed values |
| `TypedQueue` | none | enqueued values |
| `TypedPriorityQueue` | none | enqueued values |
| `TypedSet` | none | added elements |
| `TypedSortedList` | none | added elements |
| `TypedCircularBuffer` | `int $cap` | written values |
| `TypedLazyCollection` | none | yielded values (at iteration) |
| `TypedHashMap` | none | entry values |
| `TypedBidirectionalMap` | none | map values |
| `TypedMultiMap` | none | bucket values |
| `TypedLruCache` | `int $cap` | cached values |
| `TypedTrie` | none | word values |
| `TypedGraph` | none | node values |
| `TypedDirectedGraph` | none | node values |

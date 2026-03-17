# Maps

This document covers the map types in Strux: `HashMap`, `BidirectionalMap`, `MultiMap`, and `WeakReferenceMap`, together with their `Typed*` abstract variants where applicable.

---

## Contents

- [HashMap](#hashmap)
- [BidirectionalMap](#bidirectionalmap)
- [MultiMap](#multimap)
- [WeakReferenceMap](#weakreferencemap)
- [Typed Variants](#typed-variants)

---

## HashMap

`HashMap` is the general-purpose key-value map. Unlike PHP's native `array`, it accepts **any** key type including objects (object keys are stored under their SPL object hash). Lookups, insertions, and removals all run in amortised **O(1)**.

`HashMap` implements `ArrayAccess`, `Countable`, `Iterator`, `JsonSerializable`, and `MapInterface`.

### Construction

```php
use Wingman\Strux\HashMap;

$map = new HashMap();
$map = new HashMap(data: ['host' => 'localhost', 'port' => 3306], type: 'mixed');
$map = HashMap::from(['host' => 'localhost']);
```

### Setting and Getting

```php
$map->set('key', 'value');           // fluent; returns $this
$map->get('key');                    // value, or null if absent
$map->has('key');                    // bool (true even if value is null)
$map->remove('key');                 // fluent

// ArrayAccess:
$map['key'] = 'value';
echo $map['key'];
isset($map['key']);
unset($map['key']);
```

### Querying

```php
$map->getKeys();                     // all keys as array (raw form)
$map->getValues();                   // all values as array
$map->getSize();                     // entry count
$map->isEmpty();                     // bool
$map->contains($value);             // bool — value-side search
$map->clear();                       // removes all entries; returns $this
```

### Transformation

```php
$map->map(fn ($v, $k) => strtoupper($v));         // new HashMap, values transformed
$map->mapKeys(fn ($k, $v) => 'prefix_' . $k);    // new HashMap, keys transformed
$map->filter(fn ($v) => $v !== null);              // filter by value
$map->filterKey(fn ($k) => str_starts_with($k, 'db_'));  // filter by key
$map->flip();                                       // swap keys and values (values must be scalar)
$map->merge($otherMap, $anotherMap);               // shallow merge; later maps win; returns $this
$map->unique();                                     // new HashMap with duplicate values removed (first wins)
```

### Aggregation

```php
$map->sum();                             // sum of values
$map->sum(fn ($v) => $v->price);        // projected sum
$map->getMin(fn ($v) => $v->score);
$map->getMax();
$map->groupBy(fn ($v) => $v->type);     // [type => HashMap]
$map->partition(fn ($v) => $v > 0);    // [passing HashMap, failing HashMap]
```

### Functional Iterators

```php
$map->each(fn ($value, $key, $map) => process($key, $value));
$map->find(fn ($v) => $v > 100);
$map->findKey(fn ($v, $k) => str_ends_with($k, '_id'));
$map->reduce(fn ($carry, $v, $k) => $carry + $v, 0);
$map->every(fn ($v) => is_int($v));
$map->some(fn ($v) => $v === 'admin');
$map->none(fn ($v) => is_null($v));
$map->tap(fn ($m) => dump($m->getSize()));
```

### JSON Serialisation

```php
json_encode($map);  // {"key":"value",...} — outer keys are serialised forms
```

---

## BidirectionalMap

`BidirectionalMap` is a **bijective (one-to-one)** map where you can look up by key *or* by value in **O(1)**. Both keys and values are constrained to `int|string` (PHP array key types).

Bijectivity is maintained **automatically**:

- Assigning a key that already exists replaces its old pair, removing the old reverse entry.
- Assigning a value that already belongs to another key silently evicts that key.

### Construction

```php
use Wingman\Strux\BidirectionalMap;

$map = new BidirectionalMap();
$map = new BidirectionalMap(['en' => 'English', 'fr' => 'French']);
$map = BidirectionalMap::withType('string');  // restricts value type
$map = BidirectionalMap::from(['en' => 'English']);
```

### Setting and Getting

```php
$map->set('en', 'English');      // stores pair; maintains bijectivity
$map->get('en');                 // 'English'
$map->getKey('English');         // 'en' (reverse lookup)
$map->has('en');                 // bool — key side
$map->hasValue('English');       // bool — value side
$map->remove('en');              // removes pair by key
$map->removeByValue('English');  // removes pair by value
```

### Querying

```php
$map->getKeys();                 // all keys
$map->getValues();               // all values (insertion order)
$map->getSize();
$map->isEmpty();
$map->toArray();                 // ['en' => 'English', ...]
```

### Functional Iterators

```php
$map->each(fn ($value, $key, $map) => print("$key=$value"));
$map->find(fn ($v) => strlen($v) > 5);
$map->every(fn ($v) => is_string($v));
$map->some(fn ($k, $v) => $k === 'en');
$map->none(fn ($v) => is_null($v));
$map->tap(fn ($m) => log($m));
```

### Example: HTTP Status Codes

```php
$statuses = BidirectionalMap::from([
    200 => 'OK',
    404 => 'Not Found',
    500 => 'Internal Server Error',
]);

echo $statuses->get(404);           // 'Not Found'
echo $statuses->getKey('OK');       // 200
```

---

## MultiMap

`MultiMap` is a **one-to-many** map where a single key can hold an ordered bucket of values. Unlike `HashMap`, calling `set()` on an existing key **appends** to the bucket rather than replacing it.

### Construction

```php
use Wingman\Strux\MultiMap;

$mm = new MultiMap();
$mm = MultiMap::withType('callable');
$mm = MultiMap::from([
    'click'  => ['handler1', 'handler2'],
    'submit' => ['handler3'],
]);
```

### Setting and Getting

```php
$mm->set('click', $handler1, $handler2);  // appends to bucket
$mm->get('click');                         // entire bucket as array; [] if absent
$mm->getFirst('click');                    // first value in bucket, or null
$mm->has('click');                         // bool — key exists with ≥1 value
$mm->hasValue('click', $handler1);         // bool — specific value in bucket

$mm->replace('click', $newHandler);        // overwrites entire bucket
$mm->remove('click');                      // deletes entire bucket
$mm->removeValue('click', $handler1);      // removes one value; key removed if bucket empties
```

### Querying

```php
$mm->getKeys();                            // distinct keys
$mm->getValues();                          // all individual values flat
$mm->getSize();                            // distinct key count
$mm->countAll();                           // total values across all buckets
$mm->isEmpty();
$mm->toArray();                            // [key => [values...], ...]
$mm->flatten();                            // Collection of all individual values
```

### Functional Iterators

Callbacks receive the **bucket** (array) as first argument:

```php
$mm->each(fn ($bucket, $key, $mm) => runHandlers($key, $bucket));
$mm->find(fn ($bucket) => count($bucket) > 3);
$mm->every(fn ($bucket) => !empty($bucket));
$mm->some(fn ($bucket, $key) => $key === 'error');
$mm->none(fn ($bucket) => count($bucket) === 0);
$mm->tap(fn ($m) => log($m->countAll()));
```

### Example: Event Dispatcher

```php
$listeners = new MultiMap();
$listeners->set('user.created', fn ($e) => sendWelcomeEmail($e));
$listeners->set('user.created', fn ($e) => logActivity($e));

foreach ($listeners->get('user.created') as $listener) {
    $listener($event);
}
```

---

## WeakReferenceMap

`WeakReferenceMap` is backed by PHP's native `WeakMap`. **Keys must be objects.** Entries are **automatically garbage-collected** when a key object has no other references. This makes it ideal for object-lifecycle-scoped metadata without causing memory leaks.

`WeakReferenceMap` implements `Countable`, `IteratorAggregate`, and `MapInterface`.

### Construction

```php
use Wingman\Strux\WeakReferenceMap;

$map = new WeakReferenceMap();
```

`from()` accepts an array of `[object => value]` pairs:

```php
$map = WeakReferenceMap::from([[$obj1, 'meta1'], [$obj2, 'meta2']]);
```

*Note: PHP array syntax does not allow objects as keys; `from()` accepts a list of `[key, value]` tuples instead.*

### Core Operations

```php
$map->set($obj, $value);
$map->get($obj);          // value, or null if absent/GC'd
$map->has($obj);          // bool
$map->remove($obj);
$map->clear();
```

Passing a non-object key to any method throws `InvalidArgumentException`.

### Querying

```php
$map->getKeys();          // all live object keys
$map->getValues();        // all live values
$map->getSize();          // live entry count (may change between calls as GC runs)
$map->isEmpty();
$map->contains($value);   // strict value search across live entries
```

### Functional Iterators

```php
$map->each(fn ($value, $obj, $map) => process($obj, $value));
$map->tap(fn ($m) => debug($m->getSize()));
```

### Example: Per-Request Metadata

```php
$metadata = new WeakReferenceMap();

function handleRequest(Request $req): void {
    global $metadata;
    $metadata->set($req, ['start' => microtime(true)]);

    handleHttpRequest($req);

    $meta = $metadata->get($req);
    logDuration($meta['start']);
    // $req goes out of scope → entry is automatically removed from $metadata
}
```

---

## Typed Variants

`TypedHashMap`, `TypedBidirectionalMap`, and `TypedMultiMap` are abstract subclasses. Subclasses declare the `$type` property to enforce a value type:

```php
use Wingman\Strux\TypedHashMap;
use Wingman\Strux\TypedBidirectionalMap;
use Wingman\Strux\TypedMultiMap;

class StringMap extends TypedHashMap {
    protected ?string $type = 'string';
}

class LocaleMap extends TypedBidirectionalMap {
    protected ?string $type = 'string'; // restricts value type
}

class CallableMultiMap extends TypedMultiMap {
    protected ?string $type = 'callable';
}
```

`WeakReferenceMap` does not have a typed variant because `WeakMap` already provides structural safety; runtime type enforcement can be added by subclassing `WeakReferenceMap` directly.

See [Typed-Subclassing.md](Typed-Subclassing.md) for the full pattern.

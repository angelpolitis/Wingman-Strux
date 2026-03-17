<?php
    /**
     * Project Name:    Wingman Strux - Collection
     * Created by:      Angel Politis
     * Creation Date:   Nov 17 2025
     * Last Modified:   Mar 17 2026
     *
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Strux namespace.
    namespace Wingman\Strux;

    # Import the following classes to the current scope.
    use ArrayAccess;
    use ArrayIterator;
    use Countable;
    use InvalidArgumentException;
    use IteratorAggregate;
    use LogicException;
    use Traversable;
    use Wingman\Strux\Bridge\Corvus\Emitter;
    use Wingman\Strux\Bridge\Verix\Validator;
    use Wingman\Strux\Enums\Signal;
    use Wingman\Strux\Interfaces\SequenceInterface;

    /**
     * Represents a collection.
     * @package Wingman\Strux
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     * @template T
     * @implements IteratorAggregate<int, T>
     */
    class Collection implements ArrayAccess, Countable, IteratorAggregate, SequenceInterface {
        /**
         * Creates a new collection.
         * @param array $items The items to add to the collection.
         * @param string|null $type The type of items the collection will enforce.
         * @param int|null $cap The maximum capacity of the collection.
         * @param bool $frozen Whether the collection should be frozen.
         */
        public function __construct (array $items = [], ?string $type = null, ?int $cap = null, bool $frozen = false) {
            if (isset($type)) $this->type = $type;
            $this->cap = $cap;
            $this->frozen = $frozen;
            $this->add(...$items);
        }

        /**
         * The cached normalised (lowercased) form of the enforced type name.
         * Only populated for primitive type enforcement.
         * @var string|null
         */
        private ?string $normalisedType = null;

        /**
         * Whether the enforced type resolves to a class or interface (true) or a primitive
         * type name (false). Lazily computed on the first call to enforceType.
         * @var bool|null
         */
        private ?bool $typeIsClass = null;

        /**
         * The maximum number of items allowed in the collection (null = unlimited).
         * @var int|null
         */
        protected ?int $cap = null;

        /**
         * Whether a collection is frozen (immutable).
         * @var bool
         */
        protected bool $frozen = false;

        /**
         * The inner collection of a collection.
         * @var mixed[]
         */
        protected array $items = [];

        /**
         * The type of a collection.
         * @var class-string<T>|string|null
         */
        protected ?string $type = null;

        /**
         * Creates a new instance of the collection pre-loaded with the given items.
         * Subclasses that require additional constructor arguments (e.g. an enum class name)
         * should override this method rather than constructor-call new static() directly.
         * The base implementation simply calls new static($items).
         * @param array $items The items to pre-load.
         * @return static A new instance.
         */
        protected function createInstance (array $items = []) : static {
            return new static($items, $this->type);
        }

        /**
         * Enforces a collection's type on each item.
         * The class/interface vs. primitive distinction and the lowercased type name are lazily
         * cached after the first invocation, eliminating repeated class_exists and strtolower
         * calls on hot code paths.
         * @param mixed ...$items A list of items.
         * @throws InvalidArgumentException If an item isn't of the specified type.
         */
        protected function enforceType (mixed ...$items) : void {
            if (!isset($this->type)) return;

            if (Validator::isSchemaExpression($this->type)) {
                foreach ($items as $i => $item) {
                    Validator::validate($item, $this->type, $i);
                }
                return;
            }

            $this->typeIsClass ??= class_exists($this->type) || interface_exists($this->type);

            if ($this->typeIsClass) {
                foreach ($items as $i => $item) {
                    if (!($item instanceof $this->type)) {
                        throw new InvalidArgumentException("The item (index: $i) doesn't match the type '{$this->type}'.");
                    }
                }

                return;
            }

            $this->normalisedType ??= strtolower($this->type);

            foreach ($items as $i => $item) {
                $valid = match ($this->normalisedType) {
                    "int", "integer" => is_int($item),
                    "float", "double" => is_float($item),
                    "string" => is_string($item),
                    "bool", "boolean" => is_bool($item),
                    "array" => is_array($item),
                    "callable" => is_callable($item),
                    "object" => is_object($item),
                    default => throw new InvalidArgumentException(
                        "Unknown type '{$this->type}' for type enforcement."
                    )
                };

                if (!$valid) {
                    $actual = is_object($item) ? get_class($item) : gettype($item);
                    throw new InvalidArgumentException(
                        "The item (index: $i) is of type '{$actual}' but expected '{$this->type}'."
                    );
                }
            }
        }

        /**
         * Guards a capped collection against exceeding the cap.
         * @throws LogicException If the collection is about to exceed the cap.
         */
        protected function guardCap (int $countToAdd) : void {
            if ($this->cap === null) return;
        
            $newSize = $this->count() + $countToAdd;
            if ($newSize > $this->cap) {
                throw new LogicException("Collection capacity of {$this->cap} exceeded.");
            }
        }

        /**
         * Guards a frozen collection against mutations.
         * @throws LogicException If the collection is frozen.
         */
        protected function guardFrozen () : void {
            if (!$this->frozen) return;

            throw new LogicException("A frozen collection cannot be modified.");
        }

        /**
         * Adds one or multiple items to a collection.
         * @param mixed ...$items The items to add.
         * @return static The collection.
         */
        public function add (mixed ...$items) : static {
            if (empty($items)) return $this;
            $this->guardFrozen();
            $this->guardCap(count($items));
            $this->enforceType(...$items);
            foreach ($items as $item) {
                $this->items[] = $item;
            }

            Emitter::create()
                ->with(items: array_values($items), collection: $this)
                ->emit(Signal::COLLECTION_ITEM_ADDED);

            return $this;
        }

        /**
         * Splits a collection into smaller collections of a given size.
         * @param int $size The maximum number of items per chunk.
         * @return array<int, static> An array of collections.
         * @throws InvalidArgumentException If the size is less than or equal to zero.
         */
        public function chunk (int $size) : array {
            if ($size <= 0) {
                throw new InvalidArgumentException("Chunk size must be greater than zero.");
            }

            $chunks = [];

            foreach (array_chunk($this->items, $size) as $chunk) {
                $chunks[] = $this->createInstance($chunk);
            }

            return $chunks;
        }

        /**
         * Checks whether an item exists in a collection using strict equality.
         * A focused alias of {@see has()} without a custom comparator.
         * @param mixed $item The item to search for.
         * @return bool Whether the item exists in the collection.
         */
        public function contains (mixed $item) : bool {
            return $this->has($item);
        }

        /**
         * Gets the size of a collection.
         * @return int The size of the collection.
         */
        public function count () : int {
            return $this->getSize();
        }

        /**
         * Creates a new collection with duplicate items removed.
         * An optional key selector determines uniqueness by a projected value.
         * Without a key selector, strict equality is used (SPL hash for objects, serialise for scalars).
         * @param callable|null $keySelector An optional callback returning the uniqueness key per item.
         * @return static A new collection of unique items.
         */
        public function deduplicate (?callable $keySelector = null) : static {
            $seen = [];
            $items = [];

            foreach ($this->items as $item) {
                if (isset($keySelector)) {
                    $key = (string) $keySelector($item);
                }
                else {
                    $key = is_object($item) ? 'o:' . spl_object_hash($item) : 's:' . serialize($item);
                }

                if (isset($seen[$key])) continue;

                $seen[$key] = true;
                $items[] = $item;
            }

            return $this->createInstance($items);
        }

        /**
         * Iterates over each item of a collection, invoking a callback for each one.
         * @param callable $callback A callback receiving the item and its index.
         * @return static The collection.
         */
        public function each (callable $callback) : static {
            foreach ($this->items as $i => $item) {
                $callback($item, $i);
            }
            return $this;
        }

        /**
         * Checks whether every item of a collection satisfies a predicate.
         * Returns true for an empty collection (vacuous truth).
         * @param callable $predicate A predicate receiving the item and its index.
         * @return bool Whether every item satisfies the predicate.
         */
        public function every (callable $predicate) : bool {
            foreach ($this->items as $i => $item) {
                if (!$predicate($item, $i)) return false;
            }
            return true;
        }

        /**
         * Filters a collection through the use of a predicate.
         * @param callable $predicate A predicate.
         * @return static A new collection with items that pass the predicate.
         */
        public function filter (callable $predicate) : static {
            $items = [];
            foreach ($this->items as $i => $item) {
                if (!$predicate($item, $i)) continue;
                $items[] = $item;
            }
            return $this->createInstance($items);
        }

        /**
         * Finds the first item of a collection satisfying a predicate.
         * @param callable $predicate A predicate receiving the item and its index.
         * @return mixed The first matching item, or null if none found.
         */
        public function find (callable $predicate) : mixed {
            foreach ($this->items as $i => $item) {
                if ($predicate($item, $i)) return $item;
            }
            return null;
        }

        /**
         * Finds the last item of a collection satisfying a predicate.
         * @param callable $predicate A predicate receiving the item and its index.
         * @return mixed The last matching item, or null if none found.
         */
        public function findLast (callable $predicate) : mixed {
            foreach (array_reverse($this->items, true) as $i => $item) {
                if ($predicate($item, $i)) return $item;
            }
            return null;
        }

        /**
         * Freezes a collection so it becomes immutable.
         * @return static The collection.
         */
        public function freeze () : static {
            $this->frozen = true;

            Emitter::create()
                ->with(collection: $this)
                ->emit(Signal::COLLECTION_FROZEN);

            return $this;
        }

        /**
         * Creates a new collection.
         * @param array $items The items to add to the collection.
         * @param string|null $type The type of items the collection will enforce.
         * @param int|null $cap The maximum capacity of the collection.
         * @param bool $frozen Whether the collection should be frozen.
         * @return static A new collection.
         */
        public static function from (array $items, ?string $type = null, ?int $cap = null, bool $frozen = false) : static {
            return new static($items, $type, $cap, $frozen);
        }

        /**
         * Gets an item by its index.
         * @param int $index The index of the item.
         * @return mixed The item, if any.
         */
        public function get (int $index) : mixed {
            return $this->items[$index] ?? null;
        }

        /**
         * Gets all items of a collection as an array.
         * @return array The items.
         */
        public function getAll () : array {
            return $this->items;
        }

        /**
         * Gets the cap of a collection.
         * @return int|null The cap of the collection.
         */
        public function getCap () : ?int {
            return $this->cap;
        }

        /**
         * Gets the first item of a collection.
         * @return mixed The first item of the collection, if any.
         */
        public function getFirst (): mixed {
            return $this->items[0] ?? null;
        }

        /**
         * Creates an iterator for a collection.
         * @return Traversable The iterator.
         */
        public function getIterator() : Traversable {
            return new ArrayIterator($this->items);
        }

        /**
         * Gets the last item of a collection.
         * @return mixed The last item of the collection, if any.
         */
        public function getLast (): mixed {
            return $this->items ? $this->items[array_key_last($this->items)] : null;
        }

        /**
         * Gets the item with the greatest projected value in a collection.
         * Returns `null` for an empty collection.
         * @param callable|null $keySelector An optional callback returning a comparable value per item.
         * @return mixed The item with the greatest projected value, or null if the collection is empty.
         */
        public function getMax (?callable $keySelector = null) : mixed {
            if (empty($this->items)) return null;

            $maxItem = $this->items[0];
            $maxKey = isset($keySelector) ? $keySelector($maxItem) : $maxItem;

            foreach (array_slice($this->items, 1) as $item) {
                $key = isset($keySelector) ? $keySelector($item) : $item;
                if ($key > $maxKey) {
                    $maxItem = $item;
                    $maxKey = $key;
                }
            }

            return $maxItem;
        }

        /**
         * Gets the item with the smallest projected value in a collection.
         * Returns `null` for an empty collection.
         * @param callable|null $keySelector An optional callback returning a comparable value per item.
         * @return mixed The item with the smallest projected value, or null if the collection is empty.
         */
        public function getMin (?callable $keySelector = null) : mixed {
            if (empty($this->items)) return null;

            $minItem = $this->items[0];
            $minKey = isset($keySelector) ? $keySelector($minItem) : $minItem;

            foreach (array_slice($this->items, 1) as $item) {
                $key = isset($keySelector) ? $keySelector($item) : $item;
                if ($key < $minKey) {
                    $minItem = $item;
                    $minKey = $key;
                }
            }

            return $minItem;
        }

        /**
         * Gets the size of a collection.
         * @return int The size of the collection.
         */
        public function getSize () : int {
            return count($this->items);
        }

        /**
         * Gets the type enforced by a collection.
         * @return string|null The type, or `null` if no type is enforced.
         */
        public function getType () : ?string {
            return $this->type;
        }

        /**
         * Groups the items of a collection by the value returned by a callback.
         * The callback receives each item and its index, and returns the group key.
         * @param callable $callback A callback returning the group key for each item.
         * @return array<string|int, static> An associative array of collections keyed by group.
         */
        public function groupBy (callable $callback) : array {
            $groups = [];

            foreach ($this->items as $i => $item) {
                $key = $callback($item, $i);
                if (!isset($groups[$key])) $groups[$key] = $this->createInstance([]);
                $groups[$key]->add($item);
            }

            return $groups;
        }

        /**
         * Checks whether an item exists in a collection.
         * @param mixed $item An item.
         * @param callable|null $comparator A comparator to use instead of the default "strictly equals".
         * @return bool Whether the item exists in the collection.
         */
        public function has (mixed $item, ?callable $comparator = null) : bool {
            if (isset($comparator)) {
                foreach ($this->items as $_item) {
                    if (!$comparator($_item, $item)) continue;
                    return true;
                }
            }
            else {
                foreach ($this->items as $_item) {
                    if ($item !== $_item) continue;
                    return true;
                }
            }

            return false;
        }

        /**
         * Checks whether the collection has a cap.
         * @return bool Whether the collection has a cap.
         */
        public function hasCap () : bool {
            return isset($this->cap);
        }

        /**
         * Gets the index of the first occurrence of an item in a collection.
         * @param mixed $item An item.
         * @param callable|null $comparator A comparator to use instead of the default "strictly equals".
         * @return int The index of the item, or -1 if not found.
         */
        public function indexOf (mixed $item, ?callable $comparator = null) : int {
            foreach ($this->items as $i => $element) {
                $match = isset($comparator) ? $comparator($element, $item) : $element === $item;
                if ($match) return $i;
            }
            return -1;
        }

        /**
         * Checks whether a collection is empty.
         * @return bool Whether the collection is empty.
         */
        public function isEmpty () : bool {
            return empty($this->items);
        }

        /**
         * Checks whether a collection is frozen (immutable).
         * @return bool Whether the collection is frozen.
         */
        public function isFrozen () : bool {
            return $this->frozen;
        }

        /**
         * Gets the index of the last occurrence of an item in a collection.
         * @param mixed $item An item.
         * @param callable|null $comparator A comparator to use instead of the default "strictly equals".
         * @return int The index of the item, or -1 if not found.
         */
        public function lastIndexOf (mixed $item, ?callable $comparator = null) : int {
            foreach (array_reverse($this->items, true) as $i => $element) {
                $match = isset($comparator) ? $comparator($element, $item) : $element === $item;
                if ($match) return $i;
            }
            return -1;
        }

        /**
         * Transforms each item of a collection according to a callback.
         * @param callable $callback The callback.
         * @return array An array with a value per item.
         */
        public function map (callable $callback) : array {
            return array_map($callback, $this->items);
        }

        /**
         * Checks whether no item of a collection satisfies a predicate.
         * @param callable $predicate A predicate receiving the item and its index.
         * @return bool Whether no item satisfies the predicate.
         */
        public function none (callable $predicate) : bool {
            return !$this->some($predicate);
        }

        /**
         * Checks whether an item of a collection at the specified offset exists.
         * @param mixed $offset An offset.
         * @return bool Whether the item exists.
         */
        public function offsetExists ($offset) : bool {
            return isset($this->items[$offset]);
        }

        /**
         * Gets an item of a collection at the specified offset.
         * @param mixed $offset An offset.
         * @return mixed The item, if any.
         */
        public function offsetGet ($offset) : mixed {
            return $this->items[$offset] ?? null;
        }

        /**
         * Sets an item of a collection at the specified offset.
         * If the offset is `null` or does not already exist, the item is appended to preserve
         * sequential indexing. Use explicit numeric offsets only to update existing slots.
         * @param mixed $offset An offset.
         * @param mixed $value A value.
         * @throws InvalidArgumentException If the item isn't of the correct type.
         */
        public function offsetSet ($offset, $value) : void {
            $this->guardFrozen();
            $this->enforceType($value);

            $isNewSlot = $offset === null || !isset($this->items[$offset]);
            if ($isNewSlot) $this->guardCap(1);
            if ($offset === null) $this->items[] = $value;
            else $this->items[$offset] = $value;

            if ($isNewSlot) {
                Emitter::create()
                    ->with(items: [$value], collection: $this)
                    ->emit(Signal::COLLECTION_ITEM_ADDED);
            }
        }

        /**
         * Removes an item of a collection at the specified offset.
         * @param mixed $offset An offset.
         */
        public function offsetUnset ($offset) : void {
            $this->guardFrozen();
            unset($this->items[$offset]);
            $this->items = array_values($this->items);

            Emitter::create()
                ->with(collection: $this)
                ->emit(Signal::COLLECTION_ITEM_REMOVED);
        }

        /**
         * Orders a collection based on a callback called on every item.
         * @param callable $callback A callback.
         * @return static A new collection.
         */
        public function orderBy (callable $callback) : static {
            $items = $this->items;

            usort($items, function ($a, $b) use ($callback) {
                $ka = $callback($a);
                $kb = $callback($b);
                return $ka <=> $kb;
            });

            return $this->createInstance($items);
        }

        /**
         * Orders a collection in place based on a callback called on every item.
         * @param callable $callback A callback.
         * @return static The collection.
         */
        public function orderByInPlace (callable $callback) : static {
            $this->guardFrozen();

            usort($this->items, function ($a, $b) use ($callback) {
                $ka = $callback($a);
                $kb = $callback($b);
                return $ka <=> $kb;
            });

            return $this;
        }

        /**
         * Splits a collection into two collections based on a predicate.
         * The first collection contains items that satisfy the predicate;
         * the second contains items that do not.
         * @param callable $predicate A predicate receiving the item and its index.
         * @return array{0: static, 1: static} A two-element array of [passing, failing] collections.
         */
        public function partition (callable $predicate) : array {
            $passing = $this->createInstance([]);
            $failing = $this->createInstance([]);

            foreach ($this->items as $i => $item) {
                if ($predicate($item, $i)) $passing->add($item);
                else $failing->add($item);
            }

            return [$passing, $failing];
        }

        /**
         * Reduces a collection to a single value by applying a callback to each item from left to right.
         * @param callable $callback A callback receiving the accumulator and each item.
         * @param mixed $initial The initial value of the accumulator.
         * @return mixed The final accumulated value.
         */
        public function reduce (callable $callback, mixed $initial = null) : mixed {
            return array_reduce($this->items, $callback, $initial);
        }

        /**
         * Reduces a collection to a single value by applying a callback to each item from right to left.
         * @param callable $callback A callback receiving the accumulator and each item.
         * @param mixed $initial The initial value of the accumulator.
         * @return mixed The final accumulated value.
         */
        public function reduceRight (callable $callback, mixed $initial = null) : mixed {
            return array_reduce(array_reverse($this->items), $callback, $initial);
        }

        /**
         * Removes an item by its index.
         * @param int $index The index of an item.
         * @return static The collection.
         */
        public function remove (int $index) : static {
            $this->guardFrozen();
            if (isset($this->items[$index])) {
                unset($this->items[$index]);
                $this->items = array_values($this->items);

                Emitter::create()
                    ->with(collection: $this)
                    ->emit(Signal::COLLECTION_ITEM_REMOVED);
            }
            return $this;
        }

        /**
         * Creates a new collection with items in reverse order.
         * @return static A new reversed collection.
         */
        public function reverse () : static {
            return $this->createInstance(array_reverse($this->items));
        }

        /**
         * Extracts a contiguous sub-range of a collection.
         * Negative offsets count from the end of the collection.
         * @param int $offset The zero-based starting index.
         * @param int|null $length The number of items to include (null = all remaining).
         * @return static A new collection containing the specified items.
         */
        public function slice (int $offset, ?int $length = null) : static {
            return $this->createInstance(array_slice($this->items, $offset, $length));
        }

        /**
         * Checks whether at least one item of a collection satisfies a predicate.
         * @param callable $predicate A predicate receiving the item and its index.
         * @return bool Whether at least one item satisfies the predicate.
         */
        public function some (callable $predicate) : bool {
            foreach ($this->items as $i => $item) {
                if ($predicate($item, $i)) return true;
            }
            return false;
        }

        /**
         * Sorts a collection using a comparator.
         * @param callable $comparator A comparator.
         * @return static A new collection.
         */
        public function sort (callable $comparator) : static {
            $items = $this->items;
            usort($items, $comparator);
            return $this->createInstance($items);
        }

        /**
         * Sorts a collection in place using a comparator.
         * @param callable $comparator A comparator.
         * @return static The collection.
         */
        public function sortInPlace (callable $comparator) : static {
            $this->guardFrozen();
            usort($this->items, $comparator);
            return $this;
        }

        /**
         * Sums the values of a collection, optionally projected through a callback.
         * @param callable|null $callback An optional callback extracting the numeric value from each item.
         * @return int|float The total sum.
         */
        public function sum (?callable $callback = null) : int|float {
            $total = 0;
            foreach ($this->items as $item) {
                $total += isset($callback) ? $callback($item) : $item;
            }
            return $total;
        }

        /**
         * Passes the collection to a callback for side-effects without interrupting the chain.
         * Useful for debugging or logging within a fluent chain.
         * @param callable $callback A callback receiving the collection.
         * @return static The collection.
         */
        public function tap (callable $callback) : static {
            $callback($this);
            return $this;
        }

        /**
         * Unfreezes a collection so it becomes mutable.
         * @return static The collection.
         */
        public function unfreeze () : static {
            $this->frozen = false;

            Emitter::create()
                ->with(collection: $this)
                ->emit(Signal::COLLECTION_UNFROZEN);

            return $this;
        }

        /**
         * Creates a new collection with the addition of one or multiple items.
         * @param mixed ...$items The items to include.
         * @return static A new collection.
         */
        public function with (mixed ...$items) : static {
            return $this->createInstance([...$this->items, ...$items]);
        }

        /**
         * Creates a new collection with a specified capacity.
         * @param int $cap The maximum capacity of the collection.
         * @return static A new collection.
         */
        public static function withCap (int $cap) : static {
            return new static([], null, $cap);
        }

        /**
         * Creates a new collection with specified items.
         * @param array $items The items to add to the collection.
         * @return static A new collection.
         */
        public static function withItems (array $items) : static {
            return new static($items);
        }

        /**
         * Creates a new collection with the exclusion of one or multiple items.
         * Uses a hash-map lookup table so the operation runs in O(n + m) rather than O(n × m).
         * Objects are identified by their SPL object hash; scalars by their serialised form.
         * @param mixed ...$targets The items to exclude.
         * @return static A new collection.
         */
        public function without (mixed ...$targets) : static {
            $lookup = [];

            foreach ($targets as $target) {
                $key = is_object($target) ? "o:" . spl_object_hash($target) : "s:" . serialize($target);
                $lookup[$key] = true;
            }

            $includedItems = [];

            foreach ($this->items as $element) {
                $key = is_object($element) ? "o:" . spl_object_hash($element) : "s:" . serialize($element);
                if (!isset($lookup[$key])) $includedItems[] = $element;
            }

            return $this->createInstance($includedItems);
        }

        /**
         * Creates a new collection with the exclusion of one or multiple items identified by their indices.
         * @param int ...$indices The indices of the items to exclude.
         * @return static A new collection.
         */
        public function withoutIndices (int ...$indices) : static {
            $lookup = array_flip($indices);
            $items = [];

            foreach ($this->items as $i => $item) {
                if (isset($lookup[$i])) continue;
                $items[] = $item;
            }

            return $this->createInstance($items);
        }

        /**
         * Creates a new collection with a specified type.
         * @param string $type The type of items the collection will enforce.
         * @return static A new collection.
         */
        public static function withType (string $type) : static {
            return new static([], $type, null);
        }
    }
?>
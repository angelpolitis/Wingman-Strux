<?php
    /**
     * Project Name:    Wingman Strux - Multi Map
     * Created by:      Angel Politis
     * Creation Date:   Mar 16 2026
     * Last Modified:   Mar 17 2026
     *
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Strux namespace.
    namespace Wingman\Strux;

    # Import the following classes and interfaces to the current scope.
    use ArrayIterator;
    use Countable;
    use InvalidArgumentException;
    use IteratorAggregate;
    use Traversable;
    use Wingman\Strux\Bridge\Corvus\Emitter;
    use Wingman\Strux\Bridge\Verix\Validator;
    use Wingman\Strux\Enums\Signal;

    /**
     * Represents a map where a single key can hold multiple values.
     *
     * Unlike HashMap, MultiMap does not replace an existing entry when a key is written again —
     * it appends to the key's value bucket instead. This makes it ideal for one-to-many
     * relationships such as route → middleware list, tag → article IDs, or event name →
     * listener callbacks.
     *
     * set() always appends; use replace() to overwrite an entire bucket.
     * getSize() returns the number of distinct keys. countAll() returns the total number
     * of individual values stored across all buckets.
     *
     * @package Wingman\Strux
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     * @template K of int|string
     * @template V
     */
    class MultiMap implements Countable, IteratorAggregate {
        /**
         * Creates a new multi map.
         * @param array<K, V|list<V>> $data Initial data. Values may be single items or arrays of items.
         * @param string|null $type The type constraint for individual values.
         */
        public function __construct (array $data = [], ?string $type = null) {
            if (isset($type)) $this->type = $type;

            foreach ($data as $key => $values) {
                $this->set($key, ...(is_array($values) ? $values : [$values]));
            }
        }

        /**
         * The internal storage: key → ordered list of values.
         * @var array<K, list<V>>
         */
        private array $data = [];

        /**
         * The cached normalised (lowercased) form of the enforced type name.
         * Only populated for primitive type enforcement.
         * @var string|null
         */
        private ?string $normalisedType = null;

        /**
         * Whether the enforced type resolves to a class or interface.
         * Lazily computed on the first invocation of enforceType.
         * @var bool|null
         */
        private ?bool $typeIsClass = null;

        /**
         * The type that every individual value in the map must conform to, or null for no enforcement.
         * @var string|null
         */
        protected ?string $type = null;

        /**
         * Enforces the map's type constraint against each given value.
         * The class/interface vs. primitive distinction and the normalised type name are
         * lazily cached after the first invocation.
         * @param mixed ...$items The values to validate.
         * @throws InvalidArgumentException If any value does not conform to the type.
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
                        throw new InvalidArgumentException("The value (index: $i) doesn't match the type '{$this->type}'.");
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
                    default => throw new InvalidArgumentException("Unknown type '{$this->type}' for type enforcement.")
                };

                if (!$valid) {
                    $actual = is_object($item) ? get_class($item) : gettype($item);
                    throw new InvalidArgumentException("The value (index: $i) is of type '{$actual}' but expected '{$this->type}'.");
                }
            }
        }

        /**
         * Gets the number of distinct keys in the map.
         * @return int The key count.
         */
        public function count () : int {
            return $this->getSize();
        }

        /**
         * Gets the total number of individual values across all buckets.
         * @return int The total value count.
         */
        public function countAll () : int {
            $total = 0;

            foreach ($this->data as $bucket) {
                $total += count($bucket);
            }

            return $total;
        }

        /**
         * Invokes the given callback for each key-bucket pair and returns the map unchanged.
         * The callback receives the bucket (array of values), the key, and the map as its arguments.
         * @param callable(list<V>, K, static): void $callback The callback to invoke.
         * @return static The map.
         */
        public function each (callable $callback) : static {
            foreach ($this->data as $key => $bucket) {
                $callback($bucket, $key, $this);
            }

            return $this;
        }

        /**
         * Determines whether all key-bucket pairs satisfy the given predicate.
         * @param callable(list<V>, K): bool $predicate A predicate receiving the bucket and key.
         * @return bool Whether all pairs pass.
         */
        public function every (callable $predicate) : bool {
            foreach ($this->data as $key => $bucket) {
                if (!$predicate($bucket, $key)) return false;
            }

            return true;
        }

        /**
         * Finds the first bucket (in insertion order) whose key-bucket pair satisfies the given predicate.
         * @param callable(list<V>, K): bool $predicate A predicate receiving the bucket and key.
         * @return list<V>|null The first matching bucket, or null if none is found.
         */
        public function find (callable $predicate) : ?array {
            foreach ($this->data as $key => $bucket) {
                if ($predicate($bucket, $key)) return $bucket;
            }

            return null;
        }

        /**
         * Creates a new Collection containing every individual value from every bucket, in insertion order.
         * @return Collection<V> The flattened collection.
         */
        public function flatten () : Collection {
            $items = [];

            foreach ($this->data as $bucket) {
                foreach ($bucket as $value) {
                    $items[] = $value;
                }
            }

            return new Collection($items);
        }

        /**
         * Creates a new multi map pre-loaded with the given data.
         * @param array<K, V|list<V>> $data Initial data.
         * @param string|null $type The type constraint for individual values.
         * @return static The created map.
         */
        public static function from (array $data, ?string $type = null) : static {
            return new static($data, $type);
        }

        /**
         * Gets the bucket of values associated with the given key.
         * @param int|string $key The key to look up.
         * @return list<V> The bucket, or an empty array if the key does not exist.
         */
        public function get (int|string $key) : array {
            return $this->data[$key] ?? [];
        }

        /**
         * Gets the first value in the bucket for the given key, or null if none exists.
         * @param int|string $key The key to look up.
         * @return V|null The first value, or null.
         */
        public function getFirst (int|string $key) : mixed {
            return $this->data[$key][0] ?? null;
        }

        /**
         * Gets an iterator that yields key => bucket pairs in insertion order.
         * @return Traversable<K, list<V>> The iterator.
         */
        public function getIterator () : Traversable {
            return new ArrayIterator($this->data);
        }

        /**
         * Gets all keys in the map as a plain array.
         * @return K[] The keys.
         */
        public function getKeys () : array {
            return array_keys($this->data);
        }

        /**
         * Gets the number of distinct keys in the map.
         * @return int The size.
         */
        public function getSize () : int {
            return count($this->data);
        }

        /**
         * Gets the type constraint enforced by the map, or null if unrestricted.
         * @return string|null The type.
         */
        public function getType () : ?string {
            return $this->type;
        }

        /**
         * Gets all individual values from all buckets as a plain flat array, in insertion order.
         * @return V[] The values.
         */
        public function getValues () : array {
            $values = [];

            foreach ($this->data as $bucket) {
                foreach ($bucket as $value) {
                    $values[] = $value;
                }
            }

            return $values;
        }

        /**
         * Determines whether the given key exists in the map with at least one value.
         * @param int|string $key The key to check.
         * @return bool Whether the key has values.
         */
        public function has (int|string $key) : bool {
            return isset($this->data[$key]) && $this->data[$key] !== [];
        }

        /**
         * Determines whether the given value is present in the bucket for the given key.
         * @param int|string $key The key whose bucket to inspect.
         * @param mixed $value The value to search for.
         * @return bool Whether the value is present.
         */
        public function hasValue (int|string $key, mixed $value) : bool {
            return isset($this->data[$key]) && in_array($value, $this->data[$key], true);
        }

        /**
         * Determines whether the map contains no keys.
         * @return bool Whether the map is empty.
         */
        public function isEmpty () : bool {
            return $this->getSize() === 0;
        }

        /**
         * Determines whether no key-bucket pairs satisfy the given predicate.
         * Delegates to the inverse of some().
         * @param callable(list<V>, K): bool $predicate A predicate receiving the bucket and key.
         * @return bool Whether no pairs pass.
         */
        public function none (callable $predicate) : bool {
            return !$this->some($predicate);
        }

        /**
         * Removes the entire bucket associated with the given key.
         * Has no effect if the key does not exist.
         * @param int|string $key The key to remove.
         * @return static The map.
         */
        public function remove (int|string $key) : static {
            unset($this->data[$key]);
            Emitter::create()->with(key: $key, map: $this)->emit(Signal::MAP_ENTRY_REMOVED);
            return $this;
        }

        /**
         * Removes a specific value from the bucket of the given key.
         * If the bucket becomes empty after removal, the key is also removed.
         * Has no effect if the key or value does not exist.
         * @param int|string $key The key whose bucket to modify.
         * @param mixed $value The value to remove.
         * @return static The map.
         */
        public function removeValue (int|string $key, mixed $value) : static {
            if (!isset($this->data[$key])) return $this;

            $this->data[$key] = array_values(
                array_filter($this->data[$key], fn ($v) => $v !== $value)
            );

            if ($this->data[$key] === []) unset($this->data[$key]);
            Emitter::create()->with(key: $key, map: $this)->emit(Signal::MAP_ENTRY_REMOVED);

            return $this;
        }

        /**
         * Replaces the entire bucket for the given key with the provided values.
         * If no values are given, the key is removed entirely.
         * @param int|string $key The key whose bucket to replace.
         * @param V ...$values The new values.
         * @return static The map.
         * @throws InvalidArgumentException If any value fails type enforcement.
         */
        public function replace (int|string $key, mixed ...$values) : static {
            if ($values === []) {
                unset($this->data[$key]);
                Emitter::create()->with(key: $key, map: $this)->emit(Signal::MAP_ENTRY_REMOVED);
                return $this;
            }

            $this->enforceType(...$values);
            $this->data[$key] = array_values($values);
            Emitter::create()->with(key: $key, values: $this->data[$key], map: $this)->emit(Signal::MAP_ENTRY_SET);

            return $this;
        }

        /**
         * Appends one or more values to the bucket for the given key.
         * If the key does not exist, it is created automatically.
         * @param int|string $key The key.
         * @param V ...$values The values to append.
         * @return static The map.
         * @throws InvalidArgumentException If any value fails type enforcement.
         */
        public function set (int|string $key, mixed ...$values) : static {
            $this->enforceType(...$values);

            if (!isset($this->data[$key])) $this->data[$key] = [];

            foreach ($values as $value) {
                $this->data[$key][] = $value;
            }

            Emitter::create()->with(key: $key, values: array_values($values), map: $this)->emit(Signal::MAP_ENTRY_SET);

            return $this;
        }

        /**
         * Determines whether at least one key-bucket pair satisfies the given predicate.
         * @param callable(list<V>, K): bool $predicate A predicate receiving the bucket and key.
         * @return bool Whether any pair passes.
         */
        public function some (callable $predicate) : bool {
            foreach ($this->data as $key => $bucket) {
                if ($predicate($bucket, $key)) return true;
            }

            return false;
        }

        /**
         * Invokes the given callback with the map and returns the map unchanged.
         * Useful for debugging or side-effectful inspection in a method chain.
         * @param callable(static): void $callback The callback to invoke.
         * @return static The map.
         */
        public function tap (callable $callback) : static {
            $callback($this);
            return $this;
        }

        /**
         * Gets all key-bucket pairs as a plain associative array.
         * @return array<K, list<V>> The data.
         */
        public function toArray () : array {
            return $this->data;
        }

        /**
         * Creates a new multi map with the given type constraint for individual values.
         * @param string $type The type of values the map will enforce.
         * @return static The map.
         */
        public static function withType (string $type) : static {
            return new static([], $type);
        }
    }
?>
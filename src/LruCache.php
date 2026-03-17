<?php
    /**
     * Project Name:    Wingman Strux - LRU Cache
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
     * Represents a fixed-capacity key-value cache with Least-Recently-Used eviction.
     *
     * Internally the class maintains a PHP associative array whose insertion order encodes
     * access recency: the oldest (coldest) entry sits at the front and the newest (hottest)
     * entry sits at the back. On every get() or put(), the accessed entry is unset and
     * re-inserted at the back in O(1) time. When the cache is full and a new entry arrives,
     * the front entry is evicted automatically.
     *
     * has() is a pure read — it does not affect LRU order.
     *
     * Typical use-cases: query-result caches, rendered template fragment caches,
     * resolved dependency caches, recently-used file-handle registries.
     *
     * @package Wingman\Strux
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     * @template V
     */
    class LruCache implements Countable, IteratorAggregate {
        /**
         * Creates a new LRU cache with the given fixed capacity.
         * @param int $cap The maximum number of entries the cache can hold. Must be at least 1.
         * @param string|null $type The type constraint for cached values.
         * @throws InvalidArgumentException If the capacity is less than 1.
         */
        public function __construct (int $cap, ?string $type = null) {
            if ($cap < 1) {
                throw new InvalidArgumentException("LruCache capacity must be at least 1, {$cap} given.");
            }

            $this->cap = $cap;
            if (isset($type)) $this->type = $type;
        }

        /**
         * The fixed maximum capacity of the cache.
         * @var int
         */
        private int $cap;

        /**
         * The internal ordered store.
         * Entries are ordered from least-recently-used (front) to most-recently-used (back).
         * @var array<string|int, V>
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
         * The type that every cached value must conform to, or null for no enforcement.
         * @var string|null
         */
        protected ?string $type = null;

        /**
         * Enforces the cache's type constraint against each given value.
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
         * Gets the number of entries currently in the cache.
         * @return int The entry count.
         */
        public function count () : int {
            return $this->getSize();
        }

        /**
         * Invokes the given callback for each entry from least-recently-used to most-recently-used.
         * The callback receives the value, key, and cache as its arguments.
         * This operation does not affect the LRU order of any entry.
         * @param callable(V, int|string, static): void $callback The callback to invoke.
         * @return static The cache.
         */
        public function each (callable $callback) : static {
            foreach ($this->data as $key => $value) {
                $callback($value, $key, $this);
            }

            return $this;
        }

        /**
         * Manually evicts the entry with the given key from the cache.
         * Has no effect if the key does not exist.
         * @param int|string $key The key to evict.
         * @return static The cache.
         */
        public function evict (int|string $key) : static {
            unset($this->data[$key]);
            Emitter::create()->with(key: $key, cache: $this)->emit(Signal::MAP_ENTRY_REMOVED);
            return $this;
        }

        /**
         * Determines whether all entries satisfy the given predicate.
         * Evaluated from least-recently-used to most-recently-used. Does not affect LRU order.
         * @param callable(V, int|string): bool $predicate A predicate receiving the value and key.
         * @return bool Whether all entries pass.
         */
        public function every (callable $predicate) : bool {
            foreach ($this->data as $key => $value) {
                if (!$predicate($value, $key)) return false;
            }

            return true;
        }

        /**
         * Finds the first entry (from least-recently-used) that satisfies the given predicate.
         * Does not affect LRU order.
         * @param callable(V, int|string): bool $predicate A predicate receiving the value and key.
         * @return V|null The first matching value, or null if none is found.
         */
        public function find (callable $predicate) : mixed {
            foreach ($this->data as $key => $value) {
                if ($predicate($value, $key)) return $value;
            }

            return null;
        }

        /**
         * Creates a new LRU cache pre-loaded with the given entries.
         * If no capacity is specified, it defaults to the number of entries (minimum 1).
         * @param array<string|int, V> $data The initial key-value entries (first = oldest).
         * @param int|null $cap The fixed capacity. Defaults to max(1, count($data)).
         * @param string|null $type The type constraint for cached values.
         * @return static The created cache.
         */
        public static function from (array $data, ?int $cap = null, ?string $type = null) : static {
            $cache = new static($cap ?? max(1, count($data)), $type);

            foreach ($data as $key => $value) {
                $cache->put($key, $value);
            }

            return $cache;
        }

        /**
         * Gets the value for the given key and marks it as most-recently-used.
         * Returns null if the key does not exist.
         * @param int|string $key The key to retrieve.
         * @return V|null The cached value, or null.
         */
        public function get (int|string $key) : mixed {
            if (!array_key_exists($key, $this->data)) return null;

            $value = $this->data[$key];
            unset($this->data[$key]);
            $this->data[$key] = $value;

            return $value;
        }

        /**
         * Gets the fixed capacity of the cache.
         * @return int The capacity.
         */
        public function getCap () : int {
            return $this->cap;
        }

        /**
         * Gets an iterator that yields key => value pairs from least-recently-used to most-recently-used.
         * Iteration does not affect the LRU order of any entry.
         * @return Traversable<string|int, V> The iterator.
         */
        public function getIterator () : Traversable {
            return new ArrayIterator($this->data);
        }

        /**
         * Gets all keys currently in the cache, ordered from least-recently-used to most-recently-used.
         * @return (string|int)[] The keys.
         */
        public function getKeys () : array {
            return array_keys($this->data);
        }

        /**
         * Gets the number of entries currently in the cache.
         * @return int The size.
         */
        public function getSize () : int {
            return count($this->data);
        }

        /**
         * Gets the type constraint enforced by the cache, or null if unrestricted.
         * @return string|null The type.
         */
        public function getType () : ?string {
            return $this->type;
        }

        /**
         * Gets all values currently in the cache, ordered from least-recently-used to most-recently-used.
         * Does not affect LRU order.
         * @return V[] The values.
         */
        public function getValues () : array {
            return array_values($this->data);
        }

        /**
         * Determines whether the given key exists in the cache without affecting LRU order.
         * @param int|string $key The key to check.
         * @return bool Whether the key is cached.
         */
        public function has (int|string $key) : bool {
            return array_key_exists($key, $this->data);
        }

        /**
         * Determines whether the cache contains no entries.
         * @return bool Whether the cache is empty.
         */
        public function isEmpty () : bool {
            return $this->getSize() === 0;
        }

        /**
         * Determines whether the cache has reached its fixed capacity.
         * @return bool Whether the cache is full.
         */
        public function isFull () : bool {
            return $this->getSize() === $this->cap;
        }

        /**
         * Determines whether no entries satisfy the given predicate.
         * Delegates to the inverse of some().
         * @param callable(V, int|string): bool $predicate A predicate receiving the value and key.
         * @return bool Whether no entries pass.
         */
        public function none (callable $predicate) : bool {
            return !$this->some($predicate);
        }

        /**
         * Stores a value under the given key and marks it as most-recently-used.
         * If the key already exists, its value is updated and it is promoted to most-recently-used.
         * If the cache is full and the key is new, the least-recently-used entry is evicted first.
         * @param int|string $key The cache key.
         * @param V $value The value to cache.
         * @return static The cache.
         * @throws InvalidArgumentException If the value fails type enforcement.
         */
        public function put (int|string $key, mixed $value) : static {
            $this->enforceType($value);

            if (array_key_exists($key, $this->data)) {
                unset($this->data[$key]);
            } elseif (count($this->data) >= $this->cap) {
                reset($this->data);
                unset($this->data[key($this->data)]);
            }

            $this->data[$key] = $value;
            Emitter::create()->with(key: $key, value: $value, cache: $this)->emit(Signal::MAP_ENTRY_SET);

            return $this;
        }

        /**
         * Determines whether at least one entry satisfies the given predicate.
         * Does not affect LRU order.
         * @param callable(V, int|string): bool $predicate A predicate receiving the value and key.
         * @return bool Whether any entry passes.
         */
        public function some (callable $predicate) : bool {
            foreach ($this->data as $key => $value) {
                if ($predicate($value, $key)) return true;
            }

            return false;
        }

        /**
         * Invokes the given callback with the cache and returns the cache unchanged.
         * Useful for debugging or side-effectful inspection in a method chain.
         * @param callable(static): void $callback The callback to invoke.
         * @return static The cache.
         */
        public function tap (callable $callback) : static {
            $callback($this);
            return $this;
        }

        /**
         * Gets all entries as a plain associative array, ordered from least-recently-used
         * to most-recently-used.
         * @return array<string|int, V> The entries.
         */
        public function toArray () : array {
            return $this->data;
        }

        /**
         * Creates a new empty LRU cache with the given fixed capacity.
         * @param int $cap The maximum number of entries the cache can hold.
         * @return static The cache.
         */
        public static function withCap (int $cap) : static {
            return new static($cap);
        }
    }
?>
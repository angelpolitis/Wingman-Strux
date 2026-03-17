<?php
    /**
     * Project Name:    Wingman Strux - Bidirectional Map
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
     * Represents a bijective (one-to-one) map that supports O(1) lookup in both directions.
     *
     * Internally the class maintains two PHP arrays — a forward index (key → value) and a
     * reverse index (value → key) — so that get() and getKey() are both constant-time array
     * lookups. Because both indices use PHP array keys, values are constrained to int|string
     * (the only valid PHP array key types).
     *
     * Bijectivity is maintained automatically: assigning a key that already exists replaces its
     * old reverse entry, and assigning a value that already belongs to another key silently evicts
     * that key to preserve the one-to-one relationship.
     *
     * Typical use-cases: locale code ↔ locale name, HTTP status ↔ reason phrase,
     * permission slug ↔ display label, database column ↔ object property.
     *
     * @package Wingman\Strux
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     * @template K of int|string
     * @template V of int|string
     */
    class BidirectionalMap implements Countable, IteratorAggregate {
        /**
         * Creates a new bidirectional map.
         * @param array<K, V> $data An associative array of key => value pairs to pre-load.
         * @param string|null $type The type constraint for values.
         */
        public function __construct (array $data = [], ?string $type = null) {
            if (isset($type)) $this->type = $type;

            foreach ($data as $key => $value) {
                $this->set($key, $value);
            }
        }

        /**
         * The forward index mapping each key to its value.
         * @var array<K, V>
         */
        private array $forward = [];

        /**
         * The cached normalised (lowercased) form of the enforced type name.
         * Only populated for primitive type enforcement.
         * @var string|null
         */
        private ?string $normalisedType = null;

        /**
         * The reverse index mapping each value back to its key.
         * @var array<V, K>
         */
        private array $reverse = [];

        /**
         * Whether the enforced type resolves to a class or interface.
         * Lazily computed on the first invocation of enforceType.
         * @var bool|null
         */
        private ?bool $typeIsClass = null;

        /**
         * The type that every value in the map must conform to, or null for no enforcement.
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
         * Gets the number of key-value pairs in the map.
         * @return int The item count.
         */
        public function count () : int {
            return $this->getSize();
        }

        /**
         * Invokes the given callback for each key-value pair and returns the map unchanged.
         * The callback receives the value, key, and map as its arguments.
         * @param callable(V, K, static): void $callback The callback to invoke.
         * @return static The map.
         */
        public function each (callable $callback) : static {
            foreach ($this->forward as $key => $value) {
                $callback($value, $key, $this);
            }

            return $this;
        }

        /**
         * Determines whether all key-value pairs satisfy the given predicate.
         * @param callable(V, K): bool $predicate A predicate receiving the value and key.
         * @return bool Whether all pairs pass.
         */
        public function every (callable $predicate) : bool {
            foreach ($this->forward as $key => $value) {
                if (!$predicate($value, $key)) return false;
            }

            return true;
        }

        /**
         * Finds the first value (in insertion order) that satisfies the given predicate.
         * @param callable(V, K): bool $predicate A predicate receiving the value and key.
         * @return V|null The first matching value, or null if none is found.
         */
        public function find (callable $predicate) : mixed {
            foreach ($this->forward as $key => $value) {
                if ($predicate($value, $key)) return $value;
            }

            return null;
        }

        /**
         * Creates a new bidirectional map pre-loaded with the given key-value pairs.
         * @param array<K, V> $data An associative array of key => value pairs.
         * @param string|null $type The type constraint for values.
         * @return static The created map.
         */
        public static function from (array $data, ?string $type = null) : static {
            return new static($data, $type);
        }

        /**
         * Gets the value associated with the given key, or null if the key does not exist.
         * @param int|string $key The key to look up.
         * @return V|null The value, or null.
         */
        public function get (int|string $key) : mixed {
            return $this->forward[$key] ?? null;
        }

        /**
         * Gets an iterator that yields key => value pairs in insertion order.
         * @return Traversable<K, V> The iterator.
         */
        public function getIterator () : Traversable {
            return new ArrayIterator($this->forward);
        }

        /**
         * Gets the key associated with the given value, or null if the value does not exist.
         * @param int|string $value The value to look up.
         * @return K|null The key, or null.
         */
        public function getKey (int|string $value) : mixed {
            return $this->reverse[$value] ?? null;
        }

        /**
         * Gets all keys in the map as a plain array.
         * @return K[] The keys.
         */
        public function getKeys () : array {
            return array_keys($this->forward);
        }

        /**
         * Gets the number of key-value pairs in the map.
         * @return int The size.
         */
        public function getSize () : int {
            return count($this->forward);
        }

        /**
         * Gets the type constraint enforced by the map, or null if unrestricted.
         * @return string|null The type.
         */
        public function getType () : ?string {
            return $this->type;
        }

        /**
         * Gets all values in the map as a plain array in insertion order.
         * @return V[] The values.
         */
        public function getValues () : array {
            return array_values($this->forward);
        }

        /**
         * Determines whether the given key exists in the map.
         * @param int|string $key The key to check.
         * @return bool Whether the key exists.
         */
        public function has (int|string $key) : bool {
            return array_key_exists($key, $this->forward);
        }

        /**
         * Determines whether the given value exists in the map.
         * @param int|string $value The value to check.
         * @return bool Whether the value exists.
         */
        public function hasValue (int|string $value) : bool {
            return array_key_exists($value, $this->reverse);
        }

        /**
         * Determines whether the map contains no key-value pairs.
         * @return bool Whether the map is empty.
         */
        public function isEmpty () : bool {
            return $this->getSize() === 0;
        }

        /**
         * Determines whether no key-value pairs satisfy the given predicate.
         * Delegates to the inverse of some().
         * @param callable(V, K): bool $predicate A predicate receiving the value and key.
         * @return bool Whether no pairs pass.
         */
        public function none (callable $predicate) : bool {
            return !$this->some($predicate);
        }

        /**
         * Removes the key-value pair identified by the given key.
         * Has no effect if the key does not exist.
         * @param int|string $key The key to remove.
         * @return static The map.
         */
        public function remove (int|string $key) : static {
            if (!array_key_exists($key, $this->forward)) return $this;

            unset($this->reverse[$this->forward[$key]], $this->forward[$key]);
            Emitter::create()->with(key: $key, map: $this)->emit(Signal::MAP_ENTRY_REMOVED);

            return $this;
        }

        /**
         * Removes the key-value pair identified by the given value.
         * Has no effect if the value does not exist.
         * @param int|string $value The value to remove.
         * @return static The map.
         */
        public function removeByValue (int|string $value) : static {
            if (!array_key_exists($value, $this->reverse)) return $this;

            unset($this->forward[$this->reverse[$value]], $this->reverse[$value]);
            Emitter::create()->with(key: $value, map: $this)->emit(Signal::MAP_ENTRY_REMOVED);

            return $this;
        }

        /**
         * Stores a key-value pair in the map, maintaining bijectivity automatically.
         * If the key already exists, its old value is evicted from the reverse index.
         * If the value already belongs to another key, that key is evicted from the forward
         * index to preserve the one-to-one relationship.
         * @param int|string $key The key.
         * @param int|string $value The value.
         * @return static The map.
         * @throws InvalidArgumentException If the value fails type enforcement.
         */
        public function set (int|string $key, int|string $value) : static {
            $this->enforceType($value);

            if (array_key_exists($key, $this->forward)) {
                unset($this->reverse[$this->forward[$key]]);
            }

            if (array_key_exists($value, $this->reverse)) {
                unset($this->forward[$this->reverse[$value]]);
            }

            $this->forward[$key] = $value;
            $this->reverse[$value] = $key;
            Emitter::create()->with(key: $key, value: $value, map: $this)->emit(Signal::MAP_ENTRY_SET);

            return $this;
        }

        /**
         * Determines whether at least one key-value pair satisfies the given predicate.
         * @param callable(V, K): bool $predicate A predicate receiving the value and key.
         * @return bool Whether any pair passes.
         */
        public function some (callable $predicate) : bool {
            foreach ($this->forward as $key => $value) {
                if ($predicate($value, $key)) return true;
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
         * Gets all key-value pairs as a plain associative array.
         * @return array<K, V> The pairs.
         */
        public function toArray () : array {
            return $this->forward;
        }

        /**
         * Creates a new bidirectional map with the given type constraint for values.
         * @param string $type The type of values the map will enforce.
         * @return static The map.
         */
        public static function withType (string $type) : static {
            return new static([], $type);
        }
    }
?>
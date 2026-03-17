<?php
    /**
     * Project Name:    Wingman Strux - HashMap Class
     * Created by:      Angel Politis
     * Creation Date:   Dec 24 2023
     * Last Modified:   Mar 17 2026
     *
     * Copyright (c) 2023-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Strux namespace.
    namespace Wingman\Strux;

    # Import the following classes to the current scope.
    use ArrayAccess;
    use Countable;
    use InvalidArgumentException;
    use Iterator;
    use JsonSerializable;
    use Wingman\Strux\Bridge\Corvus\Emitter;
    use Wingman\Strux\Bridge\Verix\Validator;
    use Wingman\Strux\Enums\Signal;
    use Wingman\Strux\Interfaces\MapInterface;

    /**
     * Represents a hash map.
     * @package Wingman\Strux
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class HashMap implements ArrayAccess, Countable, Iterator, JsonSerializable, MapInterface {
        /**
         * The raw keys of a hash map.
         * @var array
         */
        private array $keys = [];

        /**
         * The array of a hash map where the data are stored.
         * @var array
         */
        private array $map = [];

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
         * Creates a new hash map.
         * @param array $data The data to import.
         * @param string|null $type The type of values the map will enforce.
         */
        public function __construct (array $data = [], ?string $type = null) {
            if (isset($type)) $this->type = $type;

            foreach ($data as $key => $value) {
                $this->set($key, $value);
            }
        }

        /**
         * Serialises a value to a string key used internally for storage and lookup.
         *
         * Note: object keys are keyed by `spl_object_hash`, which is unique per instance.
         * This means only the exact same object reference will resolve to the same key —
         * two equal objects will be treated as distinct keys.
         *
         * @param mixed $value The value to serialise.
         * @return string The serialised form of the value.
         */
        private function serialise (mixed $value) : string {
            if (is_object($value)) return strval(spl_object_hash($value));
            if (is_array($value) || $value instanceof JsonSerializable) return json_encode($value);
            return strval($value);
        }

        /**
         * The type that every value in the map must conform to, or null for no enforcement.
         * @var string|null
         */
        protected ?string $type = null;

        /**
         * Creates a new instance of the hash map carrying the current type constraint.
         * Subclasses may override this to control how derived instances are constructed.
         * @param array $data The data to pre-load.
         * @return static A new instance.
         */
        protected function createInstance (array $data = []) : static {
            return new static($data, $this->type);
        }

        /**
         * Enforces the map's value type constraint against each given value.
         * The class/interface vs. primitive distinction and the normalised type name are
         * lazily cached after the first invocation.
         * @param mixed ...$values The values to validate.
         * @throws InvalidArgumentException If any value does not conform to the type.
         */
        protected function enforceType (mixed ...$values) : void {
            if (!isset($this->type)) return;

            if (Validator::isSchemaExpression($this->type)) {
                foreach ($values as $i => $value) {
                    Validator::validate($value, $this->type, $i);
                }

                return;
            }

            $this->typeIsClass ??= class_exists($this->type) || interface_exists($this->type);

            if ($this->typeIsClass) {
                foreach ($values as $i => $value) {
                    if (!($value instanceof $this->type)) {
                        throw new InvalidArgumentException("The value (index: $i) doesn't match the type '{$this->type}'.");
                    }
                }

                return;
            }

            $this->normalisedType ??= strtolower($this->type);

            foreach ($values as $i => $value) {
                $valid = match ($this->normalisedType) {
                    "int", "integer" => is_int($value),
                    "float", "double" => is_float($value),
                    "string" => is_string($value),
                    "bool", "boolean" => is_bool($value),
                    "array" => is_array($value),
                    "callable" => is_callable($value),
                    "object" => is_object($value),
                    default => throw new InvalidArgumentException("Unknown type '{$this->type}' for type enforcement.")
                };

                if (!$valid) {
                    $actual = is_object($value) ? get_class($value) : gettype($value);
                    throw new InvalidArgumentException("The value (index: $i) is of type '{$actual}' but expected '{$this->type}'.");
                }
            }
        }

        /**
         * Clears a hash map.
         * @return static The hash map.
         */
        public function clear () : static {
            $this->keys = [];
            $this->map = [];
            Emitter::create()->with(map: $this)->emit(Signal::MAP_CLEARED);
            return $this;
        }

        /**
         * Gets whether a value is present in a hash map.
         * @param mixed $value The value to search for.
         * @return bool Whether the value is present in the hash map.
         */
        public function contains (mixed $value) : bool {
            return in_array($value, $this->map, strict: true);
        }

        /**
         * Gets the size of a hash map.
         * @return int The size of the hash map.
         */
        public function count () : int {
            return $this->getSize();
        }

        /**
         * Gets the value the internal pointer of a hash map currently indicates.
         * @return mixed The value indicated by the internal pointer.
         */
        public function current () : mixed {
            return current($this->map);
        }

        /**
         * Gets a shallow copy of a hash map with duplicate values removed.
         *
         * When values collide, the first occurrence wins. An optional key selector callback
         * can derive a uniqueness key from each entry.
         *
         * @param callable|null $keySelector An optional callback invoked as ($value, $key, $map) that returns a uniqueness key.
         * @return static A shallow copy of the hash map with duplicate values removed.
         */
        public function deduplicate (?callable $keySelector = null) : static {
            $hashMap = $this->createInstance();
            $seen = [];

            foreach ($this->map as $key => $value) {
                $uniqueKey = $keySelector
                    ? $keySelector($value, $this->keys[$key], $this)
                    : serialize($value);

                if (isset($seen[$uniqueKey])) continue;

                $seen[$uniqueKey] = true;
                $hashMap->set($this->keys[$key], $value);
            }

            return $hashMap;
        }

        /**
         * Iterates over each entry in a hash map, passing the value, original key, and map to the callback.
         * @param callable $callback A callback invoked as ($value, $key, $map).
         * @return static The hash map.
         */
        public function each (callable $callback) : static {
            foreach ($this->map as $key => $value) {
                $callback($value, $this->keys[$key], $this);
            }

            return $this;
        }

        /**
         * Gets whether every entry in a hash map passes a predicate.
         * @param callable $predicate A predicate invoked as ($value, $key, $map).
         * @return bool Whether every entry passes the predicate.
         */
        public function every (callable $predicate) : bool {
            foreach ($this->map as $key => $value) {
                if (!$predicate($value, $this->keys[$key], $this)) return false;
            }

            return true;
        }

        /**
         * Filters a hash map's entries by value.
         * @param callable|null $predicate A predicate used to filter the hash map. By default, all falsey values are discarded.
         * @return static A shallow copy of the hash map with only the accepted entries.
         */
        public function filter (callable | null $predicate = null) : static {
            $hashMap = $this->createInstance();

            $predicate ??= fn ($value) => boolval($value);

            foreach ($this->map as $key => $value) {
                if (!$predicate($value, $this->keys[$key], $this)) continue;
                $hashMap->set($this->keys[$key], $value);
            }

            return $hashMap;
        }

        /**
         * Filters a hash map's entries by key.
         * @param callable|null $predicate A predicate used to filter the hash map. By default, all falsey keys are discarded.
         * @return static A shallow copy of the hash map with only the accepted entries.
         */
        public function filterKey (callable | null $predicate = null) : static {
            $hashMap = $this->createInstance();

            $predicate ??= fn ($key) => boolval($key);

            foreach ($this->keys as $key => $rawKey) {
                $value = $this->map[$key];
                if (!$predicate($rawKey, $value, $this)) continue;
                $hashMap->set($rawKey, $value);
            }

            return $hashMap;
        }

        /**
         * Finds the first value in a hash map whose entry passes a predicate.
         * @param callable $predicate A predicate invoked as ($value, $key, $map).
         * @return mixed The first matching value, or `null` if not found.
         */
        public function find (callable $predicate) : mixed {
            foreach ($this->map as $key => $value) {
                if ($predicate($value, $this->keys[$key], $this)) return $value;
            }

            return null;
        }

        /**
         * Finds the first key in a hash map whose associated value passes a predicate.
         * @param callable $predicate A predicate invoked as ($value, $key, $map).
         * @return mixed The first matching key, or `null` if not found.
         */
        public function findKey (callable $predicate) : mixed {
            foreach ($this->map as $key => $value) {
                if ($predicate($value, $this->keys[$key], $this)) return $this->keys[$key];
            }

            return null;
        }

        /**
         * Gets a shallow copy of a hash map with the keys and values flipped.
         *
         * All values must be scalar (string, int, float, or bool). If any value is an
         * object or array it cannot safely serve as a usable key after flipping.
         *
         * @return static A hash map with the keys and values flipped.
         * @throws InvalidArgumentException If any value is an object or array.
         */
        public function flip () : static {
            foreach ($this->map as $value) {
                if (is_object($value) || is_array($value)) {
                    throw new InvalidArgumentException(
                        "Cannot flip a hash map that contains non-scalar values (objects and arrays cannot serve as keys)."
                    );
                }
            }

            $hashMap = new static();

            foreach ($this->map as $key => $value) {
                $hashMap->set($value, $this->keys[$key]);
            }

            return $hashMap;
        }

        /**
         * Creates a new hash map from an array.
         * @param array $data The data to import.
         * @return static The created hash map.
         */
        public static function from (array $data, ?string $type = null) : static {
            return new static($data, $type);
        }

        /**
         * Gets the value of a hash map mapped to a key.
         * @param mixed $key The key.
         * @return mixed The value, or `null` if not defined.
         */
        public function get (mixed $key) : mixed {
            $key = $this->serialise($key);
            return $this->map[$key] ?? null;
        }

        /**
         * Gets the keys of a hash map.
         * @param bool $serialised Whether to get the serialised keys instead of the original ones.
         * @return array The keys.
         */
        public function getKeys (bool $serialised = false) : array {
            return $serialised ? array_keys($this->map) : array_values($this->keys);
        }

        /**
         * Gets the maximum value in a hash map.
         * @param callable|null $keySelector An optional callback invoked as ($value, $key, $map) to derive a comparable score. When omitted the values are compared directly.
         * @return mixed The maximum value, or `null` if the hash map is empty.
         */
        public function getMax (?callable $keySelector = null) : mixed {
            if (empty($this->map)) return null;

            $maxEntry = null;
            $maxScore = null;

            foreach ($this->map as $key => $value) {
                $score = $keySelector ? $keySelector($value, $this->keys[$key], $this) : $value;

                if ($maxScore === null || $score > $maxScore) {
                    $maxEntry = $value;
                    $maxScore = $score;
                }
            }

            return $maxEntry;
        }

        /**
         * Gets the minimum value in a hash map.
         * @param callable|null $keySelector An optional callback invoked as ($value, $key, $map) to derive a comparable score. When omitted the values are compared directly.
         * @return mixed The minimum value, or `null` if the hash map is empty.
         */
        public function getMin (?callable $keySelector = null) : mixed {
            if (empty($this->map)) return null;

            $minEntry = null;
            $minScore = null;

            foreach ($this->map as $key => $value) {
                $score = $keySelector ? $keySelector($value, $this->keys[$key], $this) : $value;

                if ($minScore === null || $score < $minScore) {
                    $minEntry = $value;
                    $minScore = $score;
                }
            }

            return $minEntry;
        }

        /**
         * Gets the size of a hash map.
         * @return int The size of the hash map.
         */
        public function getSize () : int {
            return count($this->map);
        }

        /**
         * Gets the values of a hash map.
         * @return array The values.
         */
        public function getValues () : array {
            return array_values($this->map);
        }

        /**
         * Groups a hash map's entries by a key derived from a callback.
         *
         * Returns an associative array of hash maps, where each group key is the scalar
         * value returned by the callback.
         *
         * @param callable $callback A callback invoked as ($value, $key, $map) that returns a scalar group key.
         * @return static[] An associative array of hash maps indexed by group key.
         */
        public function groupBy (callable $callback) : array {
            $groups = [];

            foreach ($this->map as $key => $value) {
                $groupKey = $callback($value, $this->keys[$key], $this);
                $groups[$groupKey] ??= $this->createInstance();
                $groups[$groupKey]->set($this->keys[$key], $value);
            }

            return $groups;
        }

        /**
         * Gets whether a key has been defined in a hash map (includes `null`).
         * @param mixed $key The key.
         * @return bool Whether the key has been defined in the hash map.
         */
        public function has (mixed $key) : bool {
            $key = $this->serialise($key);
            return array_key_exists($key, $this->map);
        }

        /**
         * Gets whether a hash map is empty.
         * @return bool Whether the hash map is empty.
         */
        public function isEmpty () : bool {
            return $this->getSize() === 0;
        }

        /**
         * Gets the data of a hash map to be serialised as JSON when encoding it.
         *
         * Scalar original keys (string, int, float, bool) are used directly.
         * Non-scalar keys (objects, arrays) fall back to their internal serialised form.
         *
         * @return array The data of the hash map.
         */
        public function jsonSerialize () : array {
            $result = [];

            foreach ($this->map as $serialisedKey => $value) {
                $originalKey = $this->keys[$serialisedKey];
                $result[is_scalar($originalKey) ? $originalKey : $serialisedKey] = $value;
            }

            return $result;
        }

        /**
         * Gets the key the internal pointer of a hash map currently indicates.
         * @return mixed The key indicated by the internal pointer.
         */
        public function key () : mixed {
            return $this->keys[key($this->map)];
        }

        /**
         * Maps a hash map's values through a callback.
         * @param callable $callback A callback used to transform each value.
         * @return static A shallow copy of the hash map with the transformed values.
         */
        public function map (callable $callback) : static {
            $hashMap = $this->createInstance();

            foreach ($this->map as $key => $value) {
                $result = $callback($value, $this->keys[$key], $this);
                $hashMap->set($this->keys[$key], $result);
            }

            return $hashMap;
        }

        /**
         * Transforms a hash map's keys through a callback, preserving the associated values.
         *
         * If two existing keys map to the same new key, the latter entry wins.
         *
         * @param callable $callback A callback invoked as ($key, $value, $map) that returns the new key.
         * @return static A shallow copy of the hash map with the transformed keys.
         */
        public function mapKeys (callable $callback) : static {
            $hashMap = $this->createInstance();

            foreach ($this->map as $key => $value) {
                $newKey = $callback($this->keys[$key], $value, $this);
                $hashMap->set($newKey, $value);
            }

            return $hashMap;
        }

        /**
         * Merges one or more hash maps into a shallow copy of this hash map.
         *
         * Entries from later maps override entries from earlier maps when keys collide.
         *
         * @param HashMap ...$others The hash maps to merge in.
         * @return static A new hash map containing all entries.
         */
        public function merge (HashMap ...$others) : static {
            $hashMap = $this->createInstance();

            foreach ($this->map as $key => $value) {
                $hashMap->set($this->keys[$key], $value);
            }

            foreach ($others as $other) {
                foreach ($other as $key => $value) {
                    $hashMap->set($key, $value);
                }
            }

            return $hashMap;
        }

        /**
         * Increments the internal pointer of a hash map.
         */
        public function next () : void {
            next($this->map);
        }

        /**
         * Gets whether no entry in a hash map passes a predicate.
         * @param callable $predicate A predicate invoked as ($value, $key, $map).
         * @return bool Whether no entry passes the predicate.
         */
        public function none (callable $predicate) : bool {
            return !$this->some($predicate);
        }

        /**
         * Gets whether a key has been defined in a hash map (includes `null`).
         * @param mixed $offset The key.
         * @return bool Whether the key has been defined in the hash map.
         */
        public function offsetExists (mixed $offset) : bool {
            return $this->has($offset);
        }

        /**
         * Gets the value of a hash map mapped to a key.
         * @param mixed $offset The key.
         * @return mixed The value, or `null` if not defined.
         */
        public function offsetGet (mixed $offset) : mixed {
            return $this->get($offset);
        }

        /**
         * Sets the value of a key within a hash map.
         * @param mixed $offset The key.
         * @param mixed $value The value.
         */
        public function offsetSet (mixed $offset, mixed $value) : void {
            $this->set($offset, $value);
        }

        /**
         * Removes a key and the associated value from a hash map.
         * @param mixed $offset The key.
         */
        public function offsetUnset (mixed $offset) : void {
            $this->remove($offset);
        }

        /**
         * Partitions a hash map's entries into two hash maps based on a predicate.
         *
         * Returns a two-element array: the first element contains entries that pass the
         * predicate, the second contains entries that do not.
         *
         * @param callable $predicate A predicate invoked as ($value, $key, $map).
         * @return static[] A two-element array of hash maps: [matching, non-matching].
         */
        public function partition (callable $predicate) : array {
            $pass = $this->createInstance();
            $fail = $this->createInstance();

            foreach ($this->map as $key => $value) {
                if ($predicate($value, $this->keys[$key], $this)) {
                    $pass->set($this->keys[$key], $value);
                } else {
                    $fail->set($this->keys[$key], $value);
                }
            }

            return [$pass, $fail];
        }

        /**
         * Reduces a hash map's entries to a single value.
         * @param callable $callback A callback invoked as ($carry, $value, $key, $map).
         * @param mixed $initialValue The initial carry value. When omitted the first entry's value is used as the seed.
         * @return mixed The result of the reduction.
         */
        public function reduce (callable $callback, mixed $initialValue = null) : mixed {
            $keys = array_keys($this->map);

            if (!isset($initialValue)) {
                if (empty($keys)) return null;
                $result = $this->map[array_shift($keys)];
            }
            else $result = $initialValue;

            foreach ($keys as $key) {
                $result = $callback($result, $this->map[$key], $this->keys[$key], $this);
            }

            return $result;
        }

        /**
         * Removes a key and the associated value from a hash map.
         * @param mixed $key The key.
         * @return static The hash map.
         */
        public function remove (mixed $key) : static {
            $serialisedKey = $this->serialise($key);
            unset($this->map[$serialisedKey]);
            unset($this->keys[$serialisedKey]);
            Emitter::create()->with(key: $key, map: $this)->emit(Signal::MAP_ENTRY_REMOVED);
            return $this;
        }

        /**
         * Resets the internal pointer of a hash map.
         */
        public function rewind () : void {
            reset($this->map);
        }

        /**
         * Sets the value of a key within a hash map.
         * @param mixed $key The key.
         * @param mixed $value The value.
         * @return static The hash map.
         */
        public function set (mixed $key, mixed $value) : static {
            $this->enforceType($value);
            $serialisedKey = $this->serialise($key);
            $this->map[$serialisedKey] = $value;
            $this->keys[$serialisedKey] = $key;
            Emitter::create()->with(key: $key, value: $value, map: $this)->emit(Signal::MAP_ENTRY_SET);
            return $this;
        }

        /**
         * Gets whether at least one entry in a hash map passes a predicate.
         * @param callable $predicate A predicate invoked as ($value, $key, $map).
         * @return bool Whether at least one entry passes the predicate.
         */
        public function some (callable $predicate) : bool {
            foreach ($this->map as $key => $value) {
                if ($predicate($value, $this->keys[$key], $this)) return true;
            }

            return false;
        }

        /**
         * Gets the sum of a hash map's values, optionally using a callback to derive numeric values.
         * @param callable|null $callback An optional callback invoked as ($value, $key, $map) that returns a numeric value.
         * @return int|float The sum of all derived numeric values.
         */
        public function sum (?callable $callback = null) : int|float {
            $total = 0;

            foreach ($this->map as $key => $value) {
                $total += $callback ? $callback($value, $this->keys[$key], $this) : $value;
            }

            return $total;
        }

        /**
         * Passes a hash map to a callback and returns the hash map unchanged.
         *
         * Useful for debugging or performing side effects in a fluent chain.
         *
         * @param callable $callback A callback invoked as ($map).
         * @return static The hash map.
         */
        public function tap (callable $callback) : static {
            $callback($this);

            return $this;
        }

        /**
         * Gets whether the internal pointer of a hash map is within bounds.
         * @return bool Whether the internal pointer is within bounds.
         */
        public function valid () : bool {
            return key($this->map) !== null;
        }
    }
?>
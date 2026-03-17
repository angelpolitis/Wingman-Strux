<?php
    /**
     * Project Name:    Wingman Strux - Weak Reference Map
     * Created by:      Angel Politis
     * Creation Date:   Mar 15 2026
     * Last Modified:   Mar 17 2026
     *
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Strux namespace.
    namespace Wingman\Strux;

    # Import the following classes and interfaces to the current scope.
    use Countable;
    use InvalidArgumentException;
    use IteratorAggregate;
    use Traversable;
    use WeakMap;
    use Wingman\Strux\Bridge\Corvus\Emitter;
    use Wingman\Strux\Enums\Signal;
    use Wingman\Strux\Interfaces\MapInterface;

    /**
     * Represents a memory-safe object-to-value mapping backed by PHP's native WeakMap.
     *
     * A WeakReferenceMap holds weak references to its keys. Unlike a standard map or array,
     * a weak reference does NOT prevent the PHP garbage collector from reclaiming an object.
     * When a key object is destroyed, its entry is automatically removed from the map — no
     * manual cleanup is required. This prevents the class of memory leak where a cache or
     * lookup table keeps objects alive long after the rest of the application has released them.
     *
     * Enterprise use-cases:
     * - ORM identity maps: track which entities have been loaded without keeping them alive
     * - Database profilers: associate query counts with Connection objects
     * - Property attachments: attach transient "isDirty" or "metadata" state to an object
     *   without modifying its class definition
     * - Long-running processes (RoadRunner, FrankenPHP): caches that clean themselves up
     *
     * Note: keys MUST be objects. To map arbitrary scalar keys, use HashMap instead.
     *
     * Requires PHP 8.0+.
     *
     * @package Wingman\Strux
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     * @template K of object
     * @template V
     * @implements MapInterface<K, V>
     */
    class WeakReferenceMap implements Countable, IteratorAggregate, MapInterface {
        /**
         * The internal WeakMap used as the backing store.
         * @var WeakMap<K, V>
         */
        private WeakMap $map;

        /**
         * Creates a new weak reference map.
         */
        public function __construct () {
            $this->map = new WeakMap();
        }

        /**
         * Removes all entries from the map.
         * @return static The map.
         */
        public function clear () : static {
            $this->map = new WeakMap();
            Emitter::create()->with(map: $this)->emit(Signal::MAP_CLEARED);
            return $this;
        }

        /**
         * Determines whether the map contains the given value (strict comparison).
         * @param V $value The value to search for.
         * @return bool Whether the value is present.
         */
        public function contains (mixed $value) : bool {
            foreach ($this->map as $_ => $entry) {
                if ($entry === $value) {
                    return true;
                }
            }

            return false;
        }

        /**
         * Gets the number of entries currently in the map.
         * Entries for garbage-collected objects are excluded automatically.
         * @return int The entry count.
         */
        public function count () : int {
            return $this->getSize();
        }

        /**
         * Invokes the given callback for each entry and returns the map unchanged.
         * The callback receives the value, the key, and the map as its arguments.
         * @param callable(V, K, static): void $callback The callback to invoke.
         * @return static The map.
         */
        public function each (callable $callback) : static {
            foreach ($this->map as $key => $value) {
                $callback($value, $key, $this);
            }

            return $this;
        }

        /**
         * Creates a new weak reference map pre-loaded with the given object-keyed pairs.
         * @param array<K, V> $entries An array of object => value pairs.
         * @throws InvalidArgumentException If any key in the array is not an object.
         * @return static The created map.
         */
        public static function from (array $entries) : static {
            foreach ($entries as $key => $_) {
                if (!is_object($key)) {
                    throw new InvalidArgumentException(
                        "WeakReferenceMap keys must be objects; " . gettype($key) . " given."
                    );
                }
            }

            $map = new static();

            foreach ($entries as $key => $value) {
                $map->set($key, $value);
            }

            return $map;
        }

        /**
         * Gets the value associated with the given object key, or null if absent.
         * @param K $key The object key.
         * @return V|null The value, or null.
         */
        public function get (mixed $key) : mixed {
            return $this->map[$key] ?? null;
        }

        /**
         * Gets an iterator over all live (non-garbage-collected) entries.
         * @return Traversable<K, V> The iterator.
         */
        public function getIterator () : Traversable {
            return $this->map;
        }

        /**
         * Gets all live object keys in the map.
         * @return K[] The keys.
         */
        public function getKeys () : array {
            $keys = [];

            foreach ($this->map as $key => $_) {
                $keys[] = $key;
            }

            return $keys;
        }

        /**
         * Gets the number of entries currently in the map.
         * @return int The size.
         */
        public function getSize () : int {
            return count($this->map);
        }

        /**
         * Gets all live values in the map.
         * @return V[] The values.
         */
        public function getValues () : array {
            $values = [];

            foreach ($this->map as $_ => $value) {
                $values[] = $value;
            }

            return $values;
        }

        /**
         * Determines whether the given object key exists in the map (including null-valued entries).
         * @param K $key The object key.
         * @return bool Whether the key exists.
         */
        public function has (mixed $key) : bool {
            return $this->map->offsetExists($key);
        }

        /**
         * Determines whether the map contains no entries.
         * @return bool Whether the map is empty.
         */
        public function isEmpty () : bool {
            return $this->getSize() === 0;
        }

        /**
         * Removes the entry for the given object key from the map.
         * @param K $key The object key to remove.
         * @return static The map.
         */
        public function remove (mixed $key) : static {
            unset($this->map[$key]);
            Emitter::create()->with(key: $key, map: $this)->emit(Signal::MAP_ENTRY_REMOVED);
            return $this;
        }

        /**
         * Associates the given value with the given object key, overwriting any prior value.
         * @param K $key The object key.
         * @param V $value The value.
         * @return static The map.
         */
        public function set (mixed $key, mixed $value) : static {
            if (!is_object($key)) {
                throw new InvalidArgumentException("WeakReferenceMap keys must be objects.");
            }

            $this->map[$key] = $value;
            Emitter::create()->with(key: $key, value: $value, map: $this)->emit(Signal::MAP_ENTRY_SET);
            return $this;
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
    }
?>
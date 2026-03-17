<?php
    /**
     * Project Name:    Wingman Strux - Lazy Collection
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
    use IteratorAggregate;
    use Traversable;

    /**
     * Represents a lazily-evaluated sequence of items backed by a PHP generator or iterable.
     *
     * A LazyCollection holds a callable or iterable as its source rather than loading all items
     * into memory at once. Items are yielded one at a time on demand, which makes it possible to
     * work with arbitrarily large datasets (millions of database rows, streaming CSV files, etc.)
     * without exhausting the PHP memory limit.
     *
     * Transformation operations (filter, map, take, skip) return new LazyCollection instances
     * that wrap the previous source — no work is done until iteration actually begins. This is
     * sometimes called "deferred execution" or a "pipeline" pattern.
     *
     * Usage example:
     * ```php
     * LazyCollection::make(fn () => (yield from range(1, 1_000_000)))
     *     ->filter(fn ($n) => $n % 2 === 0)
     *     ->map(fn ($n) => $n ** 2)
     *     ->take(10)
     *     ->each(fn ($n) => print($n . "\n"));
     * ```
     *
     * @package Wingman\Strux
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     * @template T
     * @implements IteratorAggregate<int|string, T>
     */
    class LazyCollection implements IteratorAggregate {
        /**
         * The source of items, either a callable that returns a Generator or any iterable.
         * @var callable|iterable
         */
        private $source;

        /**
         * Creates a new lazy collection from a callable or iterable source.
         * Using a callable is preferred because it allows the generator to be re-wound
         * (each call to getIterator() invokes the callable afresh) whereas a bare
         * iterable can only be traversed once if it is itself a Generator.
         * @param callable|iterable $source The source of items.
         */
        public function __construct (callable|iterable $source) {
            $this->source = $source;
        }

        /**
         * Creates a new lazy collection that groups items into arrays of the given size.
         * The last chunk may be smaller than the given size if the sequence is not evenly divisible.
         * @param int $size The number of items per chunk. Must be at least 1.
         * @return static A new lazy collection whose items are arrays (chunks).
         */
        public function chunk (int $size) : static {
            return new static(function () use ($size) {
                $chunk = [];

                foreach ($this as $item) {
                    $chunk[] = $item;

                    if (count($chunk) === $size) {
                        yield $chunk;
                        $chunk = [];
                    }
                }

                if ($chunk !== []) yield $chunk;
            });
        }

        /**
         * Materialises the lazy sequence into an in-memory Collection.
         * All items are evaluated and stored. Use with care on large sequences.
         * @return Collection<T> A new collection containing all items.
         */
        public function collect () : Collection {
            $items = [];

            foreach ($this as $item) {
                $items[] = $item;
            }

            return new Collection($items);
        }

        /**
         * Gets the number of items produced by the source.
         * This forces full evaluation of the pipeline and should be used sparingly
         * on large or infinite sequences.
         * @return int The item count.
         */
        public function count () : int {
            $count = 0;

            foreach ($this as $_) {
                $count++;
            }

            return $count;
        }

        /**
         * Invokes the given callback for every item in the sequence and returns the collection unchanged.
         * The callback receives the item and its key as its arguments.
         * @param callable(T, int|string): void $callback The callback to invoke.
         * @return static The lazy collection.
         */
        public function each (callable $callback) : static {
            foreach ($this as $key => $item) {
                $callback($item, $key);
            }

            return $this;
        }

        /**
         * Determines whether all items in the sequence satisfy the given predicate.
         * This forces full evaluation and should be used sparingly on large or infinite sequences.
         * @param callable(T, int|string): bool $predicate A predicate receiving each item and its key.
         * @return bool Whether all items pass.
         */
        public function every (callable $predicate) : bool {
            foreach ($this as $key => $item) {
                if (!$predicate($item, $key)) return false;
            }

            return true;
        }

        /**
         * Creates a new lazy collection that yields only items for which the predicate returns true.
         * @param callable $predicate A predicate receiving (item, key) and returning bool.
         * @return static A new lazy collection.
         */
        public function filter (callable $predicate) : static {
            return new static(function () use ($predicate) {
                foreach ($this as $key => $item) {
                    if ($predicate($item, $key)) yield $key => $item;
                }
            });
        }

        /**         * Finds the first item (in source order) that satisfies the given predicate.
         * Partial evaluation stops as soon as the first match is found.
         * @param callable(T, int|string): bool $predicate A predicate receiving each item and its key.
         * @return T|null The first matching item, or null if none is found.
         */
        public function find (callable $predicate) : mixed {
            foreach ($this as $key => $item) {
                if ($predicate($item, $key)) return $item;
            }

            return null;
        }

        /**         * Gets the first item produced by the sequence, or null if it is empty.
         * @return T|null The first item.
         */
        public function getFirst () : mixed {
            foreach ($this as $item) {
                return $item;
            }

            return null;
        }

        /**
         * Gets an iterator for the lazy sequence.
         * If the source is callable, it is invoked to produce a fresh generator so the
         * sequence can be iterated multiple times. If the source is a plain iterable, it
         * is delegated to directly.
         * @return Traversable<int|string, T> The iterator.
         */
        public function getIterator () : Traversable {
            $source = $this->source;

            if (is_callable($source)) yield from $source();
            else yield from $source;
        }

        /**
         * Gets the number of items produced by the sequence.
         * This forces full evaluation of the pipeline and should be used sparingly
         * on large or infinite sequences.
         * @return int The size.
         */
        public function getSize () : int {
            return $this->count();
        }

        /**
         * Determines whether the sequence produces no items.
         * Peeks the first item only — does not consume the full sequence.
         * @return bool Whether the sequence is empty.
         */
        public function isEmpty () : bool {
            foreach ($this as $_) return false;
            return true;
        }

        /**
         * Creates a new lazy collection from a callable or iterable source.
         * @param callable|iterable $source The source of items.
         * @return static The created lazy collection.
         */
        public static function make (callable|iterable $source) : static {
            return new static($source);
        }

        /**
         * Creates a new lazy collection that applies the given callback to every item.
         * @param callable(T, int|string): mixed $callback A callback receiving (item, key) and returning the transformed value.
         * @return static A new lazy collection.
         */
        public function map (callable $callback) : static {
            return new static(function () use ($callback) {
                foreach ($this as $key => $item) {
                    yield $key => $callback($item, $key);
                }
            });
        }

        /**
         * Determines whether no items in the sequence satisfy the given predicate.
         * Delegates to the inverse of some().
         * @param callable(T, int|string): bool $predicate A predicate receiving each item and its key.
         * @return bool Whether no items pass.
         */
        public function none (callable $predicate) : bool {
            return !$this->some($predicate);
        }

        /**
         * Creates a new lazy collection producing an infinite integer range.
         * @param int $start The starting value (inclusive).
         * @param int $step The step between each value. Must not be zero.
         * @return static The range sequence.
         */
        public static function range (int $start = 0, int $step = 1) : static {
            return new static(function () use ($start, $step) {
                $i = $start;

                while (true) {
                    yield $i;
                    $i += $step;
                }
            });
        }

        /**
         * Reduces the sequence to a single value using the given callback.
         * @param callable(mixed, T, int|string): mixed $callback A callback receiving (carry, item, key) and returning the updated carry.
         * @param mixed $initial The initial carry value.
         * @return mixed The final reduced value.
         */
        public function reduce (callable $callback, mixed $initial = null) : mixed {
            $carry = $initial;

            foreach ($this as $key => $item) {
                $carry = $callback($carry, $item, $key);
            }

            return $carry;
        }

        /**
         * Creates a new lazy collection that produces a constant value the given number of times.
         * @param mixed $value The value to repeat.
         * @param int $times The number of repetitions. Pass -1 for an infinite sequence.
         * @return static The repeat sequence.
         */
        public static function repeat (mixed $value, int $times = -1) : static {
            return new static(function () use ($value, $times) {
                $count = 0;

                while ($times === -1 || $count < $times) {
                    yield $value;
                    $count++;
                }
            });
        }

        /**
         * Creates a new lazy collection that skips the first N items of the sequence.
         * @param int $offset The number of items to skip.
         * @return static A new lazy collection.
         */
        public function skip (int $offset) : static {
            return new static(function () use ($offset) {
                $count = 0;

                foreach ($this as $key => $item) {
                    if ($count++ < $offset) continue;

                    yield $key => $item;
                }
            });
        }

        /**
         * Determines whether at least one item in the sequence satisfies the given predicate.
         * This forces partial evaluation up to the first matching item.
         * @param callable(T, int|string): bool $predicate A predicate receiving each item and its key.
         * @return bool Whether any item passes.
         */
        public function some (callable $predicate) : bool {
            foreach ($this as $key => $item) {
                if ($predicate($item, $key)) return true;
            }

            return false;
        }

        /**
         * Creates a new lazy collection that yields at most the first N items of the sequence.
         * @param int $limit The maximum number of items to yield.
         * @return static A new lazy collection.
         */
        public function take (int $limit) : static {
            return new static(function () use ($limit) {
                $count = 0;

                foreach ($this as $key => $item) {
                    if ($count >= $limit) break;

                    yield $key => $item;
                    $count++;
                }
            });
        }

        /**
         * Invokes the given callback with the collection and returns the collection unchanged.
         * Useful for debugging or side-effectful inspection in a method chain.
         * @param callable(static): void $callback The callback to invoke.
         * @return static The lazy collection.
         */
        public function tap (callable $callback) : static {
            $callback($this);
            return $this;
        }

        /**
         * Materialises the lazy sequence into a plain array.
         * All items are evaluated and stored. Use with care on large sequences.
         * @return T[] The items.
         */
        public function toArray () : array {
            $items = [];

            foreach ($this as $key => $item) {
                $items[$key] = $item;
            }

            return $items;
        }
    }
?>
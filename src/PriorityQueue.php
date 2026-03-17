<?php
    /**
     * Project Name:    Wingman Strux - Priority Queue
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
    use ArrayIterator;
    use InvalidArgumentException;
    use SplPriorityQueue;
    use Traversable;
    use Wingman\Strux\Bridge\Corvus\Emitter;
    use Wingman\Strux\Bridge\Verix\Validator;
    use Wingman\Strux\Enums\Signal;
    use Wingman\Strux\Interfaces\SequenceInterface;

    /**
     * Represents a priority-ordered queue where items with a higher priority value are
     * dequeued before items with a lower priority value.
     *
     * Internally this class wraps PHP's SplPriorityQueue (a max-heap). To guarantee
     * stable ordering when two items share the same priority — SplPriorityQueue itself
     * does not specify an order for equal priorities — a monotonically decreasing serial
     * counter is used as a secondary sort key. This ensures that items enqueued earlier
     * are dequeued first when their priorities are identical (FIFO among equals).
     *
     * enqueue runs in O(log n); dequeue/peek run in O(log n) and O(1) respectively.
     *
     * A common use-case is a middleware/event-listener dispatcher where "AuthMiddleware"
     * (priority 100) must execute before "LogMiddleware" (priority 10).
     *
     * @package Wingman\Strux
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     * @template T
     * @implements SequenceInterface<T>
     */
    class PriorityQueue implements SequenceInterface {
        /**
         * Creates a new priority queue.
         * @param string|null $type The type of items the queue will enforce.
         */
        public function __construct (?string $type = null) {
            $this->heap = new SplPriorityQueue();
            $this->heap->setExtractFlags(SplPriorityQueue::EXTR_BOTH);

            if (isset($type)) $this->type = $type;
        }

        /**
         * The internal heap used as the backing store.
         * Configured with EXTR_BOTH so that extracted values carry both the data and priority.
         * @var SplPriorityQueue
         */
        private SplPriorityQueue $heap;

        /**
         * The cached normalised (lowercased) form of the enforced type name.
         * Only populated for primitive type enforcement.
         * @var string|null
         */
        private ?string $normalisedType = null;

        /**
         * A monotonically decreasing serial counter used to achieve stable FIFO ordering
         * among items that share the same priority value.
         * @var int
         */
        private int $serial = 0;

        /**
         * Whether the enforced type resolves to a class or interface.
         * Lazily computed on the first invocation of enforceType.
         * @var bool|null
         */
        private ?bool $typeIsClass = null;

        /**
         * The type that every item in the queue must conform to, or null for no enforcement.
         * @var class-string<T>|string|null
         */
        protected ?string $type = null;

        /**
         * Enforces the queue's type constraint against each given item.
         * The class/interface vs. primitive distinction and the normalised type name are
         * lazily cached after the first invocation.
         * @param mixed ...$items The items to validate.
         * @throws InvalidArgumentException If any item does not conform to the type.
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
                    default => throw new InvalidArgumentException("Unknown type '{$this->type}' for type enforcement.")
                };

                if (!$valid) {
                    $actual = is_object($item) ? get_class($item) : gettype($item);
                    throw new InvalidArgumentException("The item (index: $i) is of type '{$actual}' but expected '{$this->type}'.");
                }
            }
        }

        /**
         * Gets the number of items currently in the queue.
         * @return int The item count.
         */
        public function count () : int {
            return $this->getSize();
        }

        /**
         * Removes and returns the item with the highest priority.
         * When two items share the same priority, the one enqueued first is returned
         * (stable FIFO ordering among equals).
         * @return T|null The highest-priority item, or null if the queue is empty.
         */
        public function dequeue () : mixed {
            if ($this->heap->isEmpty()) return null;

            $entry = $this->heap->extract();
            $item = $entry["data"];

            Emitter::create()
                ->with(item: $item, queue: $this)
                ->emit(Signal::QUEUE_ITEM_DEQUEUED);

            return $item;
        }

        /**
         * Invokes the given callback for each item in priority order (highest first) and returns the queue unchanged.
         * The callback receives the item and the queue as its arguments.
         * @param callable(T, static): void $callback The callback to invoke.
         * @return static The queue.
         */
        public function each (callable $callback) : static {
            foreach ($this as $item) {
                $callback($item, $this);
            }

            return $this;
        }

        /**
         * Adds an item to the queue with the given priority.
         * Higher priority values are dequeued first.
         * @param T $item The item to add.
         * @param int $priority The priority (higher = dequeued sooner). Defaults to 0.
         * @return static The queue.
         * @throws InvalidArgumentException If the item fails type enforcement.
         */
        public function enqueue (mixed $item, int $priority = 0) : static {
            $this->enforceType($item);
            $this->heap->insert($item, [$priority, -$this->serial++]);

            Emitter::create()
                ->with(items: [$item], queue: $this)
                ->emit(Signal::QUEUE_ITEM_ENQUEUED);

            return $this;
        }

        /**
         * Determines whether all items in the queue satisfy the given predicate.
         * Items are evaluated in priority order (highest first).
         * @param callable(T): bool $predicate A predicate receiving each item and returning bool.
         * @return bool Whether all items pass.
         */
        public function every (callable $predicate) : bool {
            foreach ($this as $item) {
                if (!$predicate($item)) return false;
            }

            return true;
        }

        /**
         * Finds the first item (in priority order, highest first) that satisfies the given predicate.
         * @param callable(T): bool $predicate A predicate receiving each item and returning bool.
         * @return T|null The first matching item, or null if none is found.
         */
        public function find (callable $predicate) : mixed {
            foreach ($this as $item) {
                if ($predicate($item)) return $item;
            }

            return null;
        }

        /**
         * Creates a new priority queue pre-loaded with the given item-priority pairs.
         * The array key is the priority (int) and the value is the item.
         * @param array<int, mixed> $items An associative array of priority => item.
         * @param string|null $type The type constraint.
         * @return static The created priority queue.
         */
        public static function from (array $items, ?string $type = null) : static {
            $queue = new static($type);

            foreach ($items as $priority => $item) {
                $queue->enqueue($item, (int) $priority);
            }

            return $queue;
        }

        /**
         * Gets an iterator that yields items in priority order (highest first).
         * Iteration is performed on a clone of the internal heap so the queue itself
         * is not consumed.
         * @return Traversable The iterator.
         */
        public function getIterator () : Traversable {
            $heapCopy = clone $this->heap;
            $items = [];

            while (!$heapCopy->isEmpty()) {
                $entry = $heapCopy->extract();
                $items[] = $entry['data'];
            }

            return new ArrayIterator($items);
        }

        /**
         * Gets the number of items currently in the queue.
         * @return int The size.
         */
        public function getSize () : int {
            return $this->heap->count();
        }

        /**
         * Gets the type constraint enforced by the queue, or null if unrestricted.
         * @return string|null The type.
         */
        public function getType () : ?string {
            return $this->type;
        }

        /**
         * Determines whether the queue contains no items.
         * @return bool Whether the queue is empty.
         */
        public function isEmpty () : bool {
            return $this->heap->isEmpty();
        }

        /**
         * Determines whether no items in the queue satisfy the given predicate.
         * Delegates to the inverse of some().
         * @param callable(T): bool $predicate A predicate receiving each item and returning bool.
         * @return bool Whether no items pass.
         */
        public function none (callable $predicate) : bool {
            return !$this->some($predicate);
        }

        /**
         * Gets the highest-priority item without removing it from the queue.
         * @return T|null The highest-priority item, or null if the queue is empty.
         */
        public function peek () : mixed {
            if ($this->heap->isEmpty()) return null;

            $heapCopy = clone $this->heap;
            $entry = $heapCopy->extract();

            return $entry['data'];
        }

        /**
         * Reduces the queue's items to a single value by applying the given callback in priority order (highest first).
         * If no initial value is provided, the highest-priority item is used as the seed.
         * @param callable(mixed, T, static): mixed $callback A callback receiving the accumulator, the item, and the queue.
         * @param mixed $initial The initial accumulator value.
         * @return mixed The final accumulated value.
         */
        public function reduce (callable $callback, mixed $initial = null) : mixed {
            $items = iterator_to_array($this->getIterator(), false);
            $result = $initial ?? array_shift($items);

            foreach ($items as $item) {
                $result = $callback($result, $item, $this);
            }

            return $result;
        }

        /**
         * Determines whether at least one item in the queue satisfies the given predicate.
         * @param callable(T): bool $predicate A predicate receiving each item and returning bool.
         * @return bool Whether any item passes.
         */
        public function some (callable $predicate) : bool {
            foreach ($this as $item) {
                if ($predicate($item)) return true;
            }

            return false;
        }

        /**
         * Invokes the given callback with the queue and returns the queue unchanged.
         * Useful for debugging or side-effectful inspection in a method chain.
         * @param callable(static): void $callback The callback to invoke.
         * @return static The queue.
         */
        public function tap (callable $callback) : static {
            $callback($this);
            return $this;
        }

        /**
         * Gets all items in the queue as a plain array, ordered from highest to lowest priority.
         * @return T[] The items.
         */
        public function toArray () : array {
            return iterator_to_array($this->getIterator(), false);
        }

        /**
         * Creates a new priority queue with the given type constraint.
         * @param string $type The type of items the queue will enforce.
         * @return static The queue.
         */
        public static function withType (string $type) : static {
            return new static($type);
        }
    }
?>

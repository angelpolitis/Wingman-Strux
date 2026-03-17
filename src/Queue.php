<?php
    /**
     * Project Name:    Wingman Strux - Queue
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
    use InvalidArgumentException;
    use LogicException;
    use SplQueue;
    use Traversable;
    use Wingman\Strux\Bridge\Corvus\Emitter;
    use Wingman\Strux\Bridge\Verix\Validator;
    use Wingman\Strux\Enums\Signal;
    use Wingman\Strux\Interfaces\SequenceInterface;

    /**
     * Represents a FIFO (First-In, First-Out) queue backed by SplQueue.
     *
     * Both enqueue and dequeue run in O(1) because SplQueue is built atop a
     * doubly-linked list and performs no index re-allocation. An optional type
     * constraint can be applied at construction time, and the queue can be permanently
     * frozen to prevent any further mutations.
     *
     * @package Wingman\Strux
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     * @template T
     * @implements SequenceInterface<T>
     */
    class Queue implements SequenceInterface {
        /**
         * Creates a new queue.
         * @param array<T> $items The initial items to enqueue (front to back).
         * @param string|null $type The type of items the queue will enforce.
         * @param int|null $cap The maximum number of items the queue can hold.
         * @param bool $frozen Whether the queue should be immediately frozen.
         */
        public function __construct (array $items = [], ?string $type = null, ?int $cap = null, bool $frozen = false) {
            $this->queue = new SplQueue();
            $this->queue->setIteratorMode(SplQueue::IT_MODE_FIFO | SplQueue::IT_MODE_KEEP);

            if (isset($type)) $this->type = $type;
            if (isset($cap)) $this->cap = $cap;

            $this->enqueue(...$items);
            $this->frozen = $frozen;
        }

        /**
         * The internal SplQueue used as the backing store.
         * @var SplQueue<T>
         */
        private SplQueue $queue;

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
         * The maximum number of items allowed in the queue (null = unlimited).
         * @var int|null
         */
        protected ?int $cap = null;

        /**
         * Whether the queue is frozen (immutable).
         * @var bool
         */
        protected bool $frozen = false;

        /**
         * The type that every item in the queue must conform to, or null for no enforcement.
         * @var class-string<T>|string|null
         */
        protected ?string $type = null;

        /**
         * Enforces the queue's type constraint against each given item.
         * The class/interface vs. primitive distinction and the normalised type name are
         * lazily cached after the first invocation to avoid redundant calls to class_exists
         * and strtolower on hot code paths.
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
         * Guards the queue against attempts to exceed its capacity.
         * @param int $countToAdd The number of items about to be added.
         * @throws LogicException If adding the items would exceed the cap.
         */
        protected function guardCap (int $countToAdd) : void {
            if ($this->cap === null) return;

            if ($this->queue->count() + $countToAdd > $this->cap) {
                throw new LogicException("Queue capacity of {$this->cap} exceeded.");
            }
        }

        /**
         * Guards the queue against mutations when it is frozen.
         * @throws LogicException If the queue is frozen.
         */
        protected function guardFrozen () : void {
            if (!$this->frozen) return;

            throw new LogicException("A frozen queue cannot be modified.");
        }

        /**
         * Gets the number of items currently in the queue.
         * @return int The item count.
         */
        public function count () : int {
            return $this->getSize();
        }

        /**
         * Removes and gets the item at the front of the queue.
         * @return T|null The front item, or null if the queue is empty.
         * @throws LogicException If the queue is frozen.
         */
        public function dequeue () : mixed {
            $this->guardFrozen();
            if ($this->queue->isEmpty()) return null;

            $item = $this->queue->dequeue();

            Emitter::create()
                ->with(item: $item, queue: $this)
                ->emit(Signal::QUEUE_ITEM_DEQUEUED);

            return $item;
        }

        /**
         * Invokes the given callback for each item from front to back and returns the queue unchanged.
         * The callback receives the item and the queue as its arguments.
         * @param callable(T, static): void $callback The callback to invoke.
         * @return static The queue.
         */
        public function each (callable $callback) : static {
            foreach (clone $this->queue as $item) {
                $callback($item, $this);
            }

            return $this;
        }

        /**
         * Adds one or more items to the back of the queue.
         * Items are enqueued in the order they are given.
         * @param T ...$items The items to enqueue.
         * @return static The queue.
         * @throws LogicException If the queue is frozen or the cap would be exceeded.
         * @throws InvalidArgumentException If any item fails type enforcement.
         */
        public function enqueue (mixed ...$items) : static {
            $this->guardFrozen();
            $this->guardCap(count($items));
            $this->enforceType(...$items);

            foreach ($items as $item) {
                $this->queue->enqueue($item);
            }

            Emitter::create()
                ->with(items: array_values($items), queue: $this)
                ->emit(Signal::QUEUE_ITEM_ENQUEUED);

            return $this;
        }

        /**
         * Determines whether all items in the queue satisfy the given predicate.
         * Items are evaluated from front to back.
         * @param callable(T): bool $predicate A predicate receiving each item and returning bool.
         * @return bool Whether all items pass.
         */
        public function every (callable $predicate) : bool {
            foreach (clone $this->queue as $item) {
                if (!$predicate($item)) return false;
            }

            return true;
        }

        /**
         * Finds the first item (from front to back) that satisfies the given predicate.
         * @param callable(T): bool $predicate A predicate receiving each item and returning bool.
         * @return T|null The first matching item, or null if none is found.
         */
        public function find (callable $predicate) : mixed {
            foreach (clone $this->queue as $item) {
                if ($predicate($item)) return $item;
            }

            return null;
        }

        /**
         * Freezes the queue, preventing any further mutations.
         * @return static The queue.
         */
        public function freeze () : static {
            $this->frozen = true;
            return $this;
        }

        /**
         * Creates a new queue from an array of initial items.
         * @param array<T> $items The initial items.
         * @param string|null $type The type constraint.
         * @param int|null $cap The capacity limit.
         * @param bool $frozen Whether to freeze the queue immediately.
         * @return static The created queue.
         */
        public static function from (array $items, ?string $type = null, ?int $cap = null, bool $frozen = false) : static {
            return new static($items, $type, $cap, $frozen);
        }

        /**
         * Gets the cap of the queue, or null if it is unlimited.
         * @return int|null The cap.
         */
        public function getCap () : ?int {
            return $this->cap;
        }

        /**
         * Gets an iterator that traverses the queue from front to back (FIFO order).
         * The returned iterator is a clone of the internal list so that traversal does
         * not consume or advance the queue's own internal pointer.
         * @return Traversable The iterator.
         */
        public function getIterator () : Traversable {
            return clone $this->queue;
        }

        /**
         * Gets the number of items currently in the queue.
         * @return int The size.
         */
        public function getSize () : int {
            return $this->queue->count();
        }

        /**
         * Gets the type constraint enforced by the queue, or null if unrestricted.
         * @return string|null The type.
         */
        public function getType () : ?string {
            return $this->type;
        }

        /**
         * Determines whether the queue has a capacity limit.
         * @return bool Whether the queue has a cap.
         */
        public function hasCap () : bool {
            return isset($this->cap);
        }

        /**
         * Determines whether the queue contains no items.
         * @return bool Whether the queue is empty.
         */
        public function isEmpty () : bool {
            return $this->queue->isEmpty();
        }

        /**
         * Determines whether the queue is frozen.
         * @return bool Whether the queue is frozen.
         */
        public function isFrozen () : bool {
            return $this->frozen;
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
         * Gets the item at the front of the queue without removing it.
         * @return T|null The front item, or null if the queue is empty.
         */
        public function peek () : mixed {
            if ($this->queue->isEmpty()) return null;
            return $this->queue->bottom();
        }

        /**
         * Reduces the queue's items to a single value by applying the given callback from front to back.
         * If no initial value is provided, the front item is used as the seed.
         * @param callable(mixed, T, static): mixed $callback A callback receiving the accumulator, the item, and the queue.
         * @param mixed $initial The initial accumulator value.
         * @return mixed The final accumulated value.
         */
        public function reduce (callable $callback, mixed $initial = null) : mixed {
            $copy = clone $this->queue;
            $result = $initial ?? ($copy->isEmpty() ? null : $copy->dequeue());

            foreach ($copy as $item) {
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
            foreach (clone $this->queue as $item) {
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
         * Gets all items in the queue as a plain array, ordered from front to back.
         * @return T[] The items.
         */
        public function toArray () : array {
            $items = [];

            foreach (clone $this->queue as $item) {
                $items[] = $item;
            }

            return $items;
        }

        /**
         * Unfreezes the queue, re-enabling mutations.
         * @return static The queue.
         */
        public function unfreeze () : static {
            $this->frozen = false;
            return $this;
        }

        /**
         * Creates a new queue with the given capacity limit.
         * @param int $cap The maximum number of items.
         * @return static The queue.
         */
        public static function withCap (int $cap) : static {
            return new static([], null, $cap);
        }

        /**
         * Creates a new queue with the given type constraint.
         * @param string $type The type of items the queue will enforce.
         * @return static The queue.
         */
        public static function withType (string $type) : static {
            return new static([], $type);
        }
    }
?>

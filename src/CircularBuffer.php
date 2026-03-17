<?php
    /**
     * Project Name:    Wingman Strux - Circular Buffer
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
    use Traversable;
    use Wingman\Strux\Bridge\Corvus\Emitter;
    use Wingman\Strux\Bridge\Verix\Validator;
    use Wingman\Strux\Enums\Signal;
    use Wingman\Strux\Interfaces\SequenceInterface;

    /**
     * Represents a fixed-capacity ring buffer (circular buffer).
     *
     * A circular buffer maintains a pre-allocated array of a fixed size. When the buffer
     * is full, writing a new item silently overwrites the oldest entry rather than throwing
     * an exception. This makes it ideal for fixed-memory use-cases such as:
     *
     * - "Last N queries" debug panels
     * - Application log ring buffers
     * - Real-time sliding-window metrics
     *
     * All operations — write, read, and peek — run in O(1) because the implementation
     * maintains head and tail indices into the fixed-size backing array rather than
     * shifting elements. No reallocation ever occurs after construction.
     *
     * @package Wingman\Strux
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     * @template T
     */
    class CircularBuffer implements SequenceInterface {
        /**
         * Creates a new circular buffer with the given fixed capacity.
         * @param int $cap The maximum number of items the buffer can hold. Must be at least 1.
         * @param string|null $type The type of items the buffer will enforce, or null for no enforcement.
         * @throws InvalidArgumentException If the capacity is less than 1.
         */
        public function __construct (int $cap, ?string $type = null) {
            if ($cap < 1) {
                throw new InvalidArgumentException("CircularBuffer capacity must be at least 1, {$cap} given.");
            }

            $this->cap = $cap;
            $this->buffer = array_fill(0, $cap, null);
            if (isset($type)) $this->type = $type;
        }

        /**
         * The fixed-size backing array. Allocated once in the constructor and never resized.
         * @var T[]
         */
        private array $buffer;

        /**
         * The fixed maximum capacity of the buffer.
         * @var int
         */
        private int $cap;

        /**
         * The index of the next read position (the oldest item).
         * @var int
         */
        private int $head = 0;

        /**
         * The cached normalised (lowercased) form of the enforced type name.
         * Only populated for primitive type enforcement.
         * @var string|null
         */
        private ?string $normalisedType = null;

        /**
         * The number of items currently stored in the buffer.
         * @var int
         */
        private int $size = 0;

        /**
         * The index of the next write position.
         * @var int
         */
        private int $tail = 0;

        /**
         * Whether the enforced type resolves to a class or interface.
         * Lazily computed on the first invocation of enforceType.
         * @var bool|null
         */
        private ?bool $typeIsClass = null;

        /**
         * The type that every item in the buffer must conform to, or null for no enforcement.
         * @var class-string<T>|string|null
         */
        protected ?string $type = null;

        /**
         * Enforces the buffer's type constraint against each given item.
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
         * Gets the number of items currently stored in the buffer.
         * @return int The item count.
         */
        public function count () : int {
            return $this->getSize();
        }

        /**
         * Invokes the given callback for each item from oldest to newest and returns the buffer unchanged.
         * The callback receives the item and the buffer as its arguments.
         * @param callable(T, static): void $callback The callback to invoke.
         * @return static The buffer.
         */
        public function each (callable $callback) : static {
            foreach ($this as $item) {
                $callback($item, $this);
            }

            return $this;
        }

        /**
         * Determines whether all items in the buffer satisfy the given predicate.
         * Items are evaluated from oldest to newest.
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
         * Finds the first item (from oldest to newest) that satisfies the given predicate.
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
         * Discards all items, resetting the buffer to an empty state.
         * The capacity is preserved.
         * @return static The buffer.
         */
        public function flush () : static {
            $this->buffer = array_fill(0, $this->cap, null);
            $this->head = 0;
            $this->tail = 0;
            $this->size = 0;

            Emitter::create()
                ->with(buffer: $this)
                ->emit(Signal::BUFFER_FLUSHED);

            return $this;
        }

        /**
         * Creates a new circular buffer pre-loaded with the given items.
         * If no capacity is specified, it defaults to the number of items (minimum 1).
         * @param array<T> $items The initial items to write, oldest first.
         * @param string|null $type The type constraint.
         * @param int|null $cap The fixed capacity. Defaults to max(1, count($items)).
         * @return static The created buffer.
         */
        public static function from (array $items, ?string $type = null, ?int $cap = null) : static {
            $buffer = new static($cap ?? max(1, count($items)), $type);

            foreach ($items as $item) {
                $buffer->write($item);
            }

            return $buffer;
        }

        /**
         * Gets the fixed capacity of the buffer.
         * @return int The capacity.
         */
        public function getCap () : int {
            return $this->cap;
        }

        /**
         * Gets an iterator that yields items from oldest to newest.
         * @return Traversable The iterator.
         */
        public function getIterator () : Traversable {
            $items = [];

            for ($i = 0; $i < $this->size; $i++) {
                $items[] = $this->buffer[($this->head + $i) % $this->cap];
            }

            return new ArrayIterator($items);
        }

        /**
         * Gets the number of items currently stored in the buffer.
         * @return int The size.
         */
        public function getSize () : int {
            return $this->size;
        }

        /**
         * Gets the type constraint enforced by the buffer, or null if unrestricted.
         * @return string|null The type.
         */
        public function getType () : ?string {
            return $this->type;
        }

        /**
         * Determines whether the buffer contains no items.
         * @return bool Whether the buffer is empty.
         */
        public function isEmpty () : bool {
            return $this->size === 0;
        }

        /**
         * Determines whether the buffer has reached its fixed capacity.
         * @return bool Whether the buffer is full.
         */
        public function isFull () : bool {
            return $this->size === $this->cap;
        }

        /**
         * Determines whether no items in the buffer satisfy the given predicate.
         * Delegates to the inverse of some().
         * @param callable(T): bool $predicate A predicate receiving each item and returning bool.
         * @return bool Whether no items pass.
         */
        public function none (callable $predicate) : bool {
            return !$this->some($predicate);
        }

        /**
         * Gets the oldest item in the buffer without removing it.
         * @return T|null The oldest item, or null if the buffer is empty.
         */
        public function peek () : mixed {
            if ($this->size === 0) return null;
            return $this->buffer[$this->head];
        }

        /**
         * Removes and returns the oldest item from the buffer.
         * @return T|null The oldest item, or null if the buffer is empty.
         */
        public function read () : mixed {
            if ($this->size === 0) return null;

            $item = $this->buffer[$this->head];
            $this->buffer[$this->head] = null;
            $this->head = ($this->head + 1) % $this->cap;
            $this->size--;

            Emitter::create()
                ->with(item: $item, buffer: $this)
                ->emit(Signal::BUFFER_ITEM_READ);

            return $item;
        }

        /**
         * Reduces the buffer's items to a single value by applying the given callback from oldest to newest.
         * If no initial value is provided, the oldest item is used as the seed.
         * @param callable(mixed, T, static): mixed $callback A callback receiving the accumulator, the item, and the buffer.
         * @param mixed $initial The initial accumulator value.
         * @return mixed The final accumulated value.
         */
        public function reduce (callable $callback, mixed $initial = null) : mixed {
            $items = $this->toArray();
            $result = $initial ?? array_shift($items);

            foreach ($items as $item) {
                $result = $callback($result, $item, $this);
            }

            return $result;
        }

        /**
         * Determines whether at least one item in the buffer satisfies the given predicate.
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
         * Invokes the given callback with the buffer and returns the buffer unchanged.
         * Useful for debugging or side-effectful inspection in a method chain.
         * @param callable(static): void $callback The callback to invoke.
         * @return static The buffer.
         */
        public function tap (callable $callback) : static {
            $callback($this);
            return $this;
        }

        /**
         * Gets all items currently in the buffer as a plain array, oldest first.
         * @return T[] The items.
         */
        public function toArray () : array {
            $items = [];

            for ($i = 0; $i < $this->size; $i++) {
                $items[] = $this->buffer[($this->head + $i) % $this->cap];
            }

            return $items;
        }

        /**
         * Writes an item into the buffer.
         * If the buffer is full, the oldest item is silently overwritten. This makes
         * write an unconditional O(1) operation regardless of occupancy.
         * @param T $item The item to write.
         * @return static The buffer.
         */
        public function write (mixed $item) : static {
            $this->enforceType($item);

            if ($this->size === $this->cap) {
                $this->buffer[$this->tail] = $item;
                $this->tail = ($this->tail + 1) % $this->cap;
                $this->head = ($this->head + 1) % $this->cap;
            }
            else {
                $this->buffer[$this->tail] = $item;
                $this->tail = ($this->tail + 1) % $this->cap;
                $this->size++;
            }

            Emitter::create()
                ->with(item: $item, buffer: $this)
                ->emit(Signal::BUFFER_ITEM_WRITTEN);

            return $this;
        }
    }
?>
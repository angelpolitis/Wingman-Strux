<?php
    /**
     * Project Name:    Wingman Strux - Stack
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
    use SplDoublyLinkedList;
    use Traversable;
    use Wingman\Strux\Bridge\Corvus\Emitter;
    use Wingman\Strux\Bridge\Verix\Validator;
    use Wingman\Strux\Enums\Signal;
    use Wingman\Strux\Interfaces\SequenceInterface;

    /**
     * Represents a LIFO (Last-In, First-Out) stack backed by SplDoublyLinkedList.
     *
     * Both push and pop run in O(1) because SplDoublyLinkedList uses a doubly-linked
     * internal structure and requires no index re-allocation. An optional type constraint
     * can be applied at construction time, and the stack can be permanently frozen to
     * prevent any further mutations.
     *
     * @package Wingman\Strux
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     * @template T
     * @implements SequenceInterface<T>
     */
    class Stack implements SequenceInterface {
        /**
         * Creates a new stack.
         * @param array<T> $items The initial items to push onto the stack (bottom to top).
         * @param string|null $type The type of items the stack will enforce.
         * @param int|null $cap The maximum number of items the stack can hold.
         * @param bool $frozen Whether the stack should be immediately frozen.
         */
        public function __construct (array $items = [], ?string $type = null, ?int $cap = null, bool $frozen = false) {
            $this->list = new SplDoublyLinkedList();
            $this->list->setIteratorMode(SplDoublyLinkedList::IT_MODE_LIFO | SplDoublyLinkedList::IT_MODE_KEEP);

            if (isset($type)) $this->type = $type;
            if (isset($cap)) $this->cap = $cap;

            $this->push(...$items);
            $this->frozen = $frozen;
        }

        /**
         * The internal doubly-linked list used as the backing store.
         * @var SplDoublyLinkedList<T>
         */
        private SplDoublyLinkedList $list;

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
         * The maximum number of items allowed on the stack (null = unlimited).
         * @var int|null
         */
        protected ?int $cap = null;

        /**
         * Whether the stack is frozen (immutable).
         * @var bool
         */
        protected bool $frozen = false;

        /**
         * The type that every item on the stack must conform to, or null for no enforcement.
         * @var class-string<T>|string|null
         */
        protected ?string $type = null;

        /**
         * Enforces the stack's type constraint against each given item.
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
         * Guards the stack against attempts to exceed its capacity.
         * @param int $countToAdd The number of items about to be added.
         * @throws LogicException If adding the items would exceed the cap.
         */
        protected function guardCap (int $countToAdd) : void {
            if ($this->cap === null) return;

            if ($this->list->count() + $countToAdd > $this->cap) {
                throw new LogicException("Stack capacity of {$this->cap} exceeded.");
            }
        }

        /**
         * Guards the stack against mutations when it is frozen.
         * @throws LogicException If the stack is frozen.
         */
        protected function guardFrozen () : void {
            if (!$this->frozen) return;

            throw new LogicException("A frozen stack cannot be modified.");
        }

        /**
         * Gets the number of items currently on the stack.
         * @return int The item count.
         */
        public function count () : int {
            return $this->getSize();
        }

        /**
         * Invokes the given callback for each item from top to bottom and returns the stack unchanged.
         * The callback receives the item and the stack as its arguments.
         * @param callable(T, static): void $callback The callback to invoke.
         * @return static The stack.
         */
        public function each (callable $callback) : static {
            foreach (clone $this->list as $item) {
                $callback($item, $this);
            }

            return $this;
        }

        /**
         * Determines whether all items on the stack satisfy the given predicate.
         * Items are evaluated from top to bottom.
         * @param callable(T): bool $predicate A predicate receiving each item and returning bool.
         * @return bool Whether all items pass.
         */
        public function every (callable $predicate) : bool {
            foreach (clone $this->list as $item) {
                if (!$predicate($item)) return false;
            }

            return true;
        }

        /**
         * Finds the first item (from top to bottom) that satisfies the given predicate.
         * @param callable(T): bool $predicate A predicate receiving each item and returning bool.
         * @return T|null The first matching item, or null if none is found.
         */
        public function find (callable $predicate) : mixed {
            foreach (clone $this->list as $item) {
                if ($predicate($item)) return $item;
            }

            return null;
        }

        /**
         * Freezes the stack, preventing any further mutations.
         * @return static The stack.
         */
        public function freeze () : static {
            $this->frozen = true;
            return $this;
        }

        /**
         * Creates a new stack from an array of initial items.
         * @param array<T> $items The initial items.
         * @param string|null $type The type constraint.
         * @param int|null $cap The capacity limit.
         * @param bool $frozen Whether to freeze the stack immediately.
         * @return static The created stack.
         */
        public static function from (array $items, ?string $type = null, ?int $cap = null, bool $frozen = false) : static {
            return new static($items, $type, $cap, $frozen);
        }

        /**
         * Gets the cap of the stack, or null if it is unlimited.
         * @return int|null The cap.
         */
        public function getCap () : ?int {
            return $this->cap;
        }

        /**
         * Gets an iterator that traverses the stack from top to bottom (LIFO order).
         * @return Traversable The iterator.
         */
        public function getIterator () : Traversable {
            return clone $this->list;
        }

        /**
         * Gets the number of items currently on the stack.
         * @return int The size.
         */
        public function getSize () : int {
            return $this->list->count();
        }

        /**
         * Gets the type constraint enforced by the stack, or null if unrestricted.
         * @return string|null The type.
         */
        public function getType () : ?string {
            return $this->type;
        }

        /**
         * Determines whether the stack has a capacity limit.
         * @return bool Whether the stack has a cap.
         */
        public function hasCap () : bool {
            return isset($this->cap);
        }

        /**
         * Determines whether the stack contains no items.
         * @return bool Whether the stack is empty.
         */
        public function isEmpty () : bool {
            return $this->list->isEmpty();
        }

        /**
         * Determines whether the stack is frozen.
         * @return bool Whether the stack is frozen.
         */
        public function isFrozen () : bool {
            return $this->frozen;
        }

        /**
         * Determines whether no items on the stack satisfy the given predicate.
         * Delegates to the inverse of some().
         * @param callable(T): bool $predicate A predicate receiving each item and returning bool.
         * @return bool Whether no items pass.
         */
        public function none (callable $predicate) : bool {
            return !$this->some($predicate);
        }

        /**
         * Gets the item at the top of the stack without removing it.
         * @return T|null The top item, or null if the stack is empty.
         */
        public function peek () : mixed {
            if ($this->list->isEmpty()) return null;
            return $this->list->top();
        }

        /**
         * Removes and gets the item at the top of the stack.
         * @return T|null The top item, or null if the stack is empty.
         * @throws LogicException If the stack is frozen.
         */
        public function pop () : mixed {
            $this->guardFrozen();
            if ($this->list->isEmpty()) return null;

            $item = $this->list->pop();

            Emitter::create()
                ->with(item: $item, stack: $this)
                ->emit(Signal::STACK_ITEM_POPPED);

            return $item;
        }

        /**
         * Pushes one or more items onto the top of the stack.
         * Items are pushed in the order they are given, so the last argument ends up on top.
         * @param T ...$items The items to push.
         * @return static The stack.
         * @throws LogicException If the stack is frozen or the cap would be exceeded.
         * @throws InvalidArgumentException If any item fails type enforcement.
         */
        public function push (mixed ...$items) : static {
            $this->guardFrozen();
            $this->guardCap(count($items));
            $this->enforceType(...$items);

            foreach ($items as $item) {
                $this->list->push($item);
            }

            Emitter::create()
                ->with(items: array_values($items), stack: $this)
                ->emit(Signal::STACK_ITEM_PUSHED);

            return $this;
        }

        /**
         * Reduces the stack's items to a single value by applying the given callback from top to bottom.
         * If no initial value is provided, the top item is used as the seed.
         * @param callable(mixed, T, static): mixed $callback A callback receiving the accumulator, the item, and the stack.
         * @param mixed $initial The initial accumulator value.
         * @return mixed The final accumulated value.
         */
        public function reduce (callable $callback, mixed $initial = null) : mixed {
            $copy = clone $this->list;
            $result = $initial ?? ($copy->isEmpty() ? null : $copy->shift());

            foreach ($copy as $item) {
                $result = $callback($result, $item, $this);
            }

            return $result;
        }

        /**
         * Determines whether at least one item on the stack satisfies the given predicate.
         * @param callable(T): bool $predicate A predicate receiving each item and returning bool.
         * @return bool Whether any item passes.
         */
        public function some (callable $predicate) : bool {
            foreach (clone $this->list as $item) {
                if ($predicate($item)) return true;
            }

            return false;
        }

        /**
         * Invokes the given callback with the stack and returns the stack unchanged.
         * Useful for debugging or side-effectful inspection in a method chain.
         * @param callable(static): void $callback The callback to invoke.
         * @return static The stack.
         */
        public function tap (callable $callback) : static {
            $callback($this);
            return $this;
        }

        /**
         * Gets all items on the stack as a plain array, ordered from top to bottom.
         * @return T[] The items.
         */
        public function toArray () : array {
            $items = [];

            foreach (clone $this->list as $item) {
                $items[] = $item;
            }

            return $items;
        }

        /**
         * Unfreezes the stack, re-enabling mutations.
         * @return static The stack.
         */
        public function unfreeze () : static {
            $this->frozen = false;
            return $this;
        }

        /**
         * Creates a new stack with the given capacity limit.
         * @param int $cap The maximum number of items.
         * @return static The stack.
         */
        public static function withCap (int $cap) : static {
            return new static([], null, $cap);
        }

        /**
         * Creates a new stack with the given type constraint.
         * @param string $type The type of items the stack will enforce.
         * @return static The stack.
         */
        public static function withType (string $type) : static {
            return new static([], $type);
        }
    }
?>

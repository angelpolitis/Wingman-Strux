<?php
    /**
     * Project Name:    Wingman Strux - Set
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
    use LogicException;
    use Traversable;
    use Wingman\Strux\Bridge\Corvus\Emitter;
    use Wingman\Strux\Bridge\Verix\Validator;
    use Wingman\Strux\Enums\Signal;
    use Wingman\Strux\Interfaces\SetInterface;

    /**
     * Represents an unordered collection that contains no duplicate elements.
     *
     * Internally the set maintains a hash-map lookup table keyed by a deterministic
     * hash of each item. This guarantees that membership checks (contains), insertions
     * (add), and removals (remove) all run in amortised O(1) — a significant improvement
     * over naively scanning an array in O(n).
     *
     * Objects are identified by their SPL object hash; scalar values are identified by
     * their serialised form. Consequently, two different object instances that happen to
     * hold the same data are treated as distinct items.
     *
     * An optional type constraint and a frozen guard are available, consistent with the
     * rest of the Strux data-structure family.
     *
     * @package Wingman\Strux
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     * @template T
     * @implements SetInterface<T>
     */
    class Set implements SetInterface {
        /**
         * Creates a new set.
         * @param array<T> $items The initial items (duplicates are silently discarded).
         * @param string|null $type The type of items the set will enforce.
         * @param bool $frozen Whether the set should be immediately frozen.
         */
        public function __construct (array $items = [], ?string $type = null, bool $frozen = false) {
            if (isset($type)) $this->type = $type;

            $this->add(...$items);
            $this->frozen = $frozen;
        }

        /**
         * A lookup table mapping each item's hash to true.
         * Provides O(1) membership tests without iterating over $items.
         * @var array<string, true>
         */
        private array $lookup = [];

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
         * Gets a deterministic hash string for the given item.
         * Objects are hashed by their SPL object hash; scalars by their serialised form.
         * @param mixed $item The item to hash.
         * @return string The hash.
         */
        private function hash (mixed $item) : string {
            return is_object($item) ? "o:" . spl_object_hash($item) : "s:" . serialize($item);
        }

        /**
         * Whether the set is frozen (immutable).
         * @var bool
         */
        protected bool $frozen = false;

        /**
         * The ordered list of unique items held by the set.
         * @var T[]
         */
        protected array $items = [];

        /**
         * The type that every item in the set must conform to, or null for no enforcement.
         * @var class-string<T>|string|null
         */
        protected ?string $type = null;

        /**
         * Creates a new instance of the set pre-loaded with the given items.
         * Carries the current type constraint into the new instance.
         * Subclasses may override this to control how derived instances are constructed.
         * @param array $items The items to pre-load.
         * @return static A new instance.
         */
        protected function createInstance (array $items = []) : static {
            return new static($items, $this->type);
        }

        /**
         * Enforces the set's type constraint against each given item.
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
         * Guards the set against mutations when it is frozen.
         * @throws LogicException If the set is frozen.
         */
        protected function guardFrozen () : void {
            if (!$this->frozen) return;

            throw new LogicException("A frozen set cannot be modified.");
        }

        /**
         * Adds one or more items to the set. Duplicate items are silently discarded.
         * @param T ...$items The items to add.
         * @return static The set.
         * @throws LogicException If the set is frozen.
         * @throws InvalidArgumentException If any item fails type enforcement.
         */
        public function add (mixed ...$items) : static {
            $this->guardFrozen();
            $this->enforceType(...$items);

            $added = [];

            foreach ($items as $item) {
                $hash = $this->hash($item);

                if (isset($this->lookup[$hash])) continue;

                $this->lookup[$hash] = true;
                $this->items[] = $item;
                $added[] = $item;
            }

            if ($added !== []) {
                Emitter::create()
                    ->with(items: $added, set: $this)
                    ->emit(Signal::SET_ITEM_ADDED);
            }

            return $this;
        }

        /**
         * Determines whether the given item is a member of the set in O(1).
         * @param T $item The item to search for.
         * @return bool Whether the item exists.
         */
        public function contains (mixed $item) : bool {
            return isset($this->lookup[$this->hash($item)]);
        }

        /**
         * Gets the number of items in the set.
         * @return int The item count.
         */
        public function count () : int {
            return $this->getSize();
        }

        /**
         * Creates a new set containing items present in this set but absent from the other.
         * @param SetInterface $other The other set.
         * @return static The difference set.
         */
        public function diff (SetInterface $other) : static {
            $result = $this->createInstance();

            foreach ($this->items as $item) {
                if (!$other->contains($item)) $result->add($item);
            }

            return $result;
        }

        /**
         * Invokes the given callback for each item and returns the set unchanged.
         * The callback receives the item and the set as its arguments.
         * @param callable(T, static): void $callback The callback to invoke.
         * @return static The set.
         */
        public function each (callable $callback) : static {
            foreach ($this->items as $item) {
                $callback($item, $this);
            }

            return $this;
        }

        /**
         * Determines whether all items in the set satisfy the given predicate.
         * @param callable(T): bool $predicate A predicate receiving each item and returning bool.
         * @return bool Whether all items pass.
         */
        public function every (callable $predicate) : bool {
            foreach ($this->items as $item) {
                if (!$predicate($item)) return false;
            }

            return true;
        }

        /**
         * Filters the set through a predicate, creating a new set of matching items.
         * @param callable(T): bool $predicate A predicate receiving each item and returning bool.
         * @return static A new set containing items that pass the predicate.
         */
        public function filter (callable $predicate) : static {
            $result = $this->createInstance();

            foreach ($this->items as $item) {
                if ($predicate($item)) $result->add($item);
            }

            return $result;
        }

        /**
         * Freezes the set, preventing any further mutations.
         * @return static The set.
         */
        public function freeze () : static {
            $this->frozen = true;

            Emitter::create()
                ->with(collection: $this)
                ->emit(Signal::COLLECTION_FROZEN);

            return $this;
        }

        /**
         * Creates a new set from an array of initial items.
         * @param array<T> $items The initial items.
         * @param string|null $type The type constraint.
         * @param bool $frozen Whether to freeze the set immediately.
         * @return static The created set.
         */
        public static function from (array $items, ?string $type = null, bool $frozen = false) : static {
            return new static($items, $type, $frozen);
        }

        /**
         * Gets an iterator that traverses the set in insertion order.
         * @return Traversable The iterator.
         */
        public function getIterator () : Traversable {
            return new ArrayIterator($this->items);
        }

        /**
         * Gets the number of items in the set.
         * @return int The size.
         */
        public function getSize () : int {
            return count($this->items);
        }

        /**
         * Gets the type constraint enforced by the set, or null if unrestricted.
         * @return string|null The type.
         */
        public function getType () : ?string {
            return $this->type;
        }

        /**
         * Creates a new set containing only items present in both this set and the other.
         * @param SetInterface $other The other set.
         * @return static The intersection set.
         */
        public function intersect (SetInterface $other) : static {
            $result = $this->createInstance();

            foreach ($this->items as $item) {
                if ($other->contains($item)) $result->add($item);
            }

            return $result;
        }

        /**
         * Determines whether the set contains no items.
         * @return bool Whether the set is empty.
         */
        public function isEmpty () : bool {
            return $this->getSize() === 0;
        }

        /**
         * Determines whether the set is frozen.
         * @return bool Whether the set is frozen.
         */
        public function isFrozen () : bool {
            return $this->frozen;
        }

        /**
         * Determines whether no items in the set satisfy the given predicate.
         * Delegates to the inverse of some().
         * @param callable(T): bool $predicate A predicate receiving each item and returning bool.
         * @return bool Whether no items pass.
         */
        public function none (callable $predicate) : bool {
            return !$this->some($predicate);
        }

        /**
         * Reduces the set to a single value by applying the given callback to each item.
         * If no initial value is provided, the first item is used as the seed.
         * @param callable(mixed, T, static): mixed $callback A callback receiving the accumulator, the item, and the set.
         * @param mixed $initial The initial accumulator value.
         * @return mixed The final accumulated value.
         */
        public function reduce (callable $callback, mixed $initial = null) : mixed {
            $items = $this->items;
            $result = $initial ?? array_shift($items);

            foreach ($items as $item) {
                $result = $callback($result, $item, $this);
            }

            return $result;
        }

        /**
         * Removes the given item from the set if it is a member.
         * @param T $item The item to remove.
         * @return static The set.
         * @throws LogicException If the set is frozen.
         */
        public function remove (mixed $item) : static {
            $this->guardFrozen();

            $hash = $this->hash($item);

            if (!isset($this->lookup[$hash])) return $this;

            unset($this->lookup[$hash]);
            $this->items = array_values(array_filter($this->items, fn ($e) => $e !== $item));

            Emitter::create()
                ->with(item: $item, set: $this)
                ->emit(Signal::SET_ITEM_REMOVED);

            return $this;
        }

        /**
         * Determines whether at least one item in the set satisfies the given predicate.
         * @param callable(T): bool $predicate A predicate receiving each item and returning bool.
         * @return bool Whether any item passes.
         */
        public function some (callable $predicate) : bool {
            foreach ($this->items as $item) {
                if ($predicate($item)) return true;
            }

            return false;
        }

        /**
         * Invokes the given callback with the set and returns the set unchanged.
         * Useful for debugging or side-effectful inspection in a method chain.
         * @param callable(static): void $callback The callback to invoke.
         * @return static The set.
         */
        public function tap (callable $callback) : static {
            $callback($this);
            return $this;
        }

        /**
         * Gets all items of the set as a plain array.
         * @return T[] The items.
         */
        public function toArray () : array {
            return $this->items;
        }

        /**
         * Unfreezes the set, re-enabling mutations.
         * @return static The set.
         */
        public function unfreeze () : static {
            $this->frozen = false;

            Emitter::create()
                ->with(collection: $this)
                ->emit(Signal::COLLECTION_UNFROZEN);

            return $this;
        }

        /**
         * Creates a new set containing all items from both this set and the other, with no duplicates.
         * @param SetInterface $other The other set.
         * @return static The union set.
         */
        public function union (SetInterface $other) : static {
            $result = $this->createInstance($this->items);

            foreach ($other as $item) {
                $result->add($item);
            }

            return $result;
        }

        /**
         * Creates a new set with the given type constraint.
         * @param string $type The type of items the set will enforce.
         * @return static The set.
         */
        public static function withType (string $type) : static {
            return new static([], $type);
        }
    }
?>

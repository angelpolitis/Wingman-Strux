<?php
    /**
     * Project Name:    Wingman Strux - Sorted List
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
    use InvalidArgumentException;
    use Traversable;
    use Wingman\Strux\Bridge\Corvus\Emitter;
    use Wingman\Strux\Bridge\Verix\Validator;
    use Wingman\Strux\Enums\Signal;
    use Wingman\Strux\Interfaces\SequenceInterface;

    /**
     * Represents an automatically sorted list that maintains ascending order at all times.
     *
     * Every call to add() locates the correct insertion point via binary search (O(log n))
     * and splices the item in, so the list is always sorted without requiring an explicit
     * sort step. Duplicate values are permitted; all occurrences are kept in stable order
     * relative to their insertion sequence.
     *
     * By default items are sorted using PHP's spaceship operator (<=>), which handles
     * integers, floats, and strings naturally. Supply a custom comparator to sort objects,
     * composite values, or to invert the order.
     *
     * Typical use-cases: priority-aware render queues, sorted search result pages,
     * merge-sorted streams, leaderboard entries, time-ordered event lists.
     *
     * @package Wingman\Strux
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     * @template V
     */
    class SortedList implements SequenceInterface {
        /**
         * Creates a new sorted list.
         * The given items are validated and sorted prior to storage, so their original
         * order does not affect the resulting list.
         * @param V[] $items The initial elements.
         * @param callable(V, V): int|null $comparator A custom comparator, or null for the default.
         * @param string|null $type The type constraint for elements.
         * @throws InvalidArgumentException If any item fails type enforcement.
         */
        public function __construct (array $items = [], ?callable $comparator = null, ?string $type = null) {
            if (isset($type)) $this->type = $type;
            if (isset($comparator)) $this->comparator = $comparator;

            if ($items !== []) {
                $this->enforceType(...array_values($items));
                usort($items, fn ($a, $b) => $this->compare($a, $b));
                $this->data = array_values($items);
            }
        }

        /**
         * The custom comparator used to determine element order, or null for the default spaceship comparison.
         * Receives two values and must return a negative integer, zero, or a positive integer.
         * @var callable(V, V): int|null
         */
        private $comparator = null;

        /**
         * The internal sorted store. Elements are stored in ascending order as determined by
         * the comparator (or the spaceship operator when no comparator is set).
         * @var V[]
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
         * Returns the leftmost index at which the given item should be inserted to preserve
         * sorted order. The returned index may equal count($this->data) when the item is
         * greater than all existing elements.
         * @param V $item The item whose insertion point to find.
         * @return int The insertion index (0-based).
         */
        private function insertionIndex (mixed $item) : int {
            $lo = 0;
            $hi = count($this->data);

            while ($lo < $hi) {
                $mid = ($lo + $hi) >> 1;

                if ($this->compare($this->data[$mid], $item) < 0) {
                    $lo = $mid + 1;
                }
                else $hi = $mid;
            }

            return $lo;
        }

        /**
         * Searches for the first occurrence of the given item using binary search.
         * @param V $item The item to search for.
         * @return int The 0-based index of the first occurrence, or -1 if not found.
         */
        private function search (mixed $item) : int {
            $idx = $this->insertionIndex($item);

            if ($idx < count($this->data) && $this->compare($this->data[$idx], $item) === 0) {
                return $idx;
            }

            return -1;
        }

        /**
         * The type that every element must conform to, or null for no enforcement.
         * @var string|null
         */
        protected ?string $type = null;

        /**
         * Compares two values using the custom comparator if one is set, or the spaceship
         * operator otherwise.
         * @param V $a The left-hand value.
         * @param V $b The right-hand value.
         * @return int A negative integer if a < b, 0 if equal, or a positive integer if a > b.
         */
        protected function compare (mixed $a, mixed $b) : int {
            if (isset($this->comparator)) return ($this->comparator)($a, $b);
            return $a <=> $b;
        }

        /**
         * Enforces the list's type constraint against each given item.
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
         * Inserts one or more items into their correct sorted positions.
         * Multiple items are each inserted individually so the list remains sorted after
         * every individual insertion.
         * @param V ...$items The items to insert.
         * @return static The list.
         * @throws InvalidArgumentException If any item fails type enforcement.
         */
        public function add (mixed ...$items) : static {
            $this->enforceType(...$items);

            foreach ($items as $item) {
                array_splice($this->data, $this->insertionIndex($item), 0, [$item]);
            }

            Emitter::create()
                ->with(items: array_values($items), collection: $this)
                ->emit(Signal::COLLECTION_ITEM_ADDED);

            return $this;
        }

        /**
         * Gets the total number of elements in the list.
         * @return int The element count.
         */
        public function count () : int {
            return $this->getSize();
        }

        /**
         * Invokes the given callback for each element and returns the list unchanged.
         * The callback receives the value, its 0-based index, and the list as its arguments.
         * @param callable(V, int, static): void $callback The callback to invoke.
         * @return static The list.
         */
        public function each (callable $callback) : static {
            foreach ($this->data as $index => $item) {
                $callback($item, $index, $this);
            }

            return $this;
        }

        /**
         * Determines whether all elements satisfy the given predicate.
         * @param callable(V, int): bool $predicate A predicate receiving the value and index.
         * @return bool Whether all elements pass.
         */
        public function every (callable $predicate) : bool {
            foreach ($this->data as $index => $item) {
                if (!$predicate($item, $index)) return false;
            }

            return true;
        }

        /**
         * Creates a new sorted list containing only the elements that satisfy the given predicate.
         * The resulting list preserves the same comparator and type constraint.
         * Because the source list is already sorted, the filtered result is also sorted without
         * an additional sort pass.
         * @param callable(V, int): bool $predicate A predicate receiving the value and index.
         * @return static The filtered list.
         */
        public function filter (callable $predicate) : static {
            return new static(
                array_values(array_filter($this->data, fn ($v, $i) => $predicate($v, $i), ARRAY_FILTER_USE_BOTH)),
                $this->comparator,
                $this->type
            );
        }

        /**
         * Finds the first element (in sorted order) that satisfies the given predicate.
         * @param callable(V, int): bool $predicate A predicate receiving the value and index.
         * @return V|null The first matching element, or null if none is found.
         */
        public function find (callable $predicate) : mixed {
            foreach ($this->data as $index => $item) {
                if ($predicate($item, $index)) return $item;
            }

            return null;
        }

        /**
         * Creates a new sorted list pre-loaded with the given items.
         * @param V[] $items The initial elements.
         * @param callable(V, V): int|null $comparator A custom comparator, or null for the default.
         * @param string|null $type The type constraint for elements.
         * @return static The created list.
         */
        public static function from (array $items, ?callable $comparator = null, ?string $type = null) : static {
            return new static(array_values($items), $comparator, $type);
        }

        /**
         * Gets the custom comparator in use, or null if the default spaceship comparison is used.
         * @return callable(V, V): int|null The comparator.
         */
        public function getComparator () : ?callable {
            return $this->comparator;
        }

        /**
         * Gets the first (smallest) element in the sorted list, or null if the list is empty.
         * @return V|null The first element.
         */
        public function getFirst () : mixed {
            return $this->data[0] ?? null;
        }

        /**
         * Gets an iterator that yields elements in sorted order (ascending by default).
         * @return Traversable<int, V> The iterator.
         */
        public function getIterator () : Traversable {
            return new ArrayIterator($this->data);
        }

        /**
         * Gets the last (largest) element in the sorted list, or null if the list is empty.
         * @return V|null The last element.
         */
        public function getLast () : mixed {
            return $this->data[count($this->data) - 1] ?? null;
        }

        /**
         * Gets the total number of elements in the list.
         * @return int The size.
         */
        public function getSize () : int {
            return count($this->data);
        }

        /**
         * Gets the type constraint enforced by the list, or null if unrestricted.
         * @return string|null The type.
         */
        public function getType () : ?string {
            return $this->type;
        }

        /**
         * Determines whether the given item exists in the list using binary search.
         * @param V $item The item to look for.
         * @return bool Whether the item exists.
         */
        public function has (mixed $item) : bool {
            return $this->search($item) !== -1;
        }

        /**
         * Gets the 0-based index of the first occurrence of the given item, or -1 if not found.
         * Uses binary search.
         * @param V $item The item to locate.
         * @return int The index, or -1.
         */
        public function indexOf (mixed $item) : int {
            return $this->search($item);
        }

        /**
         * Determines whether the list contains no elements.
         * @return bool Whether the list is empty.
         */
        public function isEmpty () : bool {
            return $this->data === [];
        }

        /**
         * Applies the given callback to every element and returns the results as a plain array.
         * The returned array is not wrapped in a SortedList because mapped values may not
         * be comparable with the current comparator.
         * @param callable(V, int, static): mixed $callback The mapping callback.
         * @return mixed[] The mapped values.
         */
        public function map (callable $callback) : array {
            $result = [];

            foreach ($this->data as $index => $item) {
                $result[] = $callback($item, $index, $this);
            }

            return $result;
        }

        /**
         * Determines whether no elements satisfy the given predicate.
         * Delegates to the inverse of some().
         * @param callable(V, int): bool $predicate A predicate receiving the value and index.
         * @return bool Whether no elements pass.
         */
        public function none (callable $predicate) : bool {
            return !$this->some($predicate);
        }

        /**
         * Applies the given callback cumulatively to all elements, reducing them to a single value.
         * The callback receives the accumulated carry, the current element, its index, and the list.
         * @param mixed $initial The initial carry value.
         * @param callable(mixed, V, int, static): mixed $callback The reduction callback.
         * @return mixed The final accumulated value.
         */
        public function reduce (mixed $initial, callable $callback) : mixed {
            $carry = $initial;

            foreach ($this->data as $index => $item) {
                $carry = $callback($carry, $item, $index, $this);
            }

            return $carry;
        }

        /**
         * Removes the first occurrence of the given item from the list using binary search.
         * Has no effect if the item is not found.
         * @param V $item The item to remove.
         * @return static The list.
         */
        public function remove (mixed $item) : static {
            $idx = $this->search($item);
            if ($idx !== -1) {
                array_splice($this->data, $idx, 1);

                Emitter::create()
                    ->with(collection: $this)
                    ->emit(Signal::COLLECTION_ITEM_REMOVED);
            }
            return $this;
        }

        /**
         * Removes the element at the given 0-based index.
         * Has no effect if the index is out of bounds.
         * @param int $index The 0-based index of the element to remove.
         * @return static The list.
         */
        public function removeAt (int $index) : static {
            if ($index >= 0 && $index < count($this->data)) {
                array_splice($this->data, $index, 1);

                Emitter::create()
                    ->with(collection: $this)
                    ->emit(Signal::COLLECTION_ITEM_REMOVED);
            }
            return $this;
        }

        /**
         * Determines whether at least one element satisfies the given predicate.
         * @param callable(V, int): bool $predicate A predicate receiving the value and index.
         * @return bool Whether any element passes.
         */
        public function some (callable $predicate) : bool {
            foreach ($this->data as $index => $item) {
                if ($predicate($item, $index)) return true;
            }
            return false;
        }

        /**
         * Invokes the given callback with the list and returns the list unchanged.
         * Useful for debugging or side-effectful inspection in a method chain.
         * @param callable(static): void $callback The callback to invoke.
         * @return static The list.
         */
        public function tap (callable $callback) : static {
            $callback($this);
            return $this;
        }

        /**
         * Gets all elements as a plain array in sorted order.
         * @return V[] The elements.
         */
        public function toArray () : array {
            return $this->data;
        }

        /**
         * Creates a new sorted list containing the current elements, ordered by the given comparator.
         * @param callable(V, V): int $comparator The comparator to use.
         * @return static The list.
         */
        public function withComparator (callable $comparator) : static {
            return new static($this->data, $comparator);
        }

        /**
         * Creates a new empty sorted list with the given type constraint for elements.
         * @param string $type The type of elements the list will enforce.
         * @return static The list.
         */
        public static function withType (string $type) : static {
            return new static([], null, $type);
        }
    }
?>
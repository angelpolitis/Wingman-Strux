<?php
    /**
     * Project Name:    Wingman Strux - Typed Sorted List
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

    # Import the following classes to the current scope.
    use LogicException;

    /**
     * Represents an automatically sorted list with element type enforcement.
     *
     * Concrete subclasses must declare a typed {@see SortedList::$type} class property to
     * activate enforcement. Direct instantiation of this class is intentionally disallowed —
     * use a subclass that specifies the target element type.
     *
     * The type constraint may be any PHP primitive alias, a fully-qualified class or
     * interface name, or a Verix schema expression (requires the Wingman Verix package).
     *
     * @package Wingman\Strux
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     * @template V
     * @extends SortedList<V>
     */
    abstract class TypedSortedList extends SortedList {
        /**
         * Creates a new typed sorted list.
         * The type is not accepted as a constructor parameter — it must be declared as a
         * class property in the concrete subclass. The optional third parameter exists only
         * for internal compatibility with parent methods such as filter() and is ignored.
         * @param V[] $items The initial elements.
         * @param callable(V, V): int|null $comparator A custom comparator, or null for the default.
         * @param string|null $type Ignored; exists only for signature compatibility.
         */
        public function __construct (array $items = [], ?callable $comparator = null, ?string $type = null) {
            parent::__construct($items, $comparator, null);
        }

        /**
         * Creates a new typed sorted list pre-loaded with the given items.
         * The type parameter is intentionally omitted — it is declared on the subclass.
         * @param V[] $items The initial elements.
         * @param callable(V, V): int|null $comparator A custom comparator, or null for the default.
         * @param string|null $type Ignored; exists only for signature compatibility.
         * @return static The created list.
         */
        public static function from (array $items, ?callable $comparator = null, ?string $type = null) : static {
            return new static(array_values($items), $comparator);
        }

        /**
         * Not supported on TypedSortedList; the type is declared as a class property on the subclass.
         * @throws LogicException Always.
         */
        public static function withType (string $type) : static {
            throw new LogicException(static::class . " has a fixed element type and does not support withType().");
        }
    }
?>
<?php
    /**
     * Project Name:    Wingman Strux - Set Interface
     * Created by:      Angel Politis
     * Creation Date:   Mar 15 2026
     * Last Modified:   Mar 17 2026
     *
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Strux Interfaces namespace.
    namespace Wingman\Strux\Interfaces;

    # Import the following interfaces to the current scope.
    use Countable;
    use IteratorAggregate;

    /**
     * Describes an unordered collection that contains no duplicate elements.
     *
     * Membership checks, insertions, and removals must all be O(1) in conforming
     * implementations. The set operations (union, intersect, diff) return new
     * instances, preserving immutability where required.
     *
     * @package Wingman\Strux\Interfaces
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     * @template T
     * @extends IteratorAggregate<int, T>
     */
    interface SetInterface extends Countable, IteratorAggregate {
        /**
         * Adds one or more items to the set. Duplicate items are silently discarded.
         * @param mixed ...$items The items to add.
         * @return static The set.
         */
        public function add (mixed ...$items) : static;

        /**
         * Returns whether the given item is a member of the set.
         * @param mixed $item The item to search for.
         * @return bool Whether the item exists.
         */
        public function contains (mixed $item) : bool;

        /**
         * Returns a new set containing items present in this set but absent from the other.
         * @param static $other The other set.
         * @return static The difference.
         */
        public function diff (self $other) : static;

        /**
         * Returns the number of items in the set.
         * @return int The size.
         */
        public function getSize () : int;

        /**
         * Returns a new set containing only items present in both sets.
         * @param static $other The other set.
         * @return static The intersection.
         */
        public function intersect (self $other) : static;

        /**
         * Returns whether the set contains no items.
         * @return bool Whether the set is empty.
         */
        public function isEmpty () : bool;

        /**
         * Removes the given item from the set if it is a member.
         * @param mixed $item The item to remove.
         * @return static The set.
         */
        public function remove (mixed $item) : static;

        /**
         * Returns a new set containing all items from both sets with no duplicates.
         * @param static $other The other set.
         * @return static The union.
         */
        public function union (self $other) : static;
    }
?>

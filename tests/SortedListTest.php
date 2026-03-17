<?php
    /**
     * Project Name:    Wingman Strux - Sorted List Tests
     * Created by:      Angel Politis
     * Creation Date:   Mar 16 2026
     * Last Modified:   Mar 17 2026
     *
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Strux Tests namespace.
    namespace Wingman\Strux\Tests;

    # Import the following classes to the current scope.
    use Wingman\Argus\Attributes\Define;
    use Wingman\Argus\Attributes\Group;
    use Wingman\Argus\Test;
    use Wingman\Strux\Interfaces\SequenceInterface;
    use Wingman\Strux\SortedList;

    /**
     * Tests for the SortedList class, covering auto-sorted insertion, membership tests,
     * index-based access, removal, custom comparator, and interface compliance.
     * @package Wingman\Strux\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class SortedListTest extends Test {

        // ─── Sorted Insertion ────────────────────────────────────────────────────

        #[Group("SortedList")]
        #[Define(
            name: "add() — Inserts Items In Sorted Order",
            description: "add() inserts items so that the list remains sorted in ascending order by default."
        )]
        public function testAddInsertsSorted () : void {
            $list = new SortedList();
            $list->add(5, 2, 8, 1, 9, 3);

            $array = $list->toArray();

            $this->assertTrue($array === array_values($array) && $array[0] === 1, "List should start with the smallest value (1).");
            $this->assertTrue(end($array) === 9, "List should end with the largest value (9).");
            $this->assertTrue($array === [1, 2, 3, 5, 8, 9], "List should be sorted ascending: [1,2,3,5,8,9].");
        }

        // ─── Membership ──────────────────────────────────────────────────────────

        #[Group("SortedList")]
        #[Define(
            name: "has() — Returns True For Present Item",
            description: "has() returns true for an item that exists in the list."
        )]
        public function testHasReturnsTrueForPresentItem () : void {
            $list = SortedList::from([10, 20, 30]);

            $this->assertTrue($list->has(20), "has() should return true for 20.");
            $this->assertTrue(!$list->has(99), "has() should return false for 99.");
        }

        // ─── Index Access ────────────────────────────────────────────────────────

        #[Group("SortedList")]
        #[Define(
            name: "indexOf() — Returns Correct Position",
            description: "indexOf() returns the zero-based index of an item in the sorted list."
        )]
        public function testIndexOfReturnsCorrectPosition () : void {
            $list = SortedList::from([5, 10, 15, 20]);

            $this->assertTrue($list->indexOf(10) === 1, "indexOf(10) should return 1.");
            $this->assertTrue($list->indexOf(99) === -1, "indexOf(99) should return -1 for a missing item.");
        }

        #[Group("SortedList")]
        #[Define(
            name: "first() / last() — Return Boundary Items",
            description: "first() returns the smallest item and last() returns the largest."
        )]
        public function testFirstAndLastReturnBoundaryItems () : void {
            $list = SortedList::from([7, 3, 1, 9, 4]);

            $this->assertTrue($list->getFirst() === 1, "getFirst() should return 1 (the minimum).");
            $this->assertTrue($list->getLast() === 9, "getLast() should return 9 (the maximum).");
        }

        // ─── Removal ─────────────────────────────────────────────────────────────

        #[Group("SortedList")]
        #[Define(
            name: "remove() — Removes An Item By Value",
            description: "remove() removes the first occurrence of the item by value."
        )]
        public function testRemoveRemovesItemByValue () : void {
            $list = SortedList::from([1, 2, 3, 4, 5]);
            $list->remove(3);

            $this->assertTrue(!$list->has(3), "3 should be absent after remove().");
            $this->assertTrue($list->getSize() === 4, "Size should be 4 after removing one item.");
        }

        #[Group("SortedList")]
        #[Define(
            name: "removeAt() — Removes An Item By Index",
            description: "removeAt() removes the item at the specified zero-based index."
        )]
        public function testRemoveAtRemovesItemByIndex () : void {
            $list = SortedList::from([10, 20, 30]);
            $list->removeAt(0);

            $this->assertTrue($list->getFirst() === 20, "After removing index 0, getFirst() should return 20.");
        }

        // ─── Custom Comparator ───────────────────────────────────────────────────

        #[Group("SortedList")]
        #[Define(
            name: "withComparator() — Custom Sort Order",
            description: "withComparator() returns a new SortedList using the supplied comparator for ordering."
        )]
        public function testWithComparatorCustomSortOrder () : void {
            $descending = SortedList::from([3, 1, 4, 1, 5])
                ->withComparator(fn ($a, $b) => $b <=> $a);

            $this->assertTrue($descending->getFirst() === 5, "getFirst() with descending comparator should return the largest value (5).");
            $this->assertTrue($descending->getLast() === 1, "getLast() with descending comparator should return the smallest value (1).");
        }

        // ─── Interface Compliance ────────────────────────────────────────────────

        #[Group("SortedList")]
        #[Define(
            name: "Implements SequenceInterface",
            description: "SortedList implements SequenceInterface."
        )]
        public function testImplementsSequenceInterface () : void {
            $this->assertTrue(new SortedList() instanceof SequenceInterface, "SortedList must implement SequenceInterface.");
        }
    }
?>
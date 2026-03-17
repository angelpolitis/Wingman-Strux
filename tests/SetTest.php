<?php
    /**
     * Project Name:    Wingman Strux - Set Tests
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
    use LogicException;
    use Wingman\Argus\Attributes\Define;
    use Wingman\Argus\Attributes\Group;
    use Wingman\Argus\Test;
    use Wingman\Strux\Interfaces\SetInterface;
    use Wingman\Strux\Set;

    /**
     * Tests for the Set class, covering uniqueness invariant, set algebra (union,
     * intersect, diff), freeze semantics, and interface compliance.
     * @package Wingman\Strux\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class SetTest extends Test {

        // ─── Uniqueness ──────────────────────────────────────────────────────────

        #[Group("Set")]
        #[Define(
            name: "add() — Silently Discards Duplicates",
            description: "Adding a duplicate item does not increase the set size."
        )]
        public function testAddSilentlyDiscardsDuplicates () : void {
            $set = new Set();
            $set->add(1)->add(1)->add(2);

            $this->assertTrue($set->getSize() === 2, "Set should have 2 unique items despite 3 add calls.");
        }

        #[Group("Set")]
        #[Define(
            name: "contains() — Returns True For Present Item",
            description: "contains() returns true for an item already in the set."
        )]
        public function testContainsReturnsTrueForPresentItem () : void {
            $set = Set::from(["apple", "banana"]);

            $this->assertTrue($set->contains("apple"), "contains() should return true for 'apple'.");
        }

        #[Group("Set")]
        #[Define(
            name: "remove() — Removes Item",
            description: "remove() removes an existing item from the set."
        )]
        public function testRemoveRemovesItem () : void {
            $set = Set::from([1, 2, 3]);
            $set->remove(2);

            $this->assertTrue(!$set->contains(2), "Set should no longer contain 2 after remove().");
            $this->assertTrue($set->getSize() === 2, "Size should be 2 after removing one item.");
        }

        // ─── Set Algebra ─────────────────────────────────────────────────────────

        #[Group("Set")]
        #[Define(
            name: "union() — Contains All Items From Both Sets",
            description: "union() returns a new set containing every item from each operand with no duplicates."
        )]
        public function testUnionContainsAllItemsFromBothSets () : void {
            $a = Set::from([1, 2, 3]);
            $b = Set::from([3, 4, 5]);
            $result = $a->union($b);

            $this->assertTrue($result->getSize() === 5, "Union of {1,2,3} and {3,4,5} should have 5 unique items.");
        }

        #[Group("Set")]
        #[Define(
            name: "intersect() — Contains Only Shared Items",
            description: "intersect() returns a new set containing only items present in both operands."
        )]
        public function testIntersectContainsOnlySharedItems () : void {
            $a = Set::from([1, 2, 3, 4]);
            $b = Set::from([3, 4, 5, 6]);
            $result = $a->intersect($b);

            $this->assertTrue($result->getSize() === 2, "Intersection should have 2 items: 3 and 4.");
            $this->assertTrue($result->contains(3) && $result->contains(4), "Intersection should contain 3 and 4.");
        }

        #[Group("Set")]
        #[Define(
            name: "diff() — Contains Only Items Exclusive To First Set",
            description: "diff() returns a new set with items in the first set that are not in the second."
        )]
        public function testDiffContainsOnlyExclusiveItems () : void {
            $a = Set::from([1, 2, 3, 4]);
            $b = Set::from([3, 4, 5]);
            $result = $a->diff($b);

            $this->assertTrue($result->getSize() === 2, "Diff should have 2 items: 1 and 2.");
            $this->assertTrue($result->contains(1) && $result->contains(2), "Diff should contain 1 and 2.");
        }

        // ─── Freeze ──────────────────────────────────────────────────────────────

        #[Group("Set")]
        #[Define(
            name: "freeze() — Prevents Mutation",
            description: "Calling add() on a frozen set throws a LogicException."
        )]
        public function testFreezePreventsMutation () : void {
            $set = Set::from([1, 2])->freeze();

            $thrown = false;
            try {
                $set->add(3);
            } catch (LogicException $e) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "Adding to a frozen set should throw LogicException.");
        }

        // ─── Interface Compliance ────────────────────────────────────────────────

        #[Group("Set")]
        #[Define(
            name: "Implements SetInterface",
            description: "Set implements SetInterface."
        )]
        public function testImplementsSetInterface () : void {
            $this->assertTrue(new Set() instanceof SetInterface, "Set must implement SetInterface.");
        }
    }
?>
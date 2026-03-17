<?php
    /**
     * Project Name:    Wingman Strux - Collection Tests
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
    use Wingman\Strux\Collection;
    use Wingman\Strux\Interfaces\SequenceInterface;

    /**
     * Tests for the Collection class, covering the full public API: mutation, query,
     * functional iteration, cap enforcement, freeze semantics, and factory methods.
     * @package Wingman\Strux\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class CollectionTest extends Test {

        // ─── Mutation ────────────────────────────────────────────────────────────

        #[Group("Collection")]
        #[Define(
            name: "add() — Appends Items",
            description: "add() appends one or more items and increases the size accordingly."
        )]
        public function testAddAppendsItems () : void {
            $col = new Collection();
            $col->add(1, 2, 3);

            $this->assertTrue($col->getSize() === 3, "Size should be 3 after adding three items.");
            $this->assertTrue($col->get(0) === 1, "First item should be 1.");
            $this->assertTrue($col->get(2) === 3, "Third item should be 3.");
        }

        #[Group("Collection")]
        #[Define(
            name: "remove() — Removes Item By Index",
            description: "remove() removes the item at the given index and shifts subsequent items down."
        )]
        public function testRemoveRemovesItemByIndex () : void {
            $col = Collection::from([10, 20, 30]);
            $col->remove(1);

            $this->assertTrue($col->getSize() === 2, "Size should be 2 after removal.");
            $this->assertTrue($col->get(0) === 10, "First item should still be 10.");
            $this->assertTrue($col->get(1) === 30, "Second item should now be 30.");
        }

        #[Group("Collection")]
        #[Define(
            name: "with() — Returns New Collection With Extra Items",
            description: "with() returns a new collection containing the original items plus the supplied ones."
        )]
        public function testWithReturnsNewCollectionWithExtraItems () : void {
            $original = Collection::from([1, 2]);
            $extended = $original->with(3, 4);

            $this->assertTrue($original->getSize() === 2, "Original collection should be unchanged.");
            $this->assertTrue($extended->getSize() === 4, "New collection should have 4 items.");
        }

        #[Group("Collection")]
        #[Define(
            name: "without() — Returns New Collection Without Targets",
            description: "without() returns a new collection with all occurrences of the supplied values removed."
        )]
        public function testWithoutReturnsNewCollectionWithoutTargets () : void {
            $col = Collection::from([1, 2, 3, 2, 1]);
            $result = $col->without(2);

            $this->assertTrue($result->getSize() === 3, "Result should contain 3 items.");
            $this->assertTrue(!$result->contains(2), "Result should not contain 2.");
        }

        // ─── Query ───────────────────────────────────────────────────────────────

        #[Group("Collection")]
        #[Define(
            name: "contains() — Returns True For Existing Item",
            description: "contains() returns true for an item that exists in the collection."
        )]
        public function testContainsReturnsTrueForExistingItem () : void {
            $col = Collection::from(["alpha", "beta", "gamma"]);

            $this->assertTrue($col->contains("beta"), "contains() should return true for 'beta'.");
        }

        #[Group("Collection")]
        #[Define(
            name: "contains() — Returns False For Absent Item",
            description: "contains() returns false for an item not present in the collection."
        )]
        public function testContainsReturnsFalseForAbsentItem () : void {
            $col = Collection::from(["alpha", "beta"]);

            $this->assertTrue(!$col->contains("delta"), "contains() should return false for 'delta'.");
        }

        #[Group("Collection")]
        #[Define(
            name: "isEmpty() — Returns True For Empty Collection",
            description: "isEmpty() returns true when the collection has no items."
        )]
        public function testIsEmptyReturnsTrueForEmptyCollection () : void {
            $col = new Collection();

            $this->assertTrue($col->isEmpty(), "isEmpty() should return true for a new empty collection.");
        }

        #[Group("Collection")]
        #[Define(
            name: "getFirst() / getLast() — Return Boundary Items",
            description: "getFirst() returns the first item and getLast() returns the last item."
        )]
        public function testGetFirstAndGetLast () : void {
            $col = Collection::from([100, 200, 300]);

            $this->assertTrue($col->getFirst() === 100, "getFirst() should return 100.");
            $this->assertTrue($col->getLast() === 300, "getLast() should return 300.");
        }

        #[Group("Collection")]
        #[Define(
            name: "indexOf() — Returns Correct Index",
            description: "indexOf() returns the zero-based index of the first occurrence of an item."
        )]
        public function testIndexOfReturnsCorrectIndex () : void {
            $col = Collection::from(["a", "b", "c", "b"]);

            $this->assertTrue($col->indexOf("b") === 1, "indexOf('b') should return 1.");
        }

        // ─── Functional ──────────────────────────────────────────────────────────

        #[Group("Collection")]
        #[Define(
            name: "filter() — Returns Items Matching Predicate",
            description: "filter() returns a new collection containing only items for which the predicate returns true."
        )]
        public function testFilterReturnsMatchingItems () : void {
            $col = Collection::from([1, 2, 3, 4, 5, 6]);
            $evens = $col->filter(fn ($n) => $n % 2 === 0);

            $this->assertTrue($evens->getSize() === 3, "Filtered collection should have 3 even numbers.");
            $this->assertTrue($evens->contains(2) && $evens->contains(4) && $evens->contains(6), "Should contain 2, 4, 6.");
        }

        #[Group("Collection")]
        #[Define(
            name: "find() — Returns First Matching Item",
            description: "find() returns the value of the first item satisfying the predicate, or null."
        )]
        public function testFindReturnsFirstMatchingItem () : void {
            $col = Collection::from([1, 2, 3, 4, 5]);
            $result = $col->find(fn ($n) => $n > 3);

            $this->assertTrue($result === 4, "find() should return 4 as the first item greater than 3.");
        }

        #[Group("Collection")]
        #[Define(
            name: "find() — Returns Null When No Match",
            description: "find() returns null when no item satisfies the predicate."
        )]
        public function testFindReturnsNullWhenNoMatch () : void {
            $col = Collection::from([1, 2, 3]);
            $result = $col->find(fn ($n) => $n > 100);

            $this->assertTrue($result === null, "find() should return null when no item matches.");
        }

        #[Group("Collection")]
        #[Define(
            name: "map() — Transforms Each Item",
            description: "map() applies a callback to every item and returns the results as a plain array."
        )]
        public function testMapTransformsEachItem () : void {
            $col = Collection::from([1, 2, 3]);
            $doubled = $col->map(fn ($n) => $n * 2);

            $this->assertTrue($doubled === [2, 4, 6], "map() should return [2, 4, 6].");
        }

        #[Group("Collection")]
        #[Define(
            name: "reduce() — Accumulates Items",
            description: "reduce() folds all items into a single value using the given callback."
        )]
        public function testReduceAccumulatesItems () : void {
            $col = Collection::from([1, 2, 3, 4, 5]);
            $sum = $col->reduce(fn ($carry, $n) => $carry + $n, 0);

            $this->assertTrue($sum === 15, "reduce() should sum to 15.");
        }

        #[Group("Collection")]
        #[Define(
            name: "every() — Returns True When All Match",
            description: "every() returns true only when every item satisfies the predicate."
        )]
        public function testEveryReturnsTrueWhenAllMatch () : void {
            $col = Collection::from([2, 4, 6]);

            $this->assertTrue($col->every(fn ($n) => $n % 2 === 0), "every() should return true for all evens.");
        }

        #[Group("Collection")]
        #[Define(
            name: "some() — Returns True When Any Matches",
            description: "some() returns true when at least one item satisfies the predicate."
        )]
        public function testSomeReturnsTrueWhenAnyMatches () : void {
            $col = Collection::from([1, 3, 5, 6]);

            $this->assertTrue($col->some(fn ($n) => $n % 2 === 0), "some() should return true since 6 is even.");
        }

        #[Group("Collection")]
        #[Define(
            name: "none() — Returns True When None Match",
            description: "none() returns true when no item satisfies the predicate."
        )]
        public function testNoneReturnsTrueWhenNoneMatch () : void {
            $col = Collection::from([1, 3, 5]);

            $this->assertTrue($col->none(fn ($n) => $n % 2 === 0), "none() should return true since no item is even.");
        }

        #[Group("Collection")]
        #[Define(
            name: "orderBy() — Returns Sorted Copy Without Mutating Original",
            description: "orderBy() returns a new sorted collection and leaves the original unchanged."
        )]
        public function testOrderByReturnsSortedCopyWithoutMutation () : void {
            $col = Collection::from([3, 1, 4, 1, 5]);
            $sorted = $col->orderBy(fn ($x) => $x);

            $this->assertTrue($sorted->get(0) === 1, "First item of sorted collection should be 1.");
            $this->assertTrue($col->get(0) === 3, "Original collection should be unchanged.");
        }

        #[Group("Collection")]
        #[Define(
            name: "chunk() — Splits Into Fixed-Size Chunks",
            description: "chunk() divides the collection into chunks of the given size."
        )]
        public function testChunkSplitsIntoFixedSizeChunks () : void {
            $col = Collection::from([1, 2, 3, 4, 5]);
            $chunks = $col->chunk(2);

            $this->assertTrue(count($chunks) === 3, "Should produce 3 chunks from 5 items with chunk size 2.");
            $this->assertTrue($chunks[0]->getSize() === 2, "First chunk should have 2 items.");
            $this->assertTrue($chunks[2]->getSize() === 1, "Last chunk should have 1 item.");
        }

        #[Group("Collection")]
        #[Define(
            name: "deduplicate() — Removes Duplicates",
            description: "deduplicate() returns a new collection with duplicate items removed."
        )]
        public function testUniqueRemovesDuplicates () : void {
            $col = Collection::from([1, 2, 2, 3, 3, 3]);
            $unique = $col->deduplicate();

            $this->assertTrue($unique->getSize() === 3, "Unique collection should have 3 items.");
        }

        #[Group("Collection")]
        #[Define(
            name: "partition() — Splits Into Two Parts",
            description: "partition() returns a two-element array: [passing items, failing items]."
        )]
        public function testPartitionSplitsIntoTwoParts () : void {
            $col = Collection::from([1, 2, 3, 4, 5]);
            [$evens, $odds] = $col->partition(fn ($n) => $n % 2 === 0);

            $this->assertTrue($evens->getSize() === 2, "Even partition should have 2 items.");
            $this->assertTrue($odds->getSize() === 3, "Odd partition should have 3 items.");
        }

        #[Group("Collection")]
        #[Define(
            name: "sum() — Computes Total",
            description: "sum() returns the sum of all items (or of the key-selected value)."
        )]
        public function testSumComputesTotal () : void {
            $col = Collection::from([10, 20, 30]);

            $this->assertTrue($col->sum() === 60, "sum() should return 60 for [10, 20, 30].");
        }

        // ─── Freeze / Cap ────────────────────────────────────────────────────────

        #[Group("Collection")]
        #[Define(
            name: "freeze() — Prevents Mutation",
            description: "After freeze(), any attempt to add items throws a LogicException."
        )]
        public function testFreezePreventsMutation () : void {
            $col = Collection::from([1, 2, 3]);
            $col->freeze();

            $thrown = false;
            try {
                $col->add(4);
            } catch (LogicException $e) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "Adding to a frozen collection should throw LogicException.");
        }

        #[Group("Collection")]
        #[Define(
            name: "unfreeze() — Restores Mutability",
            description: "After unfreeze(), items can be added again."
        )]
        public function testUnfreezeRestoresMutability () : void {
            $col = Collection::from([1])->freeze()->unfreeze();
            $col->add(2);

            $this->assertTrue($col->getSize() === 2, "Adding after unfreeze() should succeed.");
        }

        #[Group("Collection")]
        #[Define(
            name: "withCap() — Enforces Capacity",
            description: "Adding beyond the cap throws a LogicException."
        )]
        public function testWithCapEnforcesCapacity () : void {
            $col = Collection::withCap(2);
            $col->add(1, 2);

            $thrown = false;
            try {
                $col->add(3);
            } catch (LogicException $e) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "Exceeding the cap should throw LogicException.");
        }

        // ─── Interface Compliance ────────────────────────────────────────────────

        #[Group("Collection")]
        #[Define(
            name: "Implements SequenceInterface",
            description: "Collection implements SequenceInterface."
        )]
        public function testImplementsSequenceInterface () : void {
            $this->assertTrue(new Collection() instanceof SequenceInterface, "Collection must implement SequenceInterface.");
        }
    }
?>
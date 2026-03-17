<?php
    /**
     * Project Name:    Wingman Strux - Lazy Collection Tests
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
    use Wingman\Strux\Collection;
    use Wingman\Strux\LazyCollection;

    /**
     * Tests for the LazyCollection class, covering make(), range(), repeat(),
     * take(), skip(), filter(), map(), collect(), find(), first(), and toArray().
     * @package Wingman\Strux\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class LazyCollectionTest extends Test {

        // ─── Factories ───────────────────────────────────────────────────────────

        #[Group("LazyCollection")]
        #[Define(
            name: "make() — From Iterable Source",
            description: "make() accepts an array and yields all its items when iterated."
        )]
        public function testMakeFromIterableSource () : void {
            $lazy = LazyCollection::make([10, 20, 30]);
            $result = $lazy->toArray();

            $this->assertTrue(count($result) === 3, "Lazy collection should yield 3 items.");
            $this->assertTrue(in_array(20, $result), "Result should contain 20.");
        }

        #[Group("LazyCollection")]
        #[Define(
            name: "range() — Produces Integer Sequence",
            description: "range() with take() yields the expected integer sequence."
        )]
        public function testRangeProducesIntegerSequence () : void {
            $result = LazyCollection::range(1, 2)->take(4)->toArray();

            $this->assertTrue(count($result) === 4, "take(4) should yield 4 items.");
            $this->assertTrue(array_values($result) === [1, 3, 5, 7], "range(1,2)->take(4) should yield [1,3,5,7].");
        }

        #[Group("LazyCollection")]
        #[Define(
            name: "repeat() — Repeats A Value N Times",
            description: "repeat() with a finite count yields the value exactly that many times."
        )]
        public function testRepeatGivenTimesProducesCorrectCount () : void {
            $result = LazyCollection::repeat("x", 5)->toArray();

            $this->assertTrue(count($result) === 5, "repeat('x', 5) should yield 5 items.");
            $this->assertTrue(array_unique($result) === ["x"], "All items should be 'x'.");
        }

        // ─── Transformations ─────────────────────────────────────────────────────

        #[Group("LazyCollection")]
        #[Define(
            name: "take() / skip() — Windowing Operations",
            description: "take() limits output to N items; skip() discards the first N items."
        )]
        public function testTakeAndSkip () : void {
            $source = LazyCollection::make([1, 2, 3, 4, 5]);

            $taken = array_values($source->take(3)->toArray());
            $this->assertTrue($taken === [1, 2, 3], "take(3) should yield [1,2,3].");

            $skipped = array_values($source->skip(3)->toArray());
            $this->assertTrue($skipped === [4, 5], "skip(3) should yield [4,5].");
        }

        #[Group("LazyCollection")]
        #[Define(
            name: "filter() — Yields Only Matching Items",
            description: "filter() returns a new LazyCollection yielding only items where the predicate is true."
        )]
        public function testFilterYieldsOnlyMatchingItems () : void {
            $result = LazyCollection::make([1, 2, 3, 4, 5, 6])
                ->filter(fn ($n) => $n % 2 === 0)
                ->toArray();

            $this->assertTrue(count($result) === 3, "filter() should yield 3 even numbers.");
            $this->assertTrue(!in_array(1, $result), "Odd numbers should be excluded.");
        }

        #[Group("LazyCollection")]
        #[Define(
            name: "map() — Transforms Every Item",
            description: "map() applies the callback to every item, returning a new lazy sequence."
        )]
        public function testMapTransformsEveryItem () : void {
            $result = array_values(LazyCollection::make([1, 2, 3])
                ->map(fn ($n) => $n * 10)
                ->toArray());

            $this->assertTrue($result === [10, 20, 30], "map() should return [10,20,30].");
        }

        // ─── Materialisation ─────────────────────────────────────────────────────

        #[Group("LazyCollection")]
        #[Define(
            name: "collect() — Materialises Into Collection",
            description: "collect() returns an instance of Collection containing all yielded items."
        )]
        public function testCollectMaterialisesIntoCollection () : void {
            $collection = LazyCollection::make([1, 2, 3])->collect();

            $this->assertTrue($collection instanceof Collection, "collect() should return a Collection.");
            $this->assertTrue($collection->getSize() === 3, "Collection should have 3 items.");
        }

        // ─── Terminal Operations ─────────────────────────────────────────────────

        #[Group("LazyCollection")]
        #[Define(
            name: "find() — Returns First Matching Item",
            description: "find() returns the first item for which the predicate is true, or null."
        )]
        public function testFindReturnsFirstMatchingItem () : void {
            $result = LazyCollection::make([5, 12, 3, 20])->find(fn ($n) => $n > 10);

            $this->assertTrue($result === 12, "find() should return 12, the first item greater than 10.");
        }

        #[Group("LazyCollection")]
        #[Define(
            name: "first() — Returns The First Item",
            description: "first() returns the first item produced by the sequence without full evaluation."
        )]
        public function testFirstReturnsFirstItem () : void {
            $result = LazyCollection::make([99, 1, 2])->getFirst();

            $this->assertTrue($result === 99, "first() should return 99.");
        }

        #[Group("LazyCollection")]
        #[Define(
            name: "isEmpty() / getSize() — Report Sequence State",
            description: "isEmpty() returns true for an empty source and false otherwise; getSize() counts all evaluated items."
        )]
        public function testIsEmptyAndGetSize () : void {
            $empty = LazyCollection::make([]);
            $full = LazyCollection::make([1, 2, 3]);

            $this->assertTrue($empty->isEmpty(), "An empty lazy collection should report isEmpty() as true.");
            $this->assertTrue($full->getSize() === 3, "getSize() should return 3 for a 3-item sequence.");
        }
    }
?>
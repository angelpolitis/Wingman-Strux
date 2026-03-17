<?php
    /**
     * Project Name:    Wingman Strux - Multi Map Tests
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
    use Wingman\Strux\MultiMap;

    /**
     * Tests for the MultiMap class, covering multi-value buckets, removal by value,
     * flat value enumeration, and aggregate helpers.
     * @package Wingman\Strux\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class MultiMapTest extends Test {

        // ─── Bucket Operations ───────────────────────────────────────────────────

        #[Group("MultiMap")]
        #[Define(
            name: "set() Appends Values To A Bucket",
            description: "Calling set() twice for the same key appends values rather than replacing them."
        )]
        public function testSetAppendsToBucket () : void {
            $map = new MultiMap();
            $map->set("color", "red")->set("color", "blue");

            $this->assertTrue(count($map->get("color")) === 2, "Bucket 'color' should contain 2 values after two set() calls.");
            $this->assertTrue(in_array("red", $map->get("color")), "Bucket should contain 'red'.");
            $this->assertTrue(in_array("blue", $map->get("color")), "Bucket should contain 'blue'.");
        }

        #[Group("MultiMap")]
        #[Define(
            name: "getFirst() — Returns First Value In Bucket",
            description: "getFirst() returns the first value appended to the bucket, not the entire array."
        )]
        public function testGetFirstReturnsFirstValueInBucket () : void {
            $map = new MultiMap();
            $map->set("letters", "a")->set("letters", "b")->set("letters", "c");

            $this->assertTrue($map->getFirst("letters") === "a", "getFirst() should return 'a'.");
        }

        #[Group("MultiMap")]
        #[Define(
            name: "has() — Returns True For Populated Key",
            description: "has() returns true for a key with at least one value and false for an absent key."
        )]
        public function testHasReturnsCorrectly () : void {
            $map = new MultiMap();
            $map->set("present", "value");

            $this->assertTrue($map->has("present"), "has() should return true for 'present'.");
            $this->assertTrue(!$map->has("missing"), "has() should return false for an absent key.");
        }

        #[Group("MultiMap")]
        #[Define(
            name: "hasValue() — Checks Within A Bucket",
            description: "hasValue() returns true only when the specific value exists inside the given key's bucket."
        )]
        public function testHasValueChecksWithinBucket () : void {
            $map = new MultiMap();
            $map->set("tags", "php")->set("tags", "oop");

            $this->assertTrue($map->hasValue("tags", "php"), "hasValue() should be true for ('tags', 'php').");
            $this->assertTrue(!$map->hasValue("tags", "java"), "hasValue() should be false for ('tags', 'java').");
        }

        // ─── Removal ─────────────────────────────────────────────────────────────

        #[Group("MultiMap")]
        #[Define(
            name: "removeValue() — Removes A Single Value From A Bucket",
            description: "removeValue() removes the specified value from the bucket while leaving other values intact."
        )]
        public function testRemoveValueRemovesSingleValueFromBucket () : void {
            $map = new MultiMap();
            $map->set("tags", "php")->set("tags", "oop")->set("tags", "ddd");
            $map->removeValue("tags", "oop");

            $this->assertTrue(!$map->hasValue("tags", "oop"), "Value 'oop' should be removed from bucket 'tags'.");
            $this->assertTrue($map->hasValue("tags", "php"), "Value 'php' should remain in bucket 'tags'.");
            $this->assertTrue($map->hasValue("tags", "ddd"), "Value 'ddd' should remain in bucket 'tags'.");
        }

        #[Group("MultiMap")]
        #[Define(
            name: "remove() — Removes An Entire Bucket",
            description: "remove() deletes the entire bucket and all its values for the given key."
        )]
        public function testRemoveDeletesEntireBucket () : void {
            $map = new MultiMap();
            $map->set("letters", "a")->set("letters", "b");
            $map->remove("letters");

            $this->assertTrue(!$map->has("letters"), "Bucket 'letters' should be absent after remove().");
        }

        // ─── Aggregates ──────────────────────────────────────────────────────────

        #[Group("MultiMap")]
        #[Define(
            name: "getValues() — Returns All Values Flattened",
            description: "getValues() returns a flat list of all values across all buckets."
        )]
        public function testGetValuesReturnsFlatList () : void {
            $map = new MultiMap();
            $map->set("a", 1)->set("a", 2)->set("b", 3);
            $values = $map->getValues();

            $this->assertTrue(in_array(1, $values) && in_array(2, $values) && in_array(3, $values), "getValues() should include all 3 values.");
        }

        #[Group("MultiMap")]
        #[Define(
            name: "countAll() — Counts Total Values Across All Buckets",
            description: "countAll() returns the sum of all values across every bucket."
        )]
        public function testCountAllCountsTotalValues () : void {
            $map = new MultiMap();
            $map->set("a", 1)->set("a", 2)->set("b", 10)->set("b", 20)->set("b", 30);

            $this->assertTrue($map->countAll() === 5, "countAll() should return 5 for 2 + 3 values.");
        }
    }
?>
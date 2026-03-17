<?php
    /**
     * Project Name:    Wingman Strux - Bidirectional Map Tests
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
    use Wingman\Strux\BidirectionalMap;

    /**
     * Tests for the BidirectionalMap class, covering forward and reverse lookup,
     * bijectivity guarantee, removal operations, and predicate helpers.
     * @package Wingman\Strux\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class BidirectionalMapTest extends Test {

        // ─── Core Operations ─────────────────────────────────────────────────────

        #[Group("BidirectionalMap")]
        #[Define(
            name: "set() / get() / getKey() — Bidirectional Lookup",
            description: "set() stores a key-value pair; get() retrieves the value by key; getKey() retrieves the key by value."
        )]
        public function testBidirectionalLookup () : void {
            $map = new BidirectionalMap();
            $map->set("hello", "world");

            $this->assertTrue($map->get("hello") === "world", "get() should return 'world' for key 'hello'.");
            $this->assertTrue($map->getKey("world") === "hello", "getKey() should return 'hello' for value 'world'.");
        }

        #[Group("BidirectionalMap")]
        #[Define(
            name: "has() / hasValue() — Presence Checks",
            description: "has() checks by key; hasValue() checks by value."
        )]
        public function testHasAndHasValue () : void {
            $map = BidirectionalMap::from(["a" => 1, "b" => 2]);

            $this->assertTrue($map->has("a"), "has() should return true for key 'a'.");
            $this->assertTrue($map->hasValue(2), "hasValue() should return true for value 2.");
            $this->assertTrue(!$map->has("z"), "has() should return false for missing key 'z'.");
            $this->assertTrue(!$map->hasValue(99), "hasValue() should return false for missing value 99.");
        }

        // ─── Bijectivity ─────────────────────────────────────────────────────────

        #[Group("BidirectionalMap")]
        #[Define(
            name: "Bijectivity — Re-Mapping A Value Evicts Old Pair",
            description: "If a value is already bound to another key, setting it to a new key evicts the previous pair."
        )]
        public function testRemappingValueEvictsOldPair () : void {
            $map = new BidirectionalMap();
            $map->set("original", "shared_value");
            $map->set("replacement", "shared_value");

            $this->assertTrue(!$map->has("original"), "Old key 'original' should be evicted when value is re-mapped.");
            $this->assertTrue($map->has("replacement"), "New key 'replacement' should be present.");
            $this->assertTrue($map->getKey("shared_value") === "replacement", "getKey() should return 'replacement' after re-mapping.");
        }

        // ─── Removal ─────────────────────────────────────────────────────────────

        #[Group("BidirectionalMap")]
        #[Define(
            name: "remove() — Removes Pair By Key",
            description: "remove() deletes the pair identified by the given key; the reverse index is also cleaned up."
        )]
        public function testRemoveRemovesPairByKey () : void {
            $map = new BidirectionalMap();
            $map->set("x", "y");
            $map->remove("x");

            $this->assertTrue(!$map->has("x"), "Key 'x' should be absent after remove().");
            $this->assertTrue(!$map->hasValue("y"), "Value 'y' should no longer be indexed after remove().");
        }

        #[Group("BidirectionalMap")]
        #[Define(
            name: "removeByValue() — Removes Pair By Value",
            description: "removeByValue() deletes the pair whose value matches the argument, including the forward key."
        )]
        public function testRemoveByValueRemovesPairByValue () : void {
            $map = new BidirectionalMap();
            $map->set("alpha", "beta");
            $map->removeByValue("beta");

            $this->assertTrue(!$map->hasValue("beta"), "Value 'beta' should be absent after removeByValue().");
            $this->assertTrue(!$map->has("alpha"), "Key 'alpha' should also be removed when the value is removed.");
        }

        // ─── Enumeration ─────────────────────────────────────────────────────────

        #[Group("BidirectionalMap")]
        #[Define(
            name: "getKeys() / getSize() — Enumerate The Map",
            description: "getKeys() returns all stored keys and getSize() reflects the current pair count."
        )]
        public function testGetKeysAndGetSize () : void {
            $map = BidirectionalMap::from(["a" => 1, "b" => 2, "c" => 3]);

            $this->assertTrue(count($map->getKeys()) === 3, "getKeys() should return 3 keys.");
            $this->assertTrue($map->getSize() === 3, "getSize() should return 3.");
        }
    }
?>
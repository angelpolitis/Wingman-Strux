<?php
    /**
     * Project Name:    Wingman Strux - Hash Map Tests
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
    use Wingman\Strux\HashMap;
    use Wingman\Strux\Interfaces\MapInterface;

    /**
     * Tests for the HashMap class, covering key/value storage, iteration,
     * functional operators, advanced derivations, and interface compliance.
     * @package Wingman\Strux\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class HashMapTest extends Test {

        // ─── Core CRUD ───────────────────────────────────────────────────────────

        #[Group("HashMap")]
        #[Define(
            name: "set() / get() / has() / remove() — Core Operations",
            description: "set() stores a value, get() retrieves it, has() confirms presence, and remove() deletes it."
        )]
        public function testCoreOperations () : void {
            $map = new HashMap();
            $map->set("key", "value");

            $this->assertTrue($map->has("key"), "has() should return true after set().");
            $this->assertTrue($map->get("key") === "value", "get() should return the stored value.");

            $map->remove("key");
            $this->assertTrue(!$map->has("key"), "has() should return false after remove().");
        }

        #[Group("HashMap")]
        #[Define(
            name: "get() — Returns Null For Missing Key",
            description: "get() returns null when the key does not exist."
        )]
        public function testGetReturnNullForMissingKey () : void {
            $map = new HashMap();

            $this->assertTrue($map->get("missing") === null, "get() for a missing key should return null.");
        }

        // ─── Keys & Values ───────────────────────────────────────────────────────

        #[Group("HashMap")]
        #[Define(
            name: "getKeys() / getValues() — Return Keys And Values",
            description: "getKeys() and getValues() return the stored keys and values respectively."
        )]
        public function testGetKeysAndGetValues () : void {
            $map = HashMap::from(["a" => 1, "b" => 2]);

            $keys = $map->getKeys();
            $values = $map->getValues();

            $this->assertTrue(in_array("a", $keys) && in_array("b", $keys), "getKeys() should include 'a' and 'b'.");
            $this->assertTrue(in_array(1, $values) && in_array(2, $values), "getValues() should include 1 and 2.");
        }

        // ─── Functional ──────────────────────────────────────────────────────────

        #[Group("HashMap")]
        #[Define(
            name: "filter() — Returns Matching Key-Value Pairs",
            description: "filter() returns a new HashMap containing only entries where the predicate returns true."
        )]
        public function testFilterReturnsMatchingEntries () : void {
            $map = HashMap::from(["a" => 1, "b" => 2, "c" => 3]);
            $filtered = $map->filter(fn ($v) => $v > 1);

            $this->assertTrue($filtered->getSize() === 2, "Filtered map should have 2 entries.");
            $this->assertTrue(!$filtered->has("a"), "Filtered map should not contain 'a' (value 1).");
        }

        #[Group("HashMap")]
        #[Define(
            name: "map() — Transforms Values",
            description: "map() returns a new HashMap with values transformed by the callback."
        )]
        public function testMapTransformsValues () : void {
            $map = HashMap::from(["x" => 2, "y" => 4]);
            $doubled = $map->map(fn ($v) => $v * 2);

            $this->assertTrue($doubled->get("x") === 4, "map() should double x's value to 4.");
            $this->assertTrue($doubled->get("y") === 8, "map() should double y's value to 8.");
        }

        #[Group("HashMap")]
        #[Define(
            name: "merge() — Combines Two HashMaps",
            description: "merge() returns a new HashMap with entries from both, with the second overwriting on conflicts."
        )]
        public function testMergeCombinesTwoMaps () : void {
            $base = HashMap::from(["a" => 1, "b" => 2]);
            $extra = HashMap::from(["b" => 99, "c" => 3]);
            $merged = $base->merge($extra);

            $this->assertTrue($merged->get("a") === 1, "Non-conflicting key 'a' should remain 1.");
            $this->assertTrue($merged->get("b") === 99, "Conflicting key 'b' should be overwritten with 99.");
            $this->assertTrue($merged->get("c") === 3, "New key 'c' should be present.");
        }

        #[Group("HashMap")]
        #[Define(
            name: "flip() — Swaps Keys And Values",
            description: "flip() returns a new HashMap with keys and values swapped."
        )]
        public function testFlipSwapsKeysAndValues () : void {
            $map = HashMap::from(["hello" => "world"]);
            $flipped = $map->flip();

            $this->assertTrue($flipped->has("world"), "Flipped map should have the original value as a key.");
            $this->assertTrue($flipped->get("world") === "hello", "Flipped map's value should be the original key.");
        }

        // ─── Interface Compliance ────────────────────────────────────────────────

        #[Group("HashMap")]
        #[Define(
            name: "Implements MapInterface",
            description: "HashMap implements MapInterface."
        )]
        public function testImplementsMapInterface () : void {
            $this->assertTrue(new HashMap() instanceof MapInterface, "HashMap must implement MapInterface.");
        }
    }
?>
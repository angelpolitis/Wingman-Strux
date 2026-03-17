<?php
    /**
     * Project Name:    Wingman Strux - LRU Cache Tests
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
    use Wingman\Strux\LruCache;

    /**
     * Tests for the LruCache class, covering put/get, LRU eviction on overflow,
     * access-based promotion, evict(), isFull(), and key/value ordering.
     * @package Wingman\Strux\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class LruCacheTest extends Test {

        // ─── Core Operations ─────────────────────────────────────────────────────

        #[Group("LruCache")]
        #[Define(
            name: "put() / get() / has() — Basic Storage",
            description: "put() stores a key-value pair; get() retrieves it; has() confirms presence."
        )]
        public function testBasicStorage () : void {
            $cache = LruCache::withCap(10);
            $cache->put("name", "Wingman");

            $this->assertTrue($cache->has("name"), "has() should return true after put().");
            $this->assertTrue($cache->get("name") === "Wingman", "get() should return 'Wingman'.");
        }

        #[Group("LruCache")]
        #[Define(
            name: "get() — Returns Null For Missing Key",
            description: "get() returns null for a key that has never been stored."
        )]
        public function testGetReturnsNullForMissingKey () : void {
            $cache = LruCache::withCap(5);

            $this->assertTrue($cache->get("ghost") === null, "get() should return null for a missing key.");
        }

        // ─── Eviction Policy ─────────────────────────────────────────────────────

        #[Group("LruCache")]
        #[Define(
            name: "LRU Eviction — Removes Least Recently Used On Overflow",
            description: "When the cache is full, the next put() evicts the least recently used entry."
        )]
        public function testLruEvictsLeastRecentlyUsedOnOverflow () : void {
            $cache = LruCache::withCap(3);
            $cache->put("a", 1)->put("b", 2)->put("c", 3);

            $cache->put("d", 4);

            $this->assertTrue(!$cache->has("a"), "LRU entry 'a' should be evicted when the cache overflows with cap=3.");
            $this->assertTrue($cache->has("d"), "Newly inserted 'd' should be present.");
        }

        #[Group("LruCache")]
        #[Define(
            name: "Access-Based Promotion — Rescues Entry From Eviction",
            description: "Accessing an entry via get() promotes it to MRU, so a different entry is evicted instead."
        )]
        public function testAccessPromotesToMru () : void {
            $cache = LruCache::withCap(3);
            $cache->put("a", 1)->put("b", 2)->put("c", 3);

            $cache->get("a");
            $cache->put("d", 4);

            $this->assertTrue($cache->has("a"), "'a' should survive eviction after being accessed.");
            $this->assertTrue(!$cache->has("b"), "'b' (LRU after 'a' was promoted) should be evicted instead.");
        }

        // ─── Manual Eviction ─────────────────────────────────────────────────────

        #[Group("LruCache")]
        #[Define(
            name: "evict() — Manually Removes A Specific Entry",
            description: "evict() removes the named entry without affecting others."
        )]
        public function testEvictRemovesSpecificEntry () : void {
            $cache = LruCache::withCap(5);
            $cache->put("x", 10)->put("y", 20);
            $cache->evict("x");

            $this->assertTrue(!$cache->has("x"), "'x' should be absent after evict().");
            $this->assertTrue($cache->has("y"), "'y' should still be present.");
        }

        // ─── State ───────────────────────────────────────────────────────────────

        #[Group("LruCache")]
        #[Define(
            name: "isFull() — Returns True When At Capacity",
            description: "isFull() returns false while the cache has room and true once it reaches capacity."
        )]
        public function testIsFullReflectsCapacityState () : void {
            $cache = LruCache::withCap(2);

            $this->assertTrue(!$cache->isFull(), "Cache should not be full on construction.");

            $cache->put("a", 1)->put("b", 2);

            $this->assertTrue($cache->isFull(), "Cache should be full after 2 entries with cap=2.");
        }

        // ─── Key Order ───────────────────────────────────────────────────────────

        #[Group("LruCache")]
        #[Define(
            name: "getKeys() — Returns Keys In LRU To MRU Order",
            description: "getKeys() returns keys ordered from least recently used to most recently used."
        )]
        public function testGetKeysInLruToMruOrder () : void {
            $cache = LruCache::withCap(3);
            $cache->put("first", 1)->put("second", 2)->put("third", 3);
            $cache->get("first");

            $keys = $cache->getKeys();

            $this->assertTrue(end($keys) === "first", "Most recently used key 'first' should be last in getKeys().");
        }
    }
?>
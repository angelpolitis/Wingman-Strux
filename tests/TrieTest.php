<?php
    /**
     * Project Name:    Wingman Strux - Trie Tests
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
    use Wingman\Strux\Trie;

    /**
     * Tests for the Trie class, covering word insertion and lookup, prefix detection,
     * prefix-based retrieval, removal, and size tracking.
     * @package Wingman\Strux\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class TrieTest extends Test {

        // ─── Word Insertion & Lookup ─────────────────────────────────────────────

        #[Group("Trie")]
        #[Define(
            name: "set() / has() / get() — Word Storage And Retrieval",
            description: "set() inserts a word with an associated value; has() confirms it; get() retrieves the value."
        )]
        public function testWordStorageAndRetrieval () : void {
            $trie = new Trie();
            $trie->set("apple", 100);

            $this->assertTrue($trie->has("apple"), "has() should return true for 'apple'.");
            $this->assertTrue($trie->get("apple") === 100, "get() should return 100 for 'apple'.");
        }

        #[Group("Trie")]
        #[Define(
            name: "has() — Returns False For Unknown Words",
            description: "has() returns false for a word that has never been inserted."
        )]
        public function testHasReturnsFalseForUnknownWords () : void {
            $trie = Trie::from(["apple" => 1, "apply" => 2]);

            $this->assertTrue(!$trie->has("appetite"), "has() should return false for 'appetite' which was not inserted.");
        }

        // ─── Prefix Detection ────────────────────────────────────────────────────

        #[Group("Trie")]
        #[Define(
            name: "hasPrefix() — Detects Shared Prefix",
            description: "hasPrefix() returns true for a prefix shared by at least one stored word."
        )]
        public function testHasPrefixDetectsSharedPrefix () : void {
            $trie = Trie::from(["apple" => 1, "application" => 2, "banana" => 3]);

            $this->assertTrue($trie->hasPrefix("app"), "hasPrefix() should return true for 'app'.");
            $this->assertTrue(!$trie->hasPrefix("xyz"), "hasPrefix() should return false for 'xyz'.");
        }

        // ─── Prefix-Based Retrieval ──────────────────────────────────────────────

        #[Group("Trie")]
        #[Define(
            name: "withPrefix() — Returns Sub-Trie For A Prefix",
            description: "withPrefix() returns a new Trie containing all words that share the given prefix."
        )]
        public function testWithPrefixReturnsPrefixSubtrie () : void {
            $trie = Trie::from(["cat" => 1, "car" => 2, "card" => 3, "bus" => 4]);
            $subtrie = $trie->withPrefix("car");

            $this->assertTrue($subtrie->has("car"), "Sub-trie should contain 'car'.");
            $this->assertTrue($subtrie->has("card"), "Sub-trie should contain 'card'.");
            $this->assertTrue(!$subtrie->has("cat"), "Sub-trie should not contain 'cat'.");
            $this->assertTrue(!$subtrie->has("bus"), "Sub-trie should not contain 'bus'.");
        }

        // ─── Removal ─────────────────────────────────────────────────────────────

        #[Group("Trie")]
        #[Define(
            name: "remove() — Deletes A Word Without Affecting Others",
            description: "remove() deletes a specific word while words sharing a common prefix remain intact."
        )]
        public function testRemoveDeletesWordWithoutAffectingOthers () : void {
            $trie = Trie::from(["apple" => 1, "application" => 2]);
            $trie->remove("apple");

            $this->assertTrue(!$trie->has("apple"), "'apple' should be absent after remove().");
            $this->assertTrue($trie->has("application"), "'application' should still be present.");
        }

        // ─── Enumeration & Size ──────────────────────────────────────────────────

        #[Group("Trie")]
        #[Define(
            name: "getKeys() — Returns All Stored Words",
            description: "getKeys() returns an array of all words currently stored in the trie."
        )]
        public function testGetKeysReturnsAllStoredWords () : void {
            $trie = Trie::from(["foo" => 1, "bar" => 2, "baz" => 3]);
            $keys = $trie->getKeys();

            $this->assertTrue(in_array("foo", $keys), "'foo' should be in getKeys().");
            $this->assertTrue(in_array("bar", $keys), "'bar' should be in getKeys().");
            $this->assertTrue(in_array("baz", $keys), "'baz' should be in getKeys().");
        }

        #[Group("Trie")]
        #[Define(
            name: "getSize() — Returns Word Count",
            description: "getSize() returns the number of complete words stored in the trie."
        )]
        public function testGetSizeReturnsWordCount () : void {
            $trie = Trie::from(["one" => 1, "two" => 2, "three" => 3]);

            $this->assertTrue($trie->getSize() === 3, "getSize() should return 3 for 3 inserted words.");
        }
    }
?>
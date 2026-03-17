<?php
    /**
     * Project Name:    Wingman Strux - Enum Collection Tests
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
    use InvalidArgumentException;
    use Wingman\Argus\Attributes\Define;
    use Wingman\Argus\Attributes\Group;
    use Wingman\Argus\Test;
    use Wingman\Strux\EnumCollection;

    /**
     * A backed enum used exclusively in this test suite.
     */
    enum Suit: string {
        case Hearts = 'hearts';
        case Diamonds = 'diamonds';
        case Clubs = 'clubs';
        case Spades = 'spades';
    }

    /**
     * Tests for the EnumCollection class, covering enum-specific construction,
     * value-based operations, and type enforcement.
     * @package Wingman\Strux\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class EnumCollectionTest extends Test {

        // ─── Construction ────────────────────────────────────────────────────────

        #[Group("EnumCollection")]
        #[Define(
            name: "forEnum() — Creates Empty Collection For Given Enum",
            description: "forEnum() returns a correctly typed, empty EnumCollection bound to the specified enum class."
        )]
        public function testForEnumCreatesEmptyCollection () : void {
            $ec = EnumCollection::forEnum(Suit::class);

            $this->assertTrue($ec instanceof EnumCollection, "forEnum() should return an EnumCollection.");
            $this->assertTrue($ec->isEmpty(), "Freshly created collection should be empty.");
            $this->assertTrue($ec->getEnumClass() === Suit::class, "getEnumClass() should return the bound enum class.");
        }

        #[Group("EnumCollection")]
        #[Define(
            name: "forEnum() — Throws For Invalid Enum Class",
            description: "Passing a non-enum class name to forEnum() throws an InvalidArgumentException."
        )]
        public function testForEnumThrowsForInvalidClass () : void {
            $thrown = false;
            try {
                EnumCollection::forEnum("NotAnEnum");
            } catch (InvalidArgumentException $e) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "forEnum() with a non-enum class should throw InvalidArgumentException.");
        }

        // ─── Adding Values ───────────────────────────────────────────────────────

        #[Group("EnumCollection")]
        #[Define(
            name: "addValue() — Adds Cases By Backing Value",
            description: "addValue() resolves a backing value to its enum case and adds it to the collection."
        )]
        public function testAddValueAddsCasesByBackingValue () : void {
            $ec = EnumCollection::forEnum(Suit::class);
            $ec->addValue("hearts")->addValue("spades");

            $this->assertTrue($ec->getSize() === 2, "Collection should hold 2 cases after two addValue() calls.");
        }

        #[Group("EnumCollection")]
        #[Define(
            name: "hasValue() — Returns True For Present Backing Value",
            description: "hasValue() returns true when the collection contains a case with the given backing value."
        )]
        public function testHasValueReturnsTrueForPresentValue () : void {
            $ec = EnumCollection::forEnum(Suit::class);
            $ec->addValue("diamonds");

            $this->assertTrue($ec->hasValue("diamonds"), "hasValue() should return true for 'diamonds'.");
            $this->assertTrue(!$ec->hasValue("clubs"), "hasValue() should return false for 'clubs'.");
        }

        // ─── Inspection ──────────────────────────────────────────────────────────

        #[Group("EnumCollection")]
        #[Define(
            name: "names() / values() — Return Case Names And Backing Values",
            description: "names() returns case names and values() returns raw backing values in collection order."
        )]
        public function testNamesAndValues () : void {
            $ec = EnumCollection::forEnum(Suit::class);
            $ec->addValue("clubs")->addValue("hearts");

            $this->assertTrue(in_array("Clubs", $ec->getNames()), "getNames() should include 'Clubs'.");
            $this->assertTrue(in_array("clubs", $ec->getValues()), "getValues() should include 'clubs'.");
        }

        // ─── Type Enforcement ────────────────────────────────────────────────────

        #[Group("EnumCollection")]
        #[Define(
            name: "Rejects Non-Enum Cases Via add()",
            description: "Attempting to add() a non-Suit value throws an InvalidArgumentException."
        )]
        public function testRejectsNonEnumCases () : void {
            $ec = EnumCollection::forEnum(Suit::class);
            $thrown = false;

            try {
                $ec->add("not a suit");
            } catch (InvalidArgumentException $e) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "Adding a non-enum value should throw InvalidArgumentException.");
        }
    }
?>
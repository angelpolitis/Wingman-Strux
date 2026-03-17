<?php
    /**
     * Project Name:    Wingman Strux - Typed Sorted List Tests
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
    use Wingman\Strux\TypedSortedList;

    /**
     * Tests for the TypedSortedList abstract class, exercised via an anonymous concrete
     * subclass with a declared type constraint.
     * @package Wingman\Strux\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class TypedSortedListTest extends Test {

        #[Group("TypedSortedList")]
        #[Define(
            name: "Accepts Items Matching Declared Type",
            description: "A TypedSortedList subclass accepts items whose type matches the declared \$type."
        )]
        public function testAcceptsItemsMatchingDeclaredType () : void {
            $list = new class extends TypedSortedList {
                protected ?string $type = 'int';
            };
            $list->add(3, 1, 2);

            $this->assertTrue($list->getSize() === 3, "Sorted list should hold 3 integers.");
            $this->assertTrue($list->getFirst() === 1, "getFirst() should return the smallest integer (1).");
        }

        #[Group("TypedSortedList")]
        #[Define(
            name: "Rejects Items Of Wrong Type",
            description: "Adding an item of the wrong type throws an InvalidArgumentException."
        )]
        public function testRejectsItemsOfWrongType () : void {
            $list = new class extends TypedSortedList {
                protected ?string $type = 'int';
            };

            $thrown = false;
            try {
                $list->add("not an int");
            } catch (InvalidArgumentException $e) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "Adding a string to an int-typed sorted list should throw InvalidArgumentException.");
        }
    }
?>
<?php
    /**
     * Project Name:    Wingman Strux - Typed Set Tests
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
    use Wingman\Strux\TypedSet;

    /**
     * Tests for the TypedSet abstract class, exercised via an anonymous concrete
     * subclass with a declared type constraint.
     * @package Wingman\Strux\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class TypedSetTest extends Test {

        #[Group("TypedSet")]
        #[Define(
            name: "Accepts Items Matching Declared Type",
            description: "A TypedSet subclass accepts items whose type matches the declared \$type property."
        )]
        public function testAcceptsItemsMatchingDeclaredType () : void {
            $set = new class extends TypedSet {
                protected ?string $type = 'string';
            };
            $set->add("foo")->add("bar");

            $this->assertTrue($set->getSize() === 2, "Typed set should hold 2 strings.");
        }

        #[Group("TypedSet")]
        #[Define(
            name: "Rejects Items Of Wrong Type",
            description: "Adding an item of the wrong type throws an InvalidArgumentException."
        )]
        public function testRejectsItemsOfWrongType () : void {
            $set = new class extends TypedSet {
                protected ?string $type = 'string';
            };

            $thrown = false;
            try {
                $set->add(42);
            } catch (InvalidArgumentException $e) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "Adding an integer to a string-typed set should throw InvalidArgumentException.");
        }
    }
?>
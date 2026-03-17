<?php
    /**
     * Project Name:    Wingman Strux - Typed Collection Tests
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
    use Wingman\Strux\TypedCollection;

    /**
     * Tests for the TypedCollection abstract class.
     * All scenarios are exercised via an anonymous concrete subclass that declares a
     * specific type constraint, isolating only the type-enforcement layer.
     * @package Wingman\Strux\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class TypedCollectionTest extends Test {

        #[Group("TypedCollection")]
        #[Define(
            name: "Accepts Items Matching Declared Type",
            description: "A concrete TypedCollection subclass accepts items whose type matches the declared \$type property."
        )]
        public function testAcceptsItemsMatchingDeclaredType () : void {
            $col = new class([1, 2, 3]) extends TypedCollection {
                protected ?string $type = 'int';
            };

            $this->assertTrue($col->getSize() === 3, "Collection should contain 3 integers.");
        }

        #[Group("TypedCollection")]
        #[Define(
            name: "Rejects Items Of Wrong Type",
            description: "Adding an item of the wrong type throws an InvalidArgumentException."
        )]
        public function testRejectsItemsOfWrongType () : void {
            $col = new class extends TypedCollection {
                protected ?string $type = 'int';
            };

            $thrown = false;
            try {
                $col->add("not an integer");
            } catch (InvalidArgumentException $e) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "Adding a string to an int-typed collection should throw InvalidArgumentException.");
        }

        #[Group("TypedCollection")]
        #[Define(
            name: "Rejects Wrong Type At Construction",
            description: "Passing items of the wrong type to the constructor throws an InvalidArgumentException."
        )]
        public function testRejectsWrongTypeAtConstruction () : void {
            $thrown = false;
            try {
                new class(["a", "b"]) extends TypedCollection {
                    protected ?string $type = 'int';
                };
            } catch (InvalidArgumentException $e) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "Constructing with mismatched types should throw InvalidArgumentException.");
        }

        #[Group("TypedCollection")]
        #[Define(
            name: "Enforces Object Type Constraint",
            description: "A TypedCollection typed to a class rejects instances of a different class."
        )]
        public function testEnforcesObjectTypeConstraint () : void {
            $col = new class extends TypedCollection {
                protected ?string $type = \stdClass::class;
            };

            $thrown = false;
            try {
                $col->add(new \DateTime());
            } catch (InvalidArgumentException $e) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "Adding a DateTime to a stdClass-typed collection should throw InvalidArgumentException.");
        }
    }
?>
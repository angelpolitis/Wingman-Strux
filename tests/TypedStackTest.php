<?php
    /**
     * Project Name:    Wingman Strux - Typed Stack Tests
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
    use Wingman\Strux\TypedStack;

    /**
     * Tests for the TypedStack abstract class, exercised via an anonymous concrete
     * subclass with a declared type constraint.
     * @package Wingman\Strux\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class TypedStackTest extends Test {

        #[Group("TypedStack")]
        #[Define(
            name: "Accepts Items Matching Declared Type",
            description: "A TypedStack subclass accepts items whose type matches the declared \$type property."
        )]
        public function testAcceptsItemsMatchingDeclaredType () : void {
            $stack = new class extends TypedStack {
                protected ?string $type = 'string';
            };
            $stack->push("hello", "world");

            $this->assertTrue($stack->getSize() === 2, "Stack should hold 2 strings.");
        }

        #[Group("TypedStack")]
        #[Define(
            name: "Rejects Items Of Wrong Type",
            description: "Pushing an item of the wrong type throws an InvalidArgumentException."
        )]
        public function testRejectsItemsOfWrongType () : void {
            $stack = new class extends TypedStack {
                protected ?string $type = 'string';
            };

            $thrown = false;
            try {
                $stack->push(42);
            } catch (InvalidArgumentException $e) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "Pushing an integer onto a string-typed stack should throw InvalidArgumentException.");
        }

        #[Group("TypedStack")]
        #[Define(
            name: "Enforces Type at Construction",
            description: "Passing items of the wrong type to the constructor throws an InvalidArgumentException."
        )]
        public function testEnforcesTypeAtConstruction () : void {
            $thrown = false;
            try {
                new class([1, 2, 3]) extends TypedStack {
                    protected ?string $type = 'string';
                };
            } catch (InvalidArgumentException $e) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "Constructing with mismatched integer items should throw InvalidArgumentException.");
        }
    }
?>
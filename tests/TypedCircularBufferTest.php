<?php
    /**
     * Project Name:    Wingman Strux - Typed Circular Buffer Tests
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
    use Wingman\Strux\TypedCircularBuffer;

    /**
     * Tests for the TypedCircularBuffer abstract class, exercised via an anonymous
     * concrete subclass with a declared type constraint.
     * @package Wingman\Strux\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class TypedCircularBufferTest extends Test {

        #[Group("TypedCircularBuffer")]
        #[Define(
            name: "Accepts Items Matching Declared Type",
            description: "A TypedCircularBuffer subclass writes items whose type matches the declared \$type."
        )]
        public function testAcceptsItemsMatchingDeclaredType () : void {
            $buffer = new class(5) extends TypedCircularBuffer {
                protected ?string $type = 'string';
            };
            $buffer->write("alpha")->write("beta");

            $this->assertTrue($buffer->getSize() === 2, "Buffer should hold 2 strings.");
        }

        #[Group("TypedCircularBuffer")]
        #[Define(
            name: "Rejects Items Of Wrong Type",
            description: "Writing an item of the wrong type throws an InvalidArgumentException."
        )]
        public function testRejectsItemsOfWrongType () : void {
            $buffer = new class(5) extends TypedCircularBuffer {
                protected ?string $type = 'string';
            };

            $thrown = false;
            try {
                $buffer->write(42);
            } catch (InvalidArgumentException $e) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "Writing an integer to a string-typed buffer should throw InvalidArgumentException.");
        }
    }
?>
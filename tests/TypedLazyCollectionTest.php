<?php
    /**
     * Project Name:    Wingman Strux - Typed Lazy Collection Tests
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
    use Wingman\Strux\TypedLazyCollection;

    /**
     * Tests for the TypedLazyCollection abstract class.
     *
     * Type enforcement fires at output time (during iteration), not at input time,
     * because the source is an opaque callable.
     * @package Wingman\Strux\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class TypedLazyCollectionTest extends Test {

        #[Group("TypedLazyCollection")]
        #[Define(
            name: "Passes Iteration When Types Match",
            description: "A TypedLazyCollection subclass iterates without exception when all yielded items match the declared type."
        )]
        public function testPassesIterationWhenTypesMatch () : void {
            $lazy = new class([1, 2, 3]) extends TypedLazyCollection {
                protected string $type = 'int';
            };

            $thrown = false;
            try {
                $lazy->toArray();
            } catch (InvalidArgumentException $e) {
                $thrown = false;
            }

            $this->assertTrue(!$thrown, "Iteration should not throw when all items are integers.");
            $this->assertTrue($lazy->getSize() === 3, "Sequence should contain 3 items.");
        }

        #[Group("TypedLazyCollection")]
        #[Define(
            name: "Throws On Iteration When Type Mismatches",
            description: "toArray() throws an InvalidArgumentException when a yielded item does not match the declared type."
        )]
        public function testThrowsOnIterationWhenTypeMismatches () : void {
            $lazy = new class(["string", "values"]) extends TypedLazyCollection {
                protected string $type = 'int';
            };

            $thrown = false;
            try {
                $lazy->toArray();
            } catch (InvalidArgumentException $e) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "Iterating string items from an int-typed sequence should throw InvalidArgumentException.");
        }
    }
?>
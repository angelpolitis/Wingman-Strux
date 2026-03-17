<?php
    /**
     * Project Name:    Wingman Strux - Typed Queue Tests
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
    use Wingman\Strux\TypedQueue;

    /**
     * Tests for the TypedQueue abstract class, exercised via an anonymous concrete
     * subclass with a declared type constraint.
     * @package Wingman\Strux\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class TypedQueueTest extends Test {

        #[Group("TypedQueue")]
        #[Define(
            name: "Accepts Items Matching Declared Type",
            description: "A TypedQueue subclass accepts items whose type matches the declared \$type property."
        )]
        public function testAcceptsItemsMatchingDeclaredType () : void {
            $queue = new class extends TypedQueue {
                protected ?string $type = 'float';
            };
            $queue->enqueue(1.0, 2.5);

            $this->assertTrue($queue->getSize() === 2, "Queue should hold 2 floats.");
        }

        #[Group("TypedQueue")]
        #[Define(
            name: "Rejects Items Of Wrong Type",
            description: "Enqueueing an item of the wrong type throws an InvalidArgumentException."
        )]
        public function testRejectsItemsOfWrongType () : void {
            $queue = new class extends TypedQueue {
                protected ?string $type = 'float';
            };

            $thrown = false;
            try {
                $queue->enqueue("not a float");
            } catch (InvalidArgumentException $e) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "Enqueueing a string onto a float-typed queue should throw InvalidArgumentException.");
        }
    }
?>
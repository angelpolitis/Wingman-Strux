<?php
    /**
     * Project Name:    Wingman Strux - Typed Priority Queue Tests
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
    use Wingman\Strux\TypedPriorityQueue;

    /**
     * Tests for the TypedPriorityQueue abstract class, exercised via an anonymous
     * concrete subclass with a declared type constraint.
     * @package Wingman\Strux\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class TypedPriorityQueueTest extends Test {

        #[Group("TypedPriorityQueue")]
        #[Define(
            name: "Accepts Items Matching Declared Type",
            description: "A TypedPriorityQueue subclass accepts items whose type matches the declared \$type property."
        )]
        public function testAcceptsItemsMatchingDeclaredType () : void {
            $pq = new class extends TypedPriorityQueue {
                protected ?string $type = 'int';
            };
            $pq->enqueue(10, 1)->enqueue(20, 2);

            $this->assertTrue($pq->getSize() === 2, "Queue should hold 2 integers.");
        }

        #[Group("TypedPriorityQueue")]
        #[Define(
            name: "Rejects Items Of Wrong Type",
            description: "Enqueueing an item of the wrong type throws an InvalidArgumentException."
        )]
        public function testRejectsItemsOfWrongType () : void {
            $pq = new class extends TypedPriorityQueue {
                protected ?string $type = 'int';
            };

            $thrown = false;
            try {
                $pq->enqueue("not an int", 1);
            } catch (InvalidArgumentException $e) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "Enqueueing a string onto an int-typed priority queue should throw InvalidArgumentException.");
        }
    }
?>
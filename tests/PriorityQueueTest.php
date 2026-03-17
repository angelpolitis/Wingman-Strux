<?php
    /**
     * Project Name:    Wingman Strux - Priority Queue Tests
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
    use Wingman\Strux\Interfaces\SequenceInterface;
    use Wingman\Strux\PriorityQueue;

    /**
     * Tests for the PriorityQueue class, covering priority ordering, stable ordering
     * for equal priorities, peek semantics, and interface compliance.
     * @package Wingman\Strux\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class PriorityQueueTest extends Test {

        // ─── Priority Ordering ───────────────────────────────────────────────────

        #[Group("PriorityQueue")]
        #[Define(
            name: "Higher Priority Extracted First",
            description: "Items with higher numeric priority are dequeued before items with lower priority."
        )]
        public function testHigherPriorityExtractedFirst () : void {
            $pq = new PriorityQueue();
            $pq->enqueue("low", 1)->enqueue("high", 10)->enqueue("medium", 5);

            $this->assertTrue($pq->dequeue() === "high", "Highest-priority item should be dequeued first.");
            $this->assertTrue($pq->dequeue() === "medium", "Medium-priority item should be dequeued second.");
            $this->assertTrue($pq->dequeue() === "low", "Lowest-priority item should be dequeued last.");
        }

        #[Group("PriorityQueue")]
        #[Define(
            name: "Equal Priority — Stable FIFO Ordering",
            description: "Items enqueued with the same priority are dequeued in the original insertion order."
        )]
        public function testEqualPriorityFollowsFifoOrder () : void {
            $pq = new PriorityQueue();
            $pq->enqueue("first", 5)->enqueue("second", 5)->enqueue("third", 5);

            $this->assertTrue($pq->dequeue() === "first", "First-inserted item should be dequeued first among equals.");
            $this->assertTrue($pq->dequeue() === "second", "Second-inserted item should follow.");
            $this->assertTrue($pq->dequeue() === "third", "Third-inserted item should be last.");
        }

        // ─── Peek ────────────────────────────────────────────────────────────────

        #[Group("PriorityQueue")]
        #[Define(
            name: "peek() — Returns Highest Priority Without Consuming",
            description: "peek() returns the highest-priority item without removing it."
        )]
        public function testPeekReturnsHighestPriorityWithoutConsuming () : void {
            $pq = new PriorityQueue();
            $pq->enqueue("a", 1)->enqueue("b", 99);

            $this->assertTrue($pq->peek() === "b", "peek() should return the highest-priority item 'b'.");
            $this->assertTrue($pq->getSize() === 2, "Queue size should remain 2 after peek().");
        }

        // ─── State ───────────────────────────────────────────────────────────────

        #[Group("PriorityQueue")]
        #[Define(
            name: "dequeue() — Returns Null On Empty Queue",
            description: "dequeue() on an empty priority queue returns null."
        )]
        public function testDequeueReturnsNullOnEmptyQueue () : void {
            $pq = new PriorityQueue();

            $this->assertTrue($pq->dequeue() === null, "dequeue() on an empty queue should return null.");
        }

        #[Group("PriorityQueue")]
        #[Define(
            name: "getSize() / isEmpty() Reflect State After Operations",
            description: "getSize() and isEmpty() correctly reflect the queue state after enqueue and dequeue."
        )]
        public function testSizeAndEmptyReflectState () : void {
            $pq = new PriorityQueue();

            $this->assertTrue($pq->isEmpty(), "New priority queue should be empty.");

            $pq->enqueue("x", 1);
            $this->assertTrue($pq->getSize() === 1, "Size should be 1 after one enqueue.");
            $this->assertTrue(!$pq->isEmpty(), "Queue should not be empty after enqueue.");

            $pq->dequeue();
            $this->assertTrue($pq->isEmpty(), "Queue should be empty after dequeuing the last item.");
        }

        // ─── Interface Compliance ────────────────────────────────────────────────

        #[Group("PriorityQueue")]
        #[Define(
            name: "Implements SequenceInterface",
            description: "PriorityQueue implements SequenceInterface."
        )]
        public function testImplementsSequenceInterface () : void {
            $this->assertTrue(new PriorityQueue() instanceof SequenceInterface, "PriorityQueue must implement SequenceInterface.");
        }
    }
?>
<?php
    /**
     * Project Name:    Wingman Strux - Queue Tests
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
    use LogicException;
    use Wingman\Argus\Attributes\Define;
    use Wingman\Argus\Attributes\Group;
    use Wingman\Argus\Test;
    use Wingman\Strux\Interfaces\SequenceInterface;
    use Wingman\Strux\Queue;

    /**
     * Tests for the Queue class, covering FIFO semantics, cap enforcement, freeze
     * semantics, functional iteration, and interface compliance.
     * @package Wingman\Strux\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class QueueTest extends Test {

        // ─── FIFO Semantics ──────────────────────────────────────────────────────

        #[Group("Queue")]
        #[Define(
            name: "enqueue() / dequeue() — FIFO Order",
            description: "Items are dequeued in first-in, first-out order."
        )]
        public function testEnqueueAndDequeueFollowFifoOrder () : void {
            $queue = new Queue();
            $queue->enqueue(1)->enqueue(2)->enqueue(3);

            $this->assertTrue($queue->dequeue() === 1, "First dequeue should return 1.");
            $this->assertTrue($queue->dequeue() === 2, "Second dequeue should return 2.");
            $this->assertTrue($queue->dequeue() === 3, "Third dequeue should return 3.");
        }

        #[Group("Queue")]
        #[Define(
            name: "peek() — Returns Front Without Consuming",
            description: "peek() returns the front item without removing it from the queue."
        )]
        public function testPeekReturnsFrontWithoutConsuming () : void {
            $queue = Queue::from(["first", "second"]);

            $this->assertTrue($queue->peek() === "first", "peek() should return 'first'.");
            $this->assertTrue($queue->getSize() === 2, "Queue size should remain 2 after peek().");
        }

        #[Group("Queue")]
        #[Define(
            name: "dequeue() — Returns Null On Empty Queue",
            description: "dequeue() on an empty queue returns null rather than throwing."
        )]
        public function testDequeueReturnsNullOnEmptyQueue () : void {
            $queue = new Queue();

            $this->assertTrue($queue->dequeue() === null, "dequeue() on an empty queue should return null.");
        }

        // ─── Cap & Freeze ────────────────────────────────────────────────────────

        #[Group("Queue")]
        #[Define(
            name: "Cap Enforcement — Throws On Overflow",
            description: "Enqueueing beyond the declared capacity throws a LogicException."
        )]
        public function testCapEnforcementThrowsOnOverflow () : void {
            $queue = Queue::withCap(2);
            $queue->enqueue("a", "b");

            $thrown = false;
            try {
                $queue->enqueue("c");
            } catch (LogicException $e) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "Exceeding the cap should throw LogicException.");
        }

        #[Group("Queue")]
        #[Define(
            name: "freeze() — Prevents enqueue()",
            description: "Calling enqueue() on a frozen queue throws a LogicException."
        )]
        public function testFreezePreventsMutation () : void {
            $queue = Queue::from([1])->freeze();

            $thrown = false;
            try {
                $queue->enqueue(2);
            } catch (LogicException $e) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "Enqueueing onto a frozen queue should throw LogicException.");
        }

        // ─── Functional ──────────────────────────────────────────────────────────

        #[Group("Queue")]
        #[Define(
            name: "every() / some() / none() — Predicate Methods",
            description: "every(), some(), and none() return the correct boolean for their respective semantics."
        )]
        public function testPredicateMethods () : void {
            $queue = Queue::from([2, 4, 6]);

            $this->assertTrue($queue->every(fn ($n) => $n % 2 === 0), "every() should be true for all evens.");
            $this->assertTrue($queue->some(fn ($n) => $n === 4), "some() should be true since 4 is present.");
            $this->assertTrue($queue->none(fn ($n) => $n > 10), "none() should be true since no item > 10.");
        }

        #[Group("Queue")]
        #[Define(
            name: "reduce() — Accumulates Items",
            description: "reduce() folds all items into a single value."
        )]
        public function testReduceAccumulatesItems () : void {
            $queue = Queue::from([5, 10, 15]);
            $sum = $queue->reduce(fn ($carry, $n) => $carry + $n, 0);

            $this->assertTrue($sum === 30, "reduce() should sum to 30.");
        }

        // ─── Interface Compliance ────────────────────────────────────────────────

        #[Group("Queue")]
        #[Define(
            name: "Implements SequenceInterface",
            description: "Queue implements SequenceInterface."
        )]
        public function testImplementsSequenceInterface () : void {
            $this->assertTrue(new Queue() instanceof SequenceInterface, "Queue must implement SequenceInterface.");
        }
    }
?>
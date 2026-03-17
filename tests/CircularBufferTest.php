<?php
    /**
     * Project Name:    Wingman Strux - Circular Buffer Tests
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
    use Wingman\Strux\CircularBuffer;
    use Wingman\Strux\Interfaces\SequenceInterface;

    /**
     * Tests for the CircularBuffer class, covering FIFO semantics, capacity, overwrite-on-full
     * behaviour, flush, peek, and interface compliance.
     * @package Wingman\Strux\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class CircularBufferTest extends Test {

        // ─── FIFO Semantics ──────────────────────────────────────────────────────

        #[Group("CircularBuffer")]
        #[Define(
            name: "write() / read() — FIFO Order",
            description: "Items are read in the same order they were written (first-in, first-out)."
        )]
        public function testWriteAndReadFollowFifoOrder () : void {
            $buffer = new CircularBuffer(5);
            $buffer->write(1)->write(2)->write(3);

            $this->assertTrue($buffer->read() === 1, "First read should return 1.");
            $this->assertTrue($buffer->read() === 2, "Second read should return 2.");
            $this->assertTrue($buffer->read() === 3, "Third read should return 3.");
        }

        #[Group("CircularBuffer")]
        #[Define(
            name: "peek() — Returns Oldest Item Without Consuming",
            description: "peek() returns the oldest item in the buffer without removing it."
        )]
        public function testPeekReturnsOldestItemWithoutConsuming () : void {
            $buffer = new CircularBuffer(5);
            $buffer->write("a")->write("b");

            $this->assertTrue($buffer->peek() === "a", "peek() should return 'a', the oldest item.");
            $this->assertTrue($buffer->getSize() === 2, "Size should remain 2 after peek().");
        }

        // ─── Overwrite Behaviour ─────────────────────────────────────────────────

        #[Group("CircularBuffer")]
        #[Define(
            name: "Overwrite-On-Full — Discards The Oldest Item",
            description: "When the buffer is at capacity, writing a new item silently overwrites the oldest."
        )]
        public function testOverwriteOnFullDiscardsOldestItem () : void {
            $buffer = new CircularBuffer(3);
            $buffer->write("first")->write("second")->write("third");
            $buffer->write("fourth");

            $this->assertTrue($buffer->isFull(), "Buffer should still be full after overwrite.");
            $this->assertTrue($buffer->read() === "second", "After overwrite, oldest should be 'second'.");
        }

        // ─── State Helpers ───────────────────────────────────────────────────────

        #[Group("CircularBuffer")]
        #[Define(
            name: "isFull() / isEmpty() — Report Capacity State",
            description: "isEmpty() returns true on an empty buffer; isFull() returns true once capacity is reached."
        )]
        public function testIsFullAndIsEmpty () : void {
            $buffer = new CircularBuffer(2);

            $this->assertTrue($buffer->isEmpty(), "Buffer should be empty on construction.");

            $buffer->write("x")->write("y");

            $this->assertTrue($buffer->isFull(), "Buffer should be full after writing 2 items into cap=2 buffer.");
        }

        #[Group("CircularBuffer")]
        #[Define(
            name: "flush() — Clears All Items",
            description: "flush() removes all items from the buffer, leaving it empty."
        )]
        public function testFlushClearsAllItems () : void {
            $buffer = new CircularBuffer(5);
            $buffer->write(1)->write(2)->write(3)->flush();

            $this->assertTrue($buffer->isEmpty(), "Buffer should be empty after flush().");
            $this->assertTrue($buffer->getSize() === 0, "Size should be 0 after flush().");
        }

        // ─── Capacity ────────────────────────────────────────────────────────────

        #[Group("CircularBuffer")]
        #[Define(
            name: "getCap() — Returns Declared Capacity",
            description: "getCap() returns the capacity value specified at construction time."
        )]
        public function testGetCapReturnsDeclaredCapacity () : void {
            $buffer = new CircularBuffer(8);

            $this->assertTrue($buffer->getCap() === 8, "getCap() should return 8.");
        }

        // ─── Interface Compliance ────────────────────────────────────────────────

        #[Group("CircularBuffer")]
        #[Define(
            name: "Implements SequenceInterface",
            description: "CircularBuffer implements SequenceInterface."
        )]
        public function testImplementsSequenceInterface () : void {
            $this->assertTrue(new CircularBuffer(1) instanceof SequenceInterface, "CircularBuffer must implement SequenceInterface.");
        }
    }
?>
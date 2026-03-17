<?php
    /**
     * Project Name:    Wingman Strux - Stack Tests
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
    use Wingman\Strux\Stack;

    /**
     * Tests for the Stack class, covering LIFO semantics, cap enforcement, freeze
     * semantics, functional iteration, and interface compliance.
     * @package Wingman\Strux\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class StackTest extends Test {

        // ─── LIFO Semantics ──────────────────────────────────────────────────────

        #[Group("Stack")]
        #[Define(
            name: "push() / pop() — LIFO Order",
            description: "Items are popped in last-in, first-out order."
        )]
        public function testPushAndPopFollowLifoOrder () : void {
            $stack = new Stack();
            $stack->push(1)->push(2)->push(3);

            $this->assertTrue($stack->pop() === 3, "First pop should return 3.");
            $this->assertTrue($stack->pop() === 2, "Second pop should return 2.");
            $this->assertTrue($stack->pop() === 1, "Third pop should return 1.");
        }

        #[Group("Stack")]
        #[Define(
            name: "peek() — Returns Top Without Consuming",
            description: "peek() returns the top item without removing it from the stack."
        )]
        public function testPeekReturnsTopWithoutConsuming () : void {
            $stack = new Stack();
            $stack->push("a")->push("b");

            $this->assertTrue($stack->peek() === "b", "peek() should return 'b'.");
            $this->assertTrue($stack->getSize() === 2, "Stack size should remain 2 after peek().");
        }

        #[Group("Stack")]
        #[Define(
            name: "pop() — Returns Null On Empty Stack",
            description: "pop() on an empty stack returns null rather than throwing."
        )]
        public function testPopReturnsNullOnEmptyStack () : void {
            $stack = new Stack();

            $this->assertTrue($stack->pop() === null, "pop() on an empty stack should return null.");
        }

        // ─── Cap & Freeze ────────────────────────────────────────────────────────

        #[Group("Stack")]
        #[Define(
            name: "Cap Enforcement — Throws On Overflow",
            description: "Pushing beyond the declared capacity throws a LogicException."
        )]
        public function testCapEnforcementThrowsOnOverflow () : void {
            $stack = Stack::withCap(2);
            $stack->push(1, 2);

            $thrown = false;
            try {
                $stack->push(3);
            } catch (LogicException $e) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "Exceeding the cap should throw LogicException.");
        }

        #[Group("Stack")]
        #[Define(
            name: "freeze() — Prevents push()",
            description: "Calling push() on a frozen stack throws a LogicException."
        )]
        public function testFreezePreventsPush () : void {
            $stack = Stack::from([1, 2])->freeze();

            $thrown = false;
            try {
                $stack->push(3);
            } catch (LogicException $e) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "Pushing onto a frozen stack should throw LogicException.");
        }

        // ─── Functional ──────────────────────────────────────────────────────────

        #[Group("Stack")]
        #[Define(
            name: "each() — Iterates Top To Bottom",
            description: "each() visits items from the top (most recently pushed) to the bottom."
        )]
        public function testEachIteratesTopToBottom () : void {
            $stack = Stack::from([1, 2, 3]);
            $collected = [];
            $stack->each(function ($item) use (&$collected) { $collected[] = $item; });

            $this->assertTrue($collected[0] === 3, "First visited item should be 3 (top).");
            $this->assertTrue($collected[2] === 1, "Last visited item should be 1 (bottom).");
        }

        #[Group("Stack")]
        #[Define(
            name: "find() — Returns First Matching Item",
            description: "find() returns the value of the first item satisfying the predicate."
        )]
        public function testFindReturnsFirstMatchingItem () : void {
            $stack = Stack::from([1, 3, 5, 4, 2]);
            $result = $stack->find(fn ($n) => $n % 2 === 0);

            $this->assertTrue($result !== null, "find() should find an even number.");
        }

        #[Group("Stack")]
        #[Define(
            name: "reduce() — Accumulates Items",
            description: "reduce() folds all items into a single value."
        )]
        public function testReduceAccumulatesItems () : void {
            $stack = Stack::from([1, 2, 3, 4]);
            $sum = $stack->reduce(fn ($carry, $n) => $carry + $n, 0);

            $this->assertTrue($sum === 10, "reduce() should sum to 10.");
        }

        // ─── Interface Compliance ────────────────────────────────────────────────

        #[Group("Stack")]
        #[Define(
            name: "Implements SequenceInterface",
            description: "Stack implements SequenceInterface."
        )]
        public function testImplementsSequenceInterface () : void {
            $this->assertTrue(new Stack() instanceof SequenceInterface, "Stack must implement SequenceInterface.");
        }
    }
?>
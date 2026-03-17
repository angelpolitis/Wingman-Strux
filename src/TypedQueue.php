<?php
    /**
     * Project Name:    Wingman Strux - Typed Queue
     * Created by:      Angel Politis
     * Creation Date:   Mar 16 2026
     * Last Modified:   Mar 17 2026
     *
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Strux namespace.
    namespace Wingman\Strux;

    # Import the following classes to the current scope.
    use LogicException;

    /**
     * Represents a FIFO queue with value type enforcement.
     *
     * Concrete subclasses must declare a typed {@see Queue::$type} class property to
     * activate enforcement. Direct instantiation of this class is intentionally
     * disallowed — use a subclass that specifies the target type.
     *
     * The type constraint may be any PHP primitive alias, a fully-qualified class or
     * interface name, or a Verix schema expression (requires the Wingman Verix package).
     *
     * @package Wingman\Strux
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     * @template T
     * @extends Queue<T>
     */
    abstract class TypedQueue extends Queue {
        /**
         * Creates a new typed queue.
         * The type is not accepted as a constructor parameter — it must be declared as a
         * class property in the concrete subclass.
         * @param array<T> $items The initial items to enqueue (front to back).
         * @param int|null $cap The maximum number of items the queue can hold.
         * @param bool $frozen Whether the queue should be immediately frozen.
         */
        public function __construct (array $items = [], ?int $cap = null, bool $frozen = false) {
            parent::__construct($items, null, $cap, $frozen);
        }

        /**
         * Creates a new typed queue from an array of initial items.
         * The type parameter is intentionally omitted — it is declared on the subclass.
         * @param array<T> $items The initial items.
         * @param string|null $type Ignored; exists only for signature compatibility.
         * @param int|null $cap The capacity limit.
         * @param bool $frozen Whether to freeze the queue immediately.
         * @return static The created queue.
         */
        public static function from (array $items, ?string $type = null, ?int $cap = null, bool $frozen = false) : static {
            return new static($items, $cap, $frozen);
        }

        /**
         * Not supported on TypedQueue; the type is declared as a class property on the subclass.
         * @throws LogicException Always.
         */
        public static function withType (string $type) : static {
            throw new LogicException(
                "withType() is not supported on TypedQueue subclasses. " .
                "Declare the \$type property directly on the subclass instead."
            );
        }
    }
?>
<?php
    /**
     * Project Name:    Wingman Strux - Typed Priority Queue
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
     * Represents a priority-ordered queue with value type enforcement.
     *
     * Concrete subclasses must declare a typed {@see PriorityQueue::$type} class property
     * to activate enforcement. Direct instantiation of this class is intentionally
     * disallowed — use a subclass that specifies the target type.
     *
     * The type constraint may be any PHP primitive alias, a fully-qualified class or
     * interface name, or a Verix schema expression (requires the Wingman Verix package).
     *
     * @package Wingman\Strux
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     * @template T
     * @extends PriorityQueue<T>
     */
    abstract class TypedPriorityQueue extends PriorityQueue {
        /**
         * Creates a new typed priority queue, pre-loading it with the given item-priority pairs.
         * The type is not accepted as a constructor parameter — it must be declared as a
         * class property in the concrete subclass.
         * @param array<int, mixed> $items An associative array of priority => item to pre-load.
         */
        public function __construct (array $items = []) {
            parent::__construct(null);

            foreach ($items as $priority => $item) {
                $this->enqueue($item, (int) $priority);
            }
        }

        /**
         * Creates a new typed priority queue pre-loaded with the given item-priority pairs.
         * The type parameter is intentionally omitted — it is declared on the subclass.
         * @param array<int, mixed> $items An associative array of priority => item.
         * @param string|null $type Ignored; exists only for signature compatibility.
         * @return static The created queue.
         */
        public static function from (array $items, ?string $type = null) : static {
            return new static($items);
        }

        /**
         * Not supported on TypedPriorityQueue; the type is declared as a class property on the subclass.
         * @throws LogicException Always.
         */
        public static function withType (string $type) : static {
            throw new LogicException(static::class . " has a fixed item type and does not support withType().");
        }
    }
?>
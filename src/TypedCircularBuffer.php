<?php
    /**
     * Project Name:    Wingman Strux - Typed Circular Buffer
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
     * Represents a fixed-capacity ring buffer with value type enforcement.
     *
     * Concrete subclasses must declare a typed {@see CircularBuffer::$type} class property
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
     * @extends CircularBuffer<T>
     */
    abstract class TypedCircularBuffer extends CircularBuffer {
        /**
         * Creates a new typed circular buffer with the given fixed capacity.
         * The type is not accepted as a constructor parameter — it must be declared as a
         * class property in the concrete subclass.
         * @param int $cap The maximum number of items the buffer can hold. Must be at least 1.
         */
        public function __construct (int $cap) {
            parent::__construct($cap, null);
        }

        /**
         * Creates a new typed circular buffer pre-loaded with the given items.
         * The type parameter is intentionally omitted — it is declared on the subclass.
         * If no capacity is specified, it defaults to the number of items (minimum 1).
         * @param array<T> $items The initial items to write, oldest first.
         * @param string|null $type Ignored; exists only for signature compatibility.
         * @param int|null $cap The fixed capacity. Defaults to max(1, count($items)).
         * @return static The created buffer.
         */
        public static function from (array $items, ?string $type = null, ?int $cap = null) : static {
            $buffer = new static($cap ?? max(1, count($items)));

            foreach ($items as $item) {
                $buffer->write($item);
            }

            return $buffer;
        }

        /**
         * Not supported on TypedCircularBuffer; the type is declared as a class property on the subclass.
         * @throws LogicException Always.
         */
        public static function withType (string $type, ?int $cap = null) : static {
            throw new LogicException(static::class . " has a fixed item type and does not support withType().");
        }
    }
?>
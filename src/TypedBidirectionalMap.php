<?php
    /**
     * Project Name:    Wingman Strux - Typed Bidirectional Map
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
     * Represents a bijective map with value type enforcement.
     *
     * Concrete subclasses must declare a typed {@see BidirectionalMap::$type} class property
     * to activate enforcement. Direct instantiation of this class is intentionally
     * disallowed — use a subclass that specifies the target type.
     *
     * The type constraint may be any PHP primitive alias, a fully-qualified class or
     * interface name, or a Verix schema expression (requires the Wingman Verix package).
     *
     * @package Wingman\Strux
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     * @template K of int|string
     * @template V of int|string
     * @extends BidirectionalMap<K, V>
     */
    abstract class TypedBidirectionalMap extends BidirectionalMap {
        /**
         * Creates a new typed bidirectional map.
         * The type is not accepted as a constructor parameter — it must be declared as a
         * class property in the concrete subclass.
         * @param array<K, V> $data An associative array of key => value pairs to pre-load.
         */
        public function __construct (array $data = []) {
            parent::__construct($data, null);
        }

        /**
         * Creates a new typed bidirectional map pre-loaded with the given key-value pairs.
         * The type parameter is intentionally omitted — it is declared on the subclass.
         * @param array<K, V> $data An associative array of key => value pairs.
         * @param string|null $type Ignored; exists only for signature compatibility.
         * @return static The created map.
         */
        public static function from (array $data, ?string $type = null) : static {
            return new static($data);
        }

        /**
         * Not supported on TypedBidirectionalMap; the type is declared as a class property on the subclass.
         * @throws LogicException Always.
         */
        public static function withType (string $type) : static {
            throw new LogicException(static::class . " has a fixed value type and does not support withType().");
        }
    }
?>
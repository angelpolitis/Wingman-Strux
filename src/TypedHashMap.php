<?php
    /**
     * Project Name:    Wingman Strux - Typed Hash Map
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
     * Represents a hash map with value type enforcement.
     *
     * Concrete subclasses must declare a typed {@see HashMap::$type} class property to
     * activate enforcement. Direct instantiation of this class is intentionally
     * disallowed — use a subclass that specifies the target type.
     *
     * The type constraint may be any PHP primitive alias, a fully-qualified class or
     * interface name, or a Verix schema expression (requires the Wingman Verix package).
     *
     * Keys remain unrestricted; only values are subject to type enforcement.
     *
     * @package Wingman\Strux
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     * @template K
     * @template V
     * @extends HashMap<K, V>
     */
    abstract class TypedHashMap extends HashMap {
        /**
         * Creates a new typed hash map.
         * The type is not accepted as a constructor parameter — it must be declared as a
         * class property in the concrete subclass.
         * @param array $data The data to import.
         */
        public function __construct (array $data = []) {
            parent::__construct($data, null);
        }

        /**
         * Creates a new instance of the typed hash map pre-loaded with the given data.
         * Overrides the base implementation to avoid passing a null type argument,
         * allowing the subclass-declared {@see HashMap::$type} property to remain authoritative.
         * @param array $data The data to pre-load.
         * @return static A new instance.
         */
        protected function createInstance (array $data = []) : static {
            return new static($data);
        }

        /**
         * Creates a new typed hash map from an array.
         * The type parameter is intentionally omitted — it is declared on the subclass.
         * @param array $data The data to import.
         * @param string|null $type Ignored; exists only for signature compatibility.
         * @return static The created hash map.
         */
        public static function from (array $data, ?string $type = null) : static {
            return new static($data);
        }
    }
?>
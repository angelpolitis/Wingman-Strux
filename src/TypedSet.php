<?php
    /**
     * Project Name:    Wingman Strux - Typed Set
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
     * Represents a set with type enforcement.
     *
     * Concrete subclasses must declare a typed {@see Set::$type} class property to
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
     * @extends Set<T>
     */
    abstract class TypedSet extends Set {
        /**
         * Creates a new typed set.
         * The type is not accepted as a constructor parameter — it must be declared as a
         * class property in the concrete subclass.
         * @param array $items The initial items (duplicates are silently discarded).
         * @param bool $frozen Whether the set should be immediately frozen.
         */
        public function __construct (array $items = [], bool $frozen = false) {
            parent::__construct($items, null, $frozen);
        }

        /**
         * Creates a new instance of the typed set pre-loaded with the given items.
         * Overrides the base implementation to avoid passing a null type argument,
         * allowing the subclass-declared {@see Set::$type} property to remain authoritative.
         * @param array $items The items to pre-load.
         * @return static A new instance.
         */
        protected function createInstance (array $items = []) : static {
            return new static($items);
        }

        /**
         * Creates a new typed set from an array of initial items.
         * The type parameter is intentionally omitted — it is declared on the subclass.
         * @param array $items The initial items.
         * @param string|null $type Ignored; exists only for signature compatibility.
         * @param bool $frozen Whether to freeze the set immediately.
         * @return static The created set.
         */
        public static function from (array $items, ?string $type = null, bool $frozen = false) : static {
            return new static($items, $frozen);
        }

        /**
         * Not supported on TypedSet; the type is declared as a class property on the subclass.
         * @throws LogicException Always.
         */
        public static function withType (string $type) : static {
            throw new LogicException(
                "withType() is not supported on TypedSet subclasses. " .
                "Declare the \$type property directly on the subclass instead."
            );
        }
    }
?>
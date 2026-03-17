<?php
    /**
     * Project Name:    Wingman Strux - Typed Collection
     * Created by:      Angel Politis
     * Creation Date:   Nov 18 2025
     * Last Modified:   Mar 17 2026
     *
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Strux namespace.
    namespace Wingman\Strux;

    /**
     * Represents a collection with type enforcement.
     * Concrete subclasses must declare a typed {@see Collection::$type} class property to
     * activate enforcement. Direct instantiation of this class is intentionally disallowed —
     * use a subclass that specifies the target type.
     * @package Wingman\Strux
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    abstract class TypedCollection extends Collection {
        /**
         * Creates a new collection.
         * @param array $items The items to add to the collection.
         * @param int|null $cap The maximum capacity of the collection.
         * @param bool $frozen Whether the collection should be frozen.
         */
        public function __construct (array $items = [], ?int $cap = null, bool $frozen = false) {
            parent::__construct($items, null, $cap, $frozen);
        }

        /**
         * Creates a new instance of the collection pre-loaded with the given items.
         * Overrides the base implementation to avoid passing a null type argument,
         * allowing the subclass-declared {@see Collection::$type} property to remain authoritative.
         * @param array $items The items to pre-load.
         * @return static A new instance.
         */
        protected function createInstance (array $items = []) : static {
            return new static($items);
        }
    }
?>
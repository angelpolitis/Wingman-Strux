<?php
    /**
     * Project Name:    Wingman Strux - Map Interface
     * Created by:      Angel Politis
     * Creation Date:   Mar 15 2026
     * Last Modified:   Mar 17 2026
     *
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Strux Interfaces namespace.
    namespace Wingman\Strux\Interfaces;

    # Import the following interfaces to the current scope.
    use Countable;

    /**
     * Describes a key-to-value mapping where every key is unique.
     *
     * Implementations may accept any serialisable value as a key (including objects)
     * and must guarantee that get, has, set, and remove run in amortised O(1). The
     * contract intentionally does not extend IteratorAggregate so that implementations
     * may satisfy either Iterator or IteratorAggregate internally.
     *
     * @package Wingman\Strux\Interfaces
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     * @template K
     * @template V
     */
    interface MapInterface extends Countable {
        /**
         * Returns the value mapped to the given key, or null if the key is absent.
         * @param mixed $key The key.
         * @return mixed The value, or null.
         */
        public function get (mixed $key) : mixed;

        /**
         * Returns all keys held by the map.
         * @return array The keys.
         */
        public function getKeys () : array;

        /**
         * Returns the number of key-value pairs in the map.
         * @return int The size.
         */
        public function getSize () : int;

        /**
         * Returns all values held by the map.
         * @return array The values.
         */
        public function getValues () : array;

        /**
         * Returns whether the given key exists in the map (including null-valued entries).
         * @param mixed $key The key.
         * @return bool Whether the key exists.
         */
        public function has (mixed $key) : bool;

        /**
         * Removes the given key and its associated value from the map.
         * @param mixed $key The key.
         * @return static The map.
         */
        public function remove (mixed $key) : static;

        /**
         * Associates the given value with the given key, overwriting any prior value.
         * @param mixed $key The key.
         * @param mixed $value The value.
         * @return static The map.
         */
        public function set (mixed $key, mixed $value) : static;
    }
?>

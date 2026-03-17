<?php
    /**
     * Project Name:    Wingman Strux - Sequence Interface
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
    use IteratorAggregate;

    /**
     * Describes a finite, ordered sequence of items that can be traversed and counted.
     *
     * Structures implementing this contract are suitable for use wherever an ordered
     * collection is required without coupling the consumer to a specific access pattern
     * (array-like, stack-like, queue-like, etc.).
     *
     * @package Wingman\Strux\Interfaces
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     * @template T
     * @extends IteratorAggregate<int, T>
     */
    interface SequenceInterface extends Countable, IteratorAggregate {
        /**
         * Returns the number of items currently held in the sequence.
         * @return int The number of items.
         */
        public function getSize () : int;

        /**
         * Returns whether the sequence contains no items.
         * @return bool Whether the sequence is empty.
         */
        public function isEmpty () : bool;
    }
?>

<?php
    /**
     * Project Name:    Wingman Strux - Node List
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
     * Represents a collection of tree nodes.
     * @package Wingman\Strux
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class NodeList extends TypedCollection {
        /**
         * The type of a collection.
         * @var class-string<T>|string|null
         */
        protected ?string $type = Node::class;
    }
?>
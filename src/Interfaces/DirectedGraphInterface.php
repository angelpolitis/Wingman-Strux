<?php
    /**
     * Project Name:    Wingman Strux - Directed Graph Interface
     * Created by:      Angel Politis
     * Creation Date:   Mar 16 2026
     * Last Modified:   Mar 17 2026
     *
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Strux Interfaces namespace.
    namespace Wingman\Strux\Interfaces;

    /**
     * Extends the base graph contract with directed-edge semantics.
     *
     * In a directed graph each edge has an explicit source and target, so an edge from
     * node A to node B does not imply an edge from B to A. Consumers that need to
     * distinguish between outgoing neighbours (getNeighbours()) and incoming neighbours
     * (getInNeighbours()) should type-hint against this narrower interface.
     *
     * @package Wingman\Strux\Interfaces
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     * @template V
     * @extends GraphInterface<V>
     */
    interface DirectedGraphInterface extends GraphInterface {
        /**
         * Returns all nodes that have an outgoing edge pointing to the given node.
         * Returns an empty array if the node does not exist or has no incoming edges.
         * @param int|string $id The target node ID.
         * @return array<int|string, array> A map of source node ID to edge attributes.
         */
        public function getInNeighbours (int|string $id) : array;
    }
?>
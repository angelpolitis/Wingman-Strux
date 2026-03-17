<?php
    /**
     * Project Name:    Wingman Strux - Directed Graph
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

    # Import the following interfaces to the current scope.
    use Wingman\Strux\Bridge\Corvus\Emitter;
    use Wingman\Strux\Enums\Signal;
    use Wingman\Strux\Interfaces\DirectedGraphInterface;

    /**
     * Represents a directed graph in which every edge has an explicit source and target.
     *
     * Extends Graph with directed edge semantics. addEdge(A, B) creates an edge from A to
     * B but not from B to A. The graph therefore maintains two internal index structures:
     * the inherited symmetric $edges array (repurposed here as the out-edge index: from →
     * [to → attrs]) and a private $inEdges reverse index (to → [from → attrs]).
     *
     * Maintaining the reverse index means that both outgoing (neighbours / outNeighbours)
     * and incoming (inNeighbours) adjacency lookups are O(1). The trade-off is doubled
     * edge storage, which is accepted for the data-structure layer where algorithm
     * performance is the priority.
     *
     * getEdges() returns all directed edges without deduplication; eachEdge() and filter()
     * inherit the same directed semantics through polymorphism.
     *
     * Typical use-cases: dependency trees, callgraphs, state machines, workflow DAGs,
     * network topology models, any relationship model where direction matters.
     *
     * @package Wingman\Strux
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     * @template V
     * @extends Graph<V>
     */
    class DirectedGraph extends Graph implements DirectedGraphInterface {
        /**
         * The reverse adjacency list mapping each node ID to the nodes that carry an
         * outgoing edge pointing to it. Maintained in sync with $edges to enable O(1)
         * in-neighbour lookup.
         * @var array<int|string, array<int|string, array>>
         */
        private array $inEdges = [];

        /**
         * Adds a directed edge from the first node to the second.
         * If either node does not yet exist, it is created automatically with a value of true.
         * When working with a typed graph, add nodes with proper values via addNode() before
         * connecting them.
         * If the directed edge already exists, its attributes are updated in place without
         * changing the edge count.
         * @param int|string $from The ID of the source node.
         * @param int|string $to The ID of the target node.
         * @param array $attributes Arbitrary edge metadata (e.g. ['weight' => 5.0]).
         * @return static The graph.
         */
        public function addEdge (int|string $from, int|string $to, array $attributes = []) : static {
            if (!array_key_exists($from, $this->nodes)) $this->nodes[$from] = true;
            if (!array_key_exists($to, $this->nodes)) $this->nodes[$to] = true;

            $isNew = !isset($this->edges[$from][$to]);

            $this->edges[$from][$to] = $attributes;
            $this->inEdges[$to][$from] = $attributes;

            if ($isNew) {
                $this->edgeCount++;
                Emitter::create()->with(from: $from, to: $to, attributes: $attributes, graph: $this)->emit(Signal::GRAPH_EDGE_ADDED);
            }

            return $this;
        }

        /**
         * Gets all directed edges as a list of [from, to, attributes] triples.
         * Each directed edge appears exactly once; the reverse direction is not included
         * unless it was added explicitly.
         * @return list<array{0: int|string, 1: int|string, 2: array}> The edge list.
         */
        public function getEdges () : array {
            $result = [];

            foreach ($this->edges as $from => $neighbours) {
                foreach ($neighbours as $to => $attrs) {
                    $result[] = [$from, $to, $attrs];
                }
            }

            return $result;
        }

        /**
         * Gets the in-neighbour map for the given node.
         * Returns all nodes that have a directed edge pointing to the given node.
         * Returns an empty array if the node has no incoming edges or does not exist.
         * @param int|string $id The node identifier.
         * @return array<int|string, array> A map of source ID => edge attributes.
         */
        public function getInNeighbours (int|string $id) : array {
            return $this->inEdges[$id] ?? [];
        }

        /**
         * Removes the directed edge from the first node to the second.
         * The reverse direction is not affected.
         * Has no effect if no such directed edge exists.
         * @param int|string $from The ID of the source node.
         * @param int|string $to The ID of the target node.
         * @return static The graph.
         */
        public function removeEdge (int|string $from, int|string $to) : static {
            if (!isset($this->edges[$from][$to])) return $this;

            unset($this->edges[$from][$to], $this->inEdges[$to][$from]);

            $this->edgeCount--;
            Emitter::create()->with(from: $from, to: $to, graph: $this)->emit(Signal::GRAPH_EDGE_REMOVED);

            return $this;
        }

        /**
         * Removes the node with the given ID and all directed edges incident to it,
         * both outgoing (from this node) and incoming (to this node).
         * Has no effect if the node does not exist.
         * @param int|string $id The node identifier.
         * @return static The graph.
         */
        public function removeNode (int|string $id) : static {
            if (!array_key_exists($id, $this->nodes)) return $this;

            foreach ($this->edges[$id] ?? [] as $to => $_) {
                if ($to !== $id) unset($this->inEdges[$to][$id]);

                $this->edgeCount--;
            }

            unset($this->edges[$id]);

            foreach ($this->inEdges[$id] ?? [] as $from => $_) {
                if ($from !== $id) {
                    $this->edgeCount--;
                    unset($this->edges[$from][$id]);
                }
            }

            unset($this->inEdges[$id], $this->nodes[$id]);
            Emitter::create()->with(id: $id, graph: $this)->emit(Signal::GRAPH_NODE_REMOVED);

            return $this;
        }
    }
?>
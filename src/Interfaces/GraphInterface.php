<?php
    /**
     * Project Name:    Wingman Strux - Graph Interface
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

    # Import the following interfaces to the current scope.
    use Countable;
    use IteratorAggregate;
    use Traversable;

    /**
     * Describes a graph structure of typed nodes connected by optionally attributed edges.
     *
     * Consumers that work with any undirected or directed graph should type-hint against
     * this interface. It covers the complete structural, query, and functional surface of
     * the Graph family without coupling the consumer to a concrete implementation.
     *
     * All methods encompass both mutation (addEdge, addNode, removeEdge, removeNode) and
     * structural query (getNode, getEdge, hasNode, hasEdge, neighbours, …) operations,
     * together with a full suite of functional iterators (each, every, filter, find, none,
     * reduce, some).
     *
     * @package Wingman\Strux\Interfaces
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     * @template V
     * @extends IteratorAggregate<int|string, V>
     */
    interface GraphInterface extends Countable, IteratorAggregate {
        /**
         * Adds an edge between two nodes with optional attributes.
         * If either node does not yet exist it is created implicitly with a default value of true.
         * @param int|string $from The source (or first endpoint) node ID.
         * @param int|string $to The target (or second endpoint) node ID.
         * @param array $attributes Optional key-value attributes stored on the edge.
         * @return static The graph.
         */
        public function addEdge (int|string $from, int|string $to, array $attributes = []) : static;

        /**
         * Adds a node with an associated value to the graph.
         * If the node already exists its value is updated in place.
         * @param int|string $id The node ID.
         * @param mixed $value The value to associate with the node. Defaults to true.
         * @return static The graph.
         */
        public function addNode (int|string $id, mixed $value = true) : static;

        /**
         * Returns the total number of nodes in the graph.
         * Satisfies the Countable contract.
         * @return int The node count.
         */
        public function count () : int;

        /**
         * Returns the number of edges in the graph.
         * @return int The edge count.
         */
        public function countEdges () : int;

        /**
         * Returns the number of nodes in the graph. Alias of getSize().
         * @return int The node count.
         */
        public function countNodes () : int;

        /**
         * Invokes the given callback once for each node, passing the value and ID.
         * @param callable(V, int|string): void $callback The callback to invoke.
         * @return static The graph.
         */
        public function each (callable $callback) : static;

        /**
         * Invokes the given callback once for each edge, passing from-ID, to-ID, and attributes.
         * @param callable(int|string, int|string, array): void $callback The callback to invoke.
         * @return static The graph.
         */
        public function eachEdge (callable $callback) : static;

        /**
         * Returns whether every node satisfies the given predicate.
         * @param callable(V, int|string): bool $predicate A predicate receiving the value and ID.
         * @return bool Whether all nodes pass.
         */
        public function every (callable $predicate) : bool;

        /**
         * Returns a new graph containing only the nodes that satisfy the given predicate.
         * Edges between retained nodes are also retained.
         * @param callable(V, int|string): bool $predicate A predicate receiving the value and ID.
         * @return static The filtered graph.
         */
        public function filter (callable $predicate) : static;

        /**
         * Returns the value of the first node that satisfies the given predicate, or null.
         * @param callable(V, int|string): bool $predicate A predicate receiving the value and ID.
         * @return V|null The value, or null.
         */
        public function find (callable $predicate) : mixed;

        /**
         * Returns the attributes of the edge between two nodes, or null if it does not exist.
         * @param int|string $from The source node ID.
         * @param int|string $to The target node ID.
         * @return array|null The edge attributes, or null.
         */
        public function getEdge (int|string $from, int|string $to) : ?array;

        /**
         * Returns all edges as a list of [from, to, attributes] tuples.
         * @return array<int, array{0: int|string, 1: int|string, 2: array}> The edges.
         */
        public function getEdges () : array;

        /**
         * Returns a Traversable over all nodes, yielding ID => value pairs.
         * Satisfies the IteratorAggregate contract.
         * @return Traversable<int|string, V> The iterator.
         */
        public function getIterator () : Traversable;

        /**
         * Returns all nodes adjacent to the given node ID as a map of neighbour ID to edge attributes.
         * Returns an empty array if the node does not exist or has no neighbours.
         * @param int|string $id The node ID.
         * @return array<int|string, array> A map of neighbour ID to edge attributes.
         */
        public function getNeighbours (int|string $id) : array;

        /**
         * Returns the value associated with the given node ID, or null if not present.
         * @param int|string $id The node ID.
         * @return V|null The node value, or null.
         */
        public function getNode (int|string $id) : mixed;

        /**
         * Returns all node IDs as a plain array.
         * @return (int|string)[] The node IDs.
         */
        public function getNodes () : array;

        /**
         * Returns the number of nodes in the graph.
         * @return int The node count.
         */
        public function getSize () : int;

        /**
         * Returns the type constraint enforced on node values, or null if unrestricted.
         * @return string|null The type.
         */
        public function getType () : ?string;

        /**
         * Returns whether an edge exists between two nodes.
         * @param int|string $from The source node ID.
         * @param int|string $to The target node ID.
         * @return bool Whether the edge exists.
         */
        public function hasEdge (int|string $from, int|string $to) : bool;

        /**
         * Returns whether a node with the given ID exists in the graph.
         * @param int|string $id The node ID.
         * @return bool Whether the node exists.
         */
        public function hasNode (int|string $id) : bool;

        /**
         * Returns whether the graph contains no nodes.
         * @return bool Whether the graph is empty.
         */
        public function isEmpty () : bool;

        /**
         * Returns whether no node satisfies the given predicate.
         * Delegates to the inverse of some().
         * @param callable(V, int|string): bool $predicate A predicate receiving the value and ID.
         * @return bool Whether no nodes pass.
         */
        public function none (callable $predicate) : bool;

        /**
         * Reduces all nodes to a single accumulated value using the given callback.
         * @param mixed $initial The initial accumulator value.
         * @param callable(mixed, V, int|string): mixed $callback The reducer callback.
         * @return mixed The final accumulated value.
         */
        public function reduce (mixed $initial, callable $callback) : mixed;

        /**
         * Removes the edge between two nodes.
         * Has no effect if the edge does not exist.
         * @param int|string $from The source (or first endpoint) node ID.
         * @param int|string $to The target (or second endpoint) node ID.
         * @return static The graph.
         */
        public function removeEdge (int|string $from, int|string $to) : static;

        /**
         * Removes a node and all edges incident to it.
         * Has no effect if the node does not exist.
         * @param int|string $id The node ID.
         * @return static The graph.
         */
        public function removeNode (int|string $id) : static;

        /**
         * Returns whether at least one node satisfies the given predicate.
         * @param callable(V, int|string): bool $predicate A predicate receiving the value and ID.
         * @return bool Whether any node passes.
         */
        public function some (callable $predicate) : bool;

        /**
         * Invokes the given callback with the graph and returns the graph unchanged.
         * Useful for debugging or side-effectful inspection in a method chain.
         * @param callable(static): void $callback The callback to invoke.
         * @return static The graph.
         */
        public function tap (callable $callback) : static;
    }
?>
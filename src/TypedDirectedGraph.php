<?php
    /**
     * Project Name:    Wingman Strux - Typed Directed Graph
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
     * Represents a directed graph with node value type enforcement.
     *
     * Concrete subclasses must declare a typed {@see Graph::$type} class property to
     * activate enforcement. Direct instantiation of this class is intentionally
     * disallowed — use a subclass that specifies the target node value type.
     *
     * Inherits all directed edge semantics from DirectedGraph: addEdge() is one-directional,
     * getEdges() returns directed pairs, and getInNeighbours() provides O(1) reverse lookup.
     *
     * The type constraint applies to node values only. Edge attribute arrays are always
     * untyped. To enforce per-node types, add nodes explicitly with addNode() before
     * connecting them via addEdge(); any node auto-created by addEdge() will carry a
     * default value of true, which may not satisfy the declared type.
     *
     * The type constraint may be any PHP primitive alias, a fully-qualified class or
     * interface name, or a Verix schema expression (requires the Wingman Verix package).
     *
     * @package Wingman\Strux
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     * @template V
     * @extends DirectedGraph<V>
     */
    abstract class TypedDirectedGraph extends DirectedGraph {
        /**
         * Creates a new typed directed graph.
         * The type is not accepted as a meaningful constructor parameter — it must be declared
         * as a class property in the concrete subclass. The parameter is accepted (and silently
         * ignored) only to allow internal factory methods such as filter() and from() to call
         * new static(null) without a PHP argument-count error.
         * @param string|null $type Ignored; exists only for internal compatibility.
         */
        public function __construct (?string $type = null) {
            parent::__construct(null);
        }

        /**
         * Creates a new typed directed graph pre-loaded with the given nodes and edges.
         * The type parameter is intentionally omitted — it is declared on the subclass.
         * @param array<int|string, V> $nodes The node ID => value pairs.
         * @param list<array{0: int|string, 1: int|string, 2?: array}> $edges The edge tuples.
         * @param string|null $type Ignored; exists only for signature compatibility.
         * @return static The created graph.
         */
        public static function from (array $nodes, array $edges = [], ?string $type = null) : static {
            return parent::from($nodes, $edges, null);
        }

        /**
         * Not supported on TypedDirectedGraph; the node value type is declared as a class property on the subclass.
         * @throws LogicException Always.
         */
        public static function withType (string $type) : static {
            throw new LogicException(static::class . " has a fixed node value type and does not support withType().");
        }
    }
?>
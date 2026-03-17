<?php
    /**
     * Project Name:    Wingman Strux - Directed Graph Tests
     * Created by:      Angel Politis
     * Creation Date:   Mar 16 2026
     * Last Modified:   Mar 17 2026
     *
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Strux Tests namespace.
    namespace Wingman\Strux\Tests;

    # Import the following classes to the current scope.
    use Wingman\Argus\Attributes\Define;
    use Wingman\Argus\Attributes\Group;
    use Wingman\Argus\Test;
    use Wingman\Strux\DirectedGraph;
    use Wingman\Strux\Interfaces\DirectedGraphInterface;

    /**
     * Tests for the DirectedGraph class, covering directed edge semantics,
     * getInNeighbours(), asymmetric removal, and interface compliance.
     * @package Wingman\Strux\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class DirectedGraphTest extends Test {

        // ─── Directed Edge Semantics ─────────────────────────────────────────────

        #[Group("DirectedGraph")]
        #[Define(
            name: "addEdge() — Directed Edge Does Not Create Reverse",
            description: "Adding edge A→B does not automatically create the reverse edge B→A in a directed graph."
        )]
        public function testDirectedEdgeDoesNotCreateReverse () : void {
            $graph = new DirectedGraph();
            $graph->addNode("a", 1)->addNode("b", 2);
            $graph->addEdge("a", "b");

            $this->assertTrue($graph->hasEdge("a", "b"), "Forward edge (a→b) should exist.");
            $this->assertTrue(!$graph->hasEdge("b", "a"), "Reverse edge (b→a) should NOT exist in a directed graph.");
        }

        // ─── In-Neighbours ───────────────────────────────────────────────────────

        #[Group("DirectedGraph")]
        #[Define(
            name: "getInNeighbours() — Returns Nodes With Edges Pointing Into Target",
            description: "getInNeighbours() returns the IDs of all nodes that have a directed edge pointing to the given node."
        )]
        public function testInNeighboursReturnsSourceNodes () : void {
            $graph = DirectedGraph::from(
                ["a" => 1, "b" => 2, "c" => 3],
                [["a", "c"], ["b", "c"]]
            );

            $inNeighbours = $graph->getInNeighbours("c");

            $this->assertTrue(array_key_exists("a", $inNeighbours), "getInNeighbours('c') should include 'a'.");
            $this->assertTrue(array_key_exists("b", $inNeighbours), "getInNeighbours('c') should include 'b'.");
        }

        // ─── Asymmetric Removal ──────────────────────────────────────────────────

        #[Group("DirectedGraph")]
        #[Define(
            name: "removeEdge() — Removes Only The Specified Direction",
            description: "removeEdge(a, b) removes the a→b edge only; the b→a edge remains if it was separately added."
        )]
        public function testRemoveEdgeRemovesOnlySpecifiedDirection () : void {
            $graph = new DirectedGraph();
            $graph->addNode("x", 0)->addNode("y", 0);
            $graph->addEdge("x", "y")->addEdge("y", "x");

            $graph->removeEdge("x", "y");

            $this->assertTrue(!$graph->hasEdge("x", "y"), "Edge (x→y) should be removed.");
            $this->assertTrue($graph->hasEdge("y", "x"), "Edge (y→x) should remain untouched.");
        }

        #[Group("DirectedGraph")]
        #[Define(
            name: "removeNode() — Removes Node And All Adjacent Directed Edges",
            description: "removeNode() deletes both outgoing and incoming edges for the removed node."
        )]
        public function testRemoveNodeCleansUpDirectedEdges () : void {
            $graph = DirectedGraph::from(
                ["a" => 1, "b" => 2, "c" => 3],
                [["a", "b"], ["b", "c"], ["c", "b"]]
            );
            $graph->removeNode("b");

            $this->assertTrue(!$graph->hasNode("b"), "Node 'b' should be absent after removeNode().");
            $this->assertTrue(!$graph->hasEdge("a", "b"), "Outgoing edge (a→b) should be removed.");
            $this->assertTrue(!$graph->hasEdge("b", "c"), "Outgoing edge (b→c) should be removed.");
            $this->assertTrue(!$graph->hasEdge("c", "b"), "Incoming edge (c→b) should also be removed.");
        }

        // ─── Interface Compliance ────────────────────────────────────────────────

        #[Group("DirectedGraph")]
        #[Define(
            name: "Implements DirectedGraphInterface",
            description: "DirectedGraph implements DirectedGraphInterface."
        )]
        public function testImplementsDirectedGraphInterface () : void {
            $this->assertTrue(new DirectedGraph() instanceof DirectedGraphInterface, "DirectedGraph must implement DirectedGraphInterface.");
        }
    }
?>
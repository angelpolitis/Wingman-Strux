<?php
    /**
     * Project Name:    Wingman Strux - Graph Tests
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
    use Wingman\Strux\Graph;
    use Wingman\Strux\Interfaces\GraphInterface;

    /**
     * Tests for the Graph class, covering node/edge management, neighbour lookup,
     * cascading removal, static factory, filter, and interface compliance.
     * @package Wingman\Strux\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class GraphTest extends Test {

        // ─── Node Management ─────────────────────────────────────────────────────

        #[Group("Graph")]
        #[Define(
            name: "addNode() / getNode() / hasNode() — Node Storage",
            description: "addNode() registers a node; getNode() retrieves its value; hasNode() confirms presence."
        )]
        public function testNodeStorage () : void {
            $graph = new Graph();
            $graph->addNode("a", "alpha");

            $this->assertTrue($graph->hasNode("a"), "hasNode() should return true after addNode().");
            $this->assertTrue($graph->getNode("a") === "alpha", "getNode() should return 'alpha'.");
        }

        #[Group("Graph")]
        #[Define(
            name: "removeNode() — Removes Node And Incident Edges",
            description: "removeNode() deletes the node and all edges connected to it."
        )]
        public function testRemoveNodeRemovesNodeAndIncidentEdges () : void {
            $graph = new Graph();
            $graph->addNode("a", 1)->addNode("b", 2)->addNode("c", 3);
            $graph->addEdge("a", "b")->addEdge("b", "c");

            $graph->removeNode("b");

            $this->assertTrue(!$graph->hasNode("b"), "Node 'b' should be absent after removeNode().");
            $this->assertTrue(!$graph->hasEdge("a", "b"), "Edge a-b should be absent after 'b' is removed.");
            $this->assertTrue(!$graph->hasEdge("b", "c"), "Edge b-c should be absent after 'b' is removed.");
        }

        // ─── Edge Management ─────────────────────────────────────────────────────

        #[Group("Graph")]
        #[Define(
            name: "addEdge() / hasEdge() / getEdge() — Edge Storage",
            description: "addEdge() creates an undirected edge; hasEdge() and getEdge() confirm it from both directions."
        )]
        public function testEdgeStorage () : void {
            $graph = new Graph();
            $graph->addNode("x", 1)->addNode("y", 2);
            $graph->addEdge("x", "y", ["weight" => 5]);

            $this->assertTrue($graph->hasEdge("x", "y"), "hasEdge() should return true for (x, y).");
            $this->assertTrue($graph->hasEdge("y", "x"), "Undirected graph: hasEdge() should also be true for (y, x).");
            $this->assertTrue($graph->getEdge("x", "y")["weight"] === 5, "Edge attributes should carry 'weight' => 5.");
        }

        #[Group("Graph")]
        #[Define(
            name: "removeEdge() — Removes Edge From Both Directions",
            description: "removeEdge() deletes the edge in both directions for an undirected graph."
        )]
        public function testRemoveEdgeRemovesFromBothDirections () : void {
            $graph = new Graph();
            $graph->addNode("a", 0)->addNode("b", 0);
            $graph->addEdge("a", "b");
            $graph->removeEdge("a", "b");

            $this->assertTrue(!$graph->hasEdge("a", "b"), "Edge (a,b) should be gone after removeEdge().");
            $this->assertTrue(!$graph->hasEdge("b", "a"), "Reverse edge (b,a) should also be gone.");
        }

        // ─── Neighbours ──────────────────────────────────────────────────────────

        #[Group("Graph")]
        #[Define(
            name: "getNeighbours() — Returns Adjacent Nodes",
            description: "getNeighbours() returns the IDs of all nodes directly connected to the given node."
        )]
        public function testNeighboursReturnsAdjacentNodes () : void {
            $graph = Graph::from(["a" => 1, "b" => 2, "c" => 3], [["a", "b"], ["a", "c"]]);

            $neighbours = $graph->getNeighbours("a");

            $this->assertTrue(array_key_exists("b", $neighbours), "Neighbours of 'a' should include 'b'.");
            $this->assertTrue(array_key_exists("c", $neighbours), "Neighbours of 'a' should include 'c'.");
            $this->assertTrue(!array_key_exists("a", $neighbours), "Neighbours should not include 'a' itself.");
        }

        // ─── Counts ──────────────────────────────────────────────────────────────

        #[Group("Graph")]
        #[Define(
            name: "countNodes() / countEdges() — Report Graph Size",
            description: "countNodes() and countEdges() return the number of nodes and unique edges respectively."
        )]
        public function testNodeCountAndEdgeCount () : void {
            $graph = Graph::from(["a" => 1, "b" => 2, "c" => 3], [["a", "b"], ["b", "c"]]);

            $this->assertTrue($graph->countNodes() === 3, "countNodes() should return 3.");
            $this->assertTrue($graph->countEdges() === 2, "countEdges() should return 2.");
        }

        // ─── Static Factory ──────────────────────────────────────────────────────

        #[Group("Graph")]
        #[Define(
            name: "from() — Creates A Graph From Nodes And Edges",
            description: "from() builds a fully connected graph from an associative node array and edge tuples."
        )]
        public function testFromBuildsGraphFromNodesAndEdges () : void {
            $graph = Graph::from(
                ["p" => "Paris", "l" => "London", "m" => "Madrid"],
                [["p", "l"], ["l", "m"]]
            );

            $this->assertTrue($graph->countNodes() === 3, "Graph should have 3 nodes.");
            $this->assertTrue($graph->hasEdge("p", "l"), "Graph should have edge (p, l).");
            $this->assertTrue($graph->hasEdge("l", "m"), "Graph should have edge (l, m).");
        }

        // ─── Interface Compliance ────────────────────────────────────────────────

        #[Group("Graph")]
        #[Define(
            name: "Implements GraphInterface",
            description: "Graph implements GraphInterface."
        )]
        public function testImplementsGraphInterface () : void {
            $this->assertTrue(new Graph() instanceof GraphInterface, "Graph must implement GraphInterface.");
        }
    }
?>
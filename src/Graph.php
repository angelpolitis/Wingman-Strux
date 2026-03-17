<?php
    /**
     * Project Name:    Wingman Strux - Graph
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

    # Import the following classes and interfaces to the current scope.
    use ArrayIterator;
    use InvalidArgumentException;
    use Traversable;
    use Wingman\Strux\Bridge\Corvus\Emitter;
    use Wingman\Strux\Bridge\Verix\Validator;
    use Wingman\Strux\Enums\Signal;
    use Wingman\Strux\Interfaces\GraphInterface;

    /**
     * Represents an undirected graph in which nodes hold associated values and edges carry
     * optional attribute arrays.
     *
     * Internally the graph maintains a node registry (id → value) and a symmetric adjacency
     * list (id → [neighbour → attrs]) so that every undirected edge is retrievable in O(1)
     * from either endpoint. Calling addEdge() with IDs that do not yet exist implicitly
     * creates those nodes with a default value of true; to associate typed or meaningful
     * values, call addNode() explicitly before connecting them.
     *
     * getEdges() deduplicates the symmetric adjacency list so each logical undirected edge is
     * returned exactly once. eachEdge() and filter() are powered by the same mechanism, so
     * they also respect the undirected semantics.
     *
     * The optional type constraint applies to node values only — edge attribute arrays are
     * always untyped.
     *
     * Typical use-cases: social networks, route maps, dependency graphs, scene graphs,
     * any relationship model where edges have no intrinsic direction.
     *
     * @package Wingman\Strux
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     * @template V
     */
    class Graph implements GraphInterface {
        /**
         * The cached normalised (lowercased) form of the enforced type name.
         * Only populated for primitive type enforcement.
         * @var string|null
         */
        private ?string $normalisedType = null;

        /**
         * Whether the enforced type resolves to a class or interface.
         * Lazily computed on the first invocation of enforceType.
         * @var bool|null
         */
        private ?bool $typeIsClass = null;

        /**
         * The total number of logical edges in the graph.
         * For undirected graphs, each edge is counted once regardless of endpoint order.
         * @var int
         */
        protected int $edgeCount = 0;

        /**
         * The symmetric adjacency list.
         * For every undirected edge (A, B), both $edges[A][B] and $edges[B][A] are set to
         * the same attribute array. Self-loops are stored once: $edges[A][A].
         * @var array<int|string, array<int|string, array>>
         */
        protected array $edges = [];

        /**
         * The node registry mapping each node ID to its associated value.
         * @var array<int|string, V>
         */
        protected array $nodes = [];

        /**
         * The type that every node value must conform to, or null for no enforcement.
         * @var string|null
         */
        protected ?string $type = null;

        /**
         * Creates a new undirected graph.
         * @param string|null $type The type constraint for node values.
         */
        public function __construct (?string $type = null) {
            if (isset($type)) $this->type = $type;
        }

        /**
         * Enforces the graph's type constraint against each given node value.
         * The class/interface vs. primitive distinction and the normalised type name are
         * lazily cached after the first invocation.
         * @param mixed ...$items The values to validate.
         * @throws InvalidArgumentException If any value does not conform to the type.
         */
        protected function enforceType (mixed ...$items) : void {
            if (!isset($this->type)) return;

            if (Validator::isSchemaExpression($this->type)) {
                foreach ($items as $i => $item) {
                    Validator::validate($item, $this->type, $i);
                }

                return;
            }

            $this->typeIsClass ??= class_exists($this->type) || interface_exists($this->type);

            if ($this->typeIsClass) {
                foreach ($items as $i => $item) {
                    if (!($item instanceof $this->type)) {
                        throw new InvalidArgumentException("The value (index: $i) doesn't match the type '{$this->type}'.");
                    }
                }

                return;
            }

            $this->normalisedType ??= strtolower($this->type);

            foreach ($items as $i => $item) {
                $valid = match ($this->normalisedType) {
                    "int", "integer" => is_int($item),
                    "float", "double" => is_float($item),
                    "string" => is_string($item),
                    "bool", "boolean" => is_bool($item),
                    "array" => is_array($item),
                    "callable" => is_callable($item),
                    "object" => is_object($item),
                    default => throw new InvalidArgumentException("Unknown type '{$this->type}' for type enforcement.")
                };

                if (!$valid) {
                    $actual = is_object($item) ? get_class($item) : gettype($item);
                    throw new InvalidArgumentException("The value (index: $i) is of type '{$actual}' but expected '{$this->type}'.");
                }
            }
        }

        /**
         * Adds an undirected edge between the two given nodes.
         * If either node does not yet exist, it is created automatically with a value of true.
         * When working with a typed graph, add nodes with proper values via addNode() before
         * connecting them.
         * If the edge already exists, its attributes are updated in place without changing
         * the edge count.
         * @param int|string $from The ID of the first endpoint.
         * @param int|string $to The ID of the second endpoint.
         * @param array $attributes Arbitrary edge metadata (e.g. ['weight' => 5.0]).
         * @return static The graph.
         */
        public function addEdge (int|string $from, int|string $to, array $attributes = []) : static {
            if (!array_key_exists($from, $this->nodes)) $this->nodes[$from] = true;
            if (!array_key_exists($to, $this->nodes)) $this->nodes[$to] = true;

            $isNew = !isset($this->edges[$from][$to]);

            $this->edges[$from][$to] = $attributes;

            if ($from !== $to) $this->edges[$to][$from] = $attributes;

            if ($isNew) {
                $this->edgeCount++;
                Emitter::create()->with(from: $from, to: $to, attributes: $attributes, graph: $this)->emit(Signal::GRAPH_EDGE_ADDED);
            }

            return $this;
        }

        /**
         * Adds a node with the given ID and associated value to the graph.
         * If the node already exists, its value is updated.
         * @param int|string $id The node identifier.
         * @param V $value The value to associate with the node. Defaults to true.
         * @return static The graph.
         * @throws InvalidArgumentException If the value fails type enforcement.
         */
        public function addNode (int|string $id, mixed $value = true) : static {
            $this->enforceType($value);
            $this->nodes[$id] = $value;
            Emitter::create()->with(id: $id, value: $value, graph: $this)->emit(Signal::GRAPH_NODE_ADDED);

            return $this;
        }

        /**
         * Gets the number of nodes in the graph.
         * @return int The node count.
         */
        public function count () : int {
            return $this->getSize();
        }

        /**
         * Gets the total number of edges in the graph.
         * Each undirected edge is counted once.
         * @return int The edge count.
         */
        public function countEdges () : int {
            return $this->edgeCount;
        }

        /**
         * Gets the number of nodes in the graph.
         * @return int The node count.
         */
        public function countNodes () : int {
            return count($this->nodes);
        }

        /**
         * Invokes the given callback for each node and returns the graph unchanged.
         * The callback receives the node value, node ID, and graph as its arguments.
         * @param callable(V, int|string, static): void $callback The callback to invoke.
         * @return static The graph.
         */
        public function each (callable $callback) : static {
            foreach ($this->nodes as $id => $value) {
                $callback($value, $id, $this);
            }

            return $this;
        }

        /**
         * Invokes the given callback for each edge and returns the graph unchanged.
         * Each undirected edge is visited exactly once.
         * The callback receives the from ID, to ID, edge attributes, and graph as its arguments.
         * @param callable(int|string, int|string, array, static): void $callback The callback to invoke.
         * @return static The graph.
         */
        public function eachEdge (callable $callback) : static {
            foreach ($this->getEdges() as [$from, $to, $attrs]) {
                $callback($from, $to, $attrs, $this);
            }

            return $this;
        }

        /**
         * Determines whether all nodes satisfy the given predicate.
         * @param callable(V, int|string): bool $predicate A predicate receiving the value and node ID.
         * @return bool Whether all nodes pass.
         */
        public function every (callable $predicate) : bool {
            foreach ($this->nodes as $id => $value) {
                if (!$predicate($value, $id)) return false;
            }

            return true;
        }

        /**
         * Creates a new graph containing only the nodes that satisfy the given predicate,
         * together with all edges whose both endpoints survived the filter.
         * The resulting graph preserves the same type constraint and graph direction.
         * @param callable(V, int|string): bool $predicate A predicate receiving the value and node ID.
         * @return static The induced subgraph.
         */
        public function filter (callable $predicate) : static {
            $new = new static(null);

            foreach ($this->nodes as $id => $value) {
                if ($predicate($value, $id)) $new->addNode($id, $value);
            }

            foreach ($this->getEdges() as [$from, $to, $attrs]) {
                if ($new->hasNode($from) && $new->hasNode($to)) {
                    $new->addEdge($from, $to, $attrs);
                }
            }

            return $new;
        }

        /**
         * Finds the value of the first node (in insertion order) that satisfies the given predicate.
         * @param callable(V, int|string): bool $predicate A predicate receiving the value and node ID.
         * @return V|null The first matching node value, or null if none is found.
         */
        public function find (callable $predicate) : mixed {
            foreach ($this->nodes as $id => $value) {
                if ($predicate($value, $id)) return $value;
            }

            return null;
        }

        /**
         * Creates a new graph pre-loaded with the given nodes and edges.
         * The nodes array must be an associative array of node ID => node value pairs.
         * The edges array must be a list of [from, to] or [from, to, attributes] tuples.
         * @param array<int|string, V> $nodes The node ID => value pairs.
         * @param list<array{0: int|string, 1: int|string, 2?: array}> $edges The edge tuples.
         * @param string|null $type The type constraint for node values.
         * @return static The created graph.
         */
        public static function from (array $nodes, array $edges = [], ?string $type = null) : static {
            $graph = new static($type);

            foreach ($nodes as $id => $value) {
                $graph->addNode($id, $value);
            }

            foreach ($edges as $edge) {
                $graph->addEdge($edge[0], $edge[1], $edge[2] ?? []);
            }

            return $graph;
        }

        /**
         * Gets the attribute array of the edge between the two given nodes, or null if no
         * such edge exists.
         * @param int|string $from The ID of the first endpoint.
         * @param int|string $to The ID of the second endpoint.
         * @return array|null The edge attributes, or null.
         */
        public function getEdge (int|string $from, int|string $to) : ?array {
            return $this->edges[$from][$to] ?? null;
        }

        /**
         * Gets all edges as a list of [from, to, attributes] triples.
         * For undirected graphs, each logical edge appears exactly once — the pair [A, B]
         * is not duplicated as [B, A]. Self-loops are included once.
         * @return list<array{0: int|string, 1: int|string, 2: array}> The edge list.
         */
        public function getEdges () : array {
            $seen = [];
            $result = [];

            foreach ($this->edges as $from => $neighbours) {
                foreach ($neighbours as $to => $attrs) {
                    $forward = "$from\0$to";
                    $backward = "$to\0$from";

                    if (!isset($seen[$forward]) && !isset($seen[$backward])) {
                        $seen[$forward] = true;
                        $result[] = [$from, $to, $attrs];
                    }
                }
            }

            return $result;
        }

        /**
         * Gets an iterator that yields node ID => value pairs in insertion order.
         * @return Traversable<int|string, V> The iterator.
         */
        public function getIterator () : Traversable {
            return new ArrayIterator($this->nodes);
        }

        /**
         * Gets the adjacency map for the given node.
         * For undirected graphs, this contains every node directly connected to the given
         * node regardless of which direction the edge was added.
         * Returns an empty array if the node has no edges or does not exist.
         * @param int|string $id The node identifier.
         * @return array<int|string, array> A map of neighbour ID => edge attributes.
         */
        public function getNeighbours (int|string $id) : array {
            return $this->edges[$id] ?? [];
        }

        /**
         * Gets the value associated with the given node, or null if the node does not exist.
         * @param int|string $id The node identifier.
         * @return V|null The node value, or null.
         */
        public function getNode (int|string $id) : mixed {
            return $this->nodes[$id] ?? null;
        }

        /**
         * Gets all nodes as a plain associative array of node ID => value pairs.
         * @return array<int|string, V> The node map.
         */
        public function getNodes () : array {
            return $this->nodes;
        }

        /**
         * Gets the number of nodes in the graph.
         * @return int The size.
         */
        public function getSize () : int {
            return $this->countNodes();
        }

        /**
         * Gets the type constraint enforced on node values, or null if unrestricted.
         * @return string|null The type.
         */
        public function getType () : ?string {
            return $this->type;
        }

        /**
         * Determines whether an edge exists between the two given nodes.
         * @param int|string $from The ID of the first endpoint.
         * @param int|string $to The ID of the second endpoint.
         * @return bool Whether the edge exists.
         */
        public function hasEdge (int|string $from, int|string $to) : bool {
            return isset($this->edges[$from][$to]);
        }

        /**
         * Determines whether a node with the given ID exists in the graph.
         * @param int|string $id The node identifier.
         * @return bool Whether the node exists.
         */
        public function hasNode (int|string $id) : bool {
            return array_key_exists($id, $this->nodes);
        }

        /**
         * Determines whether the graph contains no nodes.
         * @return bool Whether the graph is empty.
         */
        public function isEmpty () : bool {
            return $this->nodes === [];
        }

        /**
         * Determines whether no nodes satisfy the given predicate.
         * Delegates to the inverse of some().
         * @param callable(V, int|string): bool $predicate A predicate receiving the value and node ID.
         * @return bool Whether no nodes pass.
         */
        public function none (callable $predicate) : bool {
            return !$this->some($predicate);
        }

        /**
         * Applies the given callback cumulatively to all nodes in insertion order,
         * reducing them to a single value.
         * The callback receives the accumulated carry, the node value, the node ID, and the graph.
         * @param mixed $initial The initial carry value.
         * @param callable(mixed, V, int|string, static): mixed $callback The reduction callback.
         * @return mixed The final accumulated value.
         */
        public function reduce (mixed $initial, callable $callback) : mixed {
            $carry = $initial;

            foreach ($this->nodes as $id => $value) {
                $carry = $callback($carry, $value, $id, $this);
            }

            return $carry;
        }

        /**
         * Removes the edge between the two given nodes.
         * For undirected graphs, both directions are removed.
         * Has no effect if no such edge exists.
         * @param int|string $from The ID of the first endpoint.
         * @param int|string $to The ID of the second endpoint.
         * @return static The graph.
         */
        public function removeEdge (int|string $from, int|string $to) : static {
            if (!isset($this->edges[$from][$to])) return $this;

            unset($this->edges[$from][$to]);

            if ($from !== $to) unset($this->edges[$to][$from]);

            $this->edgeCount--;
            Emitter::create()->with(from: $from, to: $to, graph: $this)->emit(Signal::GRAPH_EDGE_REMOVED);

            return $this;
        }

        /**
         * Removes the node with the given ID and all edges incident to it.
         * Has no effect if the node does not exist.
         * @param int|string $id The node identifier.
         * @return static The graph.
         */
        public function removeNode (int|string $id) : static {
            if (!array_key_exists($id, $this->nodes)) return $this;

            foreach ($this->edges[$id] ?? [] as $neighbour => $_) {
                if ($neighbour !== $id) unset($this->edges[$neighbour][$id]);

                $this->edgeCount--;
            }

            unset($this->edges[$id], $this->nodes[$id]);
            Emitter::create()->with(id: $id, graph: $this)->emit(Signal::GRAPH_NODE_REMOVED);

            return $this;
        }

        /**
         * Determines whether at least one node satisfies the given predicate.
         * @param callable(V, int|string): bool $predicate A predicate receiving the value and node ID.
         * @return bool Whether any node passes.
         */
        public function some (callable $predicate) : bool {
            foreach ($this->nodes as $id => $value) {
                if ($predicate($value, $id)) return true;
            }

            return false;
        }

        /**
         * Invokes the given callback with the graph and returns the graph unchanged.
         * Useful for debugging or side-effectful inspection in a method chain.
         * @param callable(static): void $callback The callback to invoke.
         * @return static The graph.
         */
        public function tap (callable $callback) : static {
            $callback($this);
            return $this;
        }

        /**
         * Creates a new empty undirected graph with the given type constraint for node values.
         * @param string $type The type of node values the graph will enforce.
         * @return static The graph.
         */
        public static function withType (string $type) : static {
            return new static($type);
        }
    }
?>
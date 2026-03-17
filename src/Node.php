<?php
    /**
     * Project Name:    Wingman Strux - Node
     * Created by:      Angel Politis
     * Creation Date:   Sep 18 2022
     * Last Modified:   Mar 17 2026
     *
     * Copyright (c) 2022-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Strux namespace.
    namespace Wingman\Strux;

    # Import the following classes to the current scope.
    use ArrayAccess;
    use Exception;
    use InvalidArgumentException;
    use IteratorAggregate;
    use JsonSerializable;
    use Traversable;
    use Wingman\Strux\Bridge\Corvus\Emitter;
    use Wingman\Strux\Enums\Signal;

    /**
     * Represents a node of a tree.
     * @package Wingman\Strux
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class Node implements ArrayAccess, IteratorAggregate, JsonSerializable {
        /**
         * Whether this node should be automatically removed from its parent when its last child
         * is unset. Disabled by default; enable explicitly via `setAutoPrune(true)`.
         * @var bool
         */
        protected bool $autoPrune = false;

        /**
         * The array containing the children of a tree node.
         * @var NodeList
         */
        protected NodeList $children;

        /**
         * The content of a tree node.
         * @var mixed
         */
        protected mixed $content = null;

        /**
         * The name of a tree node.
         * @var string
         */
        protected string $name = "";

        /**
         * The parent node of a tree node.
         * @var Node|null
         */
        protected ?Node $parent = null;

        /**
         * The qualified name of a tree node.
         * @var string|null
         */
        protected ?string $qualifiedName = null;

        /**
         * Creates a new node.
         * @param mixed $content The content of the node.
         * @param string|null $name The name of the node.
         * @param static|null $parent The parent of the node.
         */
        public function __construct (mixed $content = null, ?string $name = null, ?self $parent = null) {
            # Cache the given name, if given.
            if (!empty($name)) $this->name = $name;

            $this->children = new NodeList();

            # Cache the given parent, if given.
            if ($parent) $this->setParent($parent);

            $this->content = $content;
        }

        /**
         * Deep-clones this node, producing a new independent subtree with no parent.
         * All children are recursively cloned and receive correct parent and qualified-name
         * references, so the resulting tree is fully self-consistent.
         */
        public function __clone () : void {
            $original = $this->children;
            $this->children = new NodeList();
            $this->parent = null;
            $this->qualifiedName = null;

            foreach ($original as $name => $child) {
                $clonedChild                = clone $child;
                $clonedChild->parent        = $this;
                $clonedChild->qualifiedName = static::getQualifiedNameOfNode($clonedChild);
                $this->children[$name]      = $clonedChild;

                # Cascade the correct qualified-name prefix into the cloned subtree.
                $clonedChild->recalculateQualifiedNames();
            }
        }
        
        /**
         * Gets a child of a node.
         * @param string $name The name of the child.
         * @return static|null The child.
         */
        public function __get (string $name) : ?static {
            # If the given name is a qualified name, resolve it to its target.
            if (strpos($name, '.') !== false) {
                return $this->get($name);
            }

            return $this->children[$name] ?? null;
        }
        
        /**
         * Returns whether a child of a node is set.
         * @param string $name The name of the child.
         * @return bool Whether the child exists.
         */
        public function __isset (string $name) : bool {
            # If the given name is a qualified name, resolve it to its target.
            if (strpos($name, '.') !== false) {
                return $this->has($name);
            }

            return isset($this->children[$name]);
        }

        /**
         * Sets a child of a node.
         * @param string $name The name of the child.
         * @param static $child The child.
         */
        public function __set (string $name, self $child) : void {
            # If the given name is a qualified name, delegate to set() which calls import().
            if (strpos($name, '.') !== false) {
                $this->set($name, $child);
                return;
            }

            # Delegate to setChild() — the single source of truth for child attachment.
            $this->setChild($child, $name);
        }

        /**
         * Unsets a child of a node.
         * @param string $name The name of the child.
         */
        public function __unset (string $name) : void {
            if (strpos($name, '.') !== false) {
                $nameParts = explode('.', $name);
                $name = array_pop($nameParts);
                $parentName = implode('.', $nameParts);

                $this->get($parentName)->__unset($name);
                
                return;
            }

            # Resolve the child before removal so its state can be cleaned up.
            $child = $this->children[$name] ?? null;

            if ($child === null) return;

            # Remove the child from this node's list.
            unset($this->children[$name]);

            # Clear the orphaned child's parent and qualified-name references so it is fully
            # self-consistent as a new root node after removal.
            $child->parent = null;
            $child->qualifiedName = null;

            # Auto-prune: if opted in and this node is now childless and has a parent, detach it.
            if ($this->autoPrune && isset($this->parent) && $this->children->isEmpty()) {
                $this->detach();
            }

            # Dispatch the NODE_UNSET signal on the Corvus bus.
            Emitter::create()
                ->with(
                    qualifiedName: $this->getQualifiedName() . ".$name",
                    node: $this,
                    root: $this->getRoot()
                )
                ->emit(Signal::NODE_UNSET);
        }

        /**
         * Recursively recalculates the qualified name of every node in this subtree.
         * Called after any structural mutation — rename, re-parent, or clone — that invalidates
         * the cached `$qualifiedName` of one or more descendants.
         */
        private function recalculateQualifiedNames () : void {
            foreach ($this->children as $child) {
                $child->qualifiedName = static::getQualifiedNameOfNode($child);
                $child->recalculateQualifiedNames();
            }
        }

        /**
         * Detaches this node from its parent, returning it as a free-standing root node.
         * Unlike `__unset()`, this method does not trigger the auto-prune cascade: the parent
         * is not itself removed even if it becomes childless.
         * Dispatches `Signal::NODE_REMOVED` on the Corvus bus.
         * @return static The detached node.
         */
        public function detach () : static {
            if (!isset($this->parent)) return $this;

            # Directly remove this node from the parent's NodeList, bypassing __unset().
            unset($this->parent->children[$this->name]);

            # Clear parent and qualified-name ties.
            $this->parent = null;
            $this->qualifiedName = null;

            # Dispatch the NODE_REMOVED signal on the Corvus bus.
            Emitter::create()
                ->with(node: $this)
                ->emit(Signal::NODE_REMOVED);

            return $this;
        }

        /**
         * Exports a node into a flat array with the qualified names of nodes as keys.
         * @return array The array.
         */
        public function export () : array {
            # Export the root node and return the result.
            return static::exportNode($this);
        }

        /**
         * Exports a node into a flat array with the qualified names of its descendants as keys.
         * @param Node $node The node.
         * @return array The data.
         */
        public static function exportNode (self $node) : array {
            # Define the data array to hold the exported values.
            $data = [];

            # The function that exports a node.
            $export = function (self $node) use (&$export, &$data) {
                # Cache the children of the node.
                $children = $node->getChildren();

                # Iterate over the children.
                foreach ($children as $name => $child) {
                    # Export the child node.
                    $export($child);
                }

                # Determine the qualified name of the child.
                $qualifiedName = isset($node->qualifiedName) ? $node->qualifiedName : $node->name;

                if (!$qualifiedName) return;
                
                # Save the child into the data using its qualified name.
                $data[$qualifiedName] = $node->content;
            };

            # Export the node.
            $export($node, $data);

            # Return the data array.
            return $data;
        }

        /**
         * Returns a new node containing only the children that pass the given callback.
         * When no callback is provided the default truthiness check is applied (falsy children
         * are removed). The mode flag mirrors PHP's array_filter constants:
         *   0                      — callback receives the child value (default)
         *   ARRAY_FILTER_USE_KEY   — callback receives the child key
         *   ARRAY_FILTER_USE_BOTH  — callback receives value and key
         * @param callable|null $callback The filter predicate, or null for truthiness.
         * @param int $mode One of 0, ARRAY_FILTER_USE_KEY, or ARRAY_FILTER_USE_BOTH.
         * @return static A new node containing only the children that passed the filter.
         */
        public function filter (?callable $callback = null, int $mode = 0) : static {
            $result = new static(null, "_root");

            foreach ($this->children as $key => $value) {
                # When no callback is given, keep only truthy children.
                if ($callback === null) {
                    if ($value) $result->setChild(clone $value, $key);
                    continue;
                }

                $shouldKeep = match ($mode) {
                    ARRAY_FILTER_USE_KEY  => $callback($key),
                    ARRAY_FILTER_USE_BOTH => $callback($value, $key),
                    default               => $callback($value),
                };

                if ($shouldKeep) $result->setChild(clone $value, $key);
            }

            return $result;
        }

        /**
         * Creates a new node.
         * @param mixed $content The content of the node.
         * @param string|null $name The name of the node.
         * @param static|null $parent The parent of the node.
         * @return static The created node.
         */
        public static function from (mixed $content, ?string $name = null, ?self $parent = null) : static {
            return new static($content, $name, $parent);
        }

        /**
         * Resolves a qualified name to a node/value from a node.
         * @param string $qualifiedName The qualified name of the node/value.
         * @return static|null The node the qualified name resolves to, if any.
         */
        public function get (string $qualifiedName) : ?static {
            # Split the name at the dots.
            $nameParts = explode('.', $qualifiedName);

            # Cache the current node.
            $node = $this;

            # Iterate over the name parts and cache the child of the node with the iterated name.
            foreach ($nameParts as $part) {
                $node = $node->getChild($part);

                if (!$node) break;
            }

            # Return the node or value the qualified name resolve to.
            return $node;
        }

        /**
         * Returns whether auto-pruning is enabled for this node.
         * @return bool Whether auto-pruning is enabled.
         */
        public function getAutoPrune () : bool {
            return $this->autoPrune;
        }

        /**
         * Gets a child of a node.
         * @param string $name The name of the node.
         * @return Node|null The child node, if any.
         */
        public function getChild (string $name) : ?Node {
            return $this->children[$name] ?? null;
        }

        /**
         * Gets the children of a node.
         * @return NodeList The children of the node.
         */
        public function getChildren () : NodeList {
            return $this->children;
        }

        /**
         * Gets the content of a node.
         * @return mixed The content of the node.
         */
        public function getContent () : mixed {
            return $this->content ?? null;
        }
        
        /**
         * Gets the depth of a node within its tree.
         * The root node has depth 0; each level of ancestry adds 1.
         * @return int The depth of the node.
         */
        public function getDepth () : int {
            $depth = 0;
            $node  = $this;

            while (isset($node->parent)) {
                $depth++;
                $node = $node->parent;
            }

            return $depth;
        }

        /**
         * Gets an iterator for a node so it can be traversed.
         * @return Traversable The iterator.
         */
        public function getIterator () : Traversable {
            return $this->children->getIterator();
        }

        /**
         * Gets the name of a node.
         * @return string The name of the node.
         */
        public function getName () : string {
            return $this->name ?? "";
        }

        /**
         * Gets the parent of a node.
         * @return Node|null The parent of the node.
         */
        public function getParent () : ?static {
            return $this->parent ?? null;
        }

        /**
         * Gets the qualified name of a node.
         * @return string The qualified name.
         */
        public function getQualifiedName () : string {
            return $this->qualifiedName ?? $this->name;
        }

        /**
         * Gets the qualified name of a node.
         * @param Node $node The node.
         * @return string The qualified name.
         */
        public static function getQualifiedNameOfNode (self $node) : string {
            # Return the name of the node if it has no parent.
            if (!isset($node->parent)) return $node->name;

            # Cache the qualified name of the parent.
            $parentQualifiedName = $node->parent->qualifiedName ?? null;

            # Join the qualified name with the name of the node and return the result.
            return ($parentQualifiedName ? $parentQualifiedName . '.' : "") . $node->name;
        }
        
        /**
         * Gets the most distant ancestor of a node.
         * @return Node The root of the node (itself if it's a root node).
         */
        public function getRoot () : static {
            # Cache the parent of the node as the root.
            $root = $this->parent ?? null;

            # Return the node if the node has no parent.
            if (is_null($root)) return $this;

            # Find the current root's parent until the actual root is located.
            while (($root->parent ?? null) !== null) $root = $root->parent;

            # Return the root.
            return $root;
        }

        /**
         * Checks whether a descendant exists at a path.
         * @param string $path A path, e.g. "system", "system.language".
         * @return bool Whether the descendant exists.
         */
        public function has (string $path) : bool {
            $parts = explode('.', $path);
            $current = $this;

            foreach ($parts as $part) {
                if (!$current->hasChild($part)) return false;
                
                $current = $current->getChild($part);

                # If the current node is not a Node but there are still parts left, the path doesn't exist.
                if (!($current instanceof static) && end($parts) !== $part) return false;
            }

            return true;
        }

        /**
         * Checks whether a node has a child of the given name.
         * @param string $name The name of the node.
         * @return bool Whether the node has a child with the given name.
         */
        public function hasChild (string $name) : bool {
            return isset($this->children[$name]);
        }

        /**
         * Imports given data into a node.
         * @param iterable ...$data The data.
         * @return Node The node.
         */
        public function import (iterable ...$data) : static {
            static::importIntoNode($this, ...$data);

            return $this;
        }
        
        /**
         * Imports given data into a node.
         * @param Node The node.
         * @param iterable ...$data The data.
         * @return Node The node.
         */
        public static function importIntoNode (self $node, iterable ...$data) : void {
            foreach ($data as $dataPack) {
                foreach ($dataPack as $name => $value) {
                    $nameParts = explode('.', (string) $name);

                    $nameParts = array_map("trim", $nameParts);
                    
                    $current = $node;

                    foreach (array_slice($nameParts, 0, -1) as $namePart) {
                        if (!$current->hasChild($namePart)) {
                            # Do not pass the parent to the constructor — setChild() handles that.
                            $current->setChild(new static(null, $namePart));
                        }

                        $current = $current->getChild($namePart);
                    }

                    $leafKey = end($nameParts);

                    if ($value instanceof static) {
                        $current->setChild($value, $leafKey);
                    }
                    else {
                        # Do not pass the parent to the constructor — setChild() handles that.
                        $current->setChild(new static($value, $leafKey));
                    }
                }
            }
        }
        
        /**
         * Returns whether this node is a leaf (has no children).
         * @return bool Whether the node has no children.
         */
        public function isLeaf () : bool {
            return $this->children->isEmpty();
        }

        /**
         * Returns whether this node is a root (has no parent).
         * @return bool Whether the node has no parent.
         */
        public function isRoot () : bool {
            return !isset($this->parent);
        }

        /**
         * Gets the data to be serialised to JSON when encoded with json_encode().
         * @return array The data.
         */
        public function jsonSerialize () : mixed {
            return [
                "children" => $this->children,
                "content" => $this->content
            ];
        }
        
        /**
         * Recursively merges one or more nodes into this one.
         * Behaves like array_replace: when both sides hold a child node at the same key, they are
         * merged recursively; otherwise the incoming value wins.
         * @param iterable ...$lists The nodes or iterables to merge in.
         * @return static The merged node.
         */
        public function merge (iterable ...$lists) : static {
            # Create a new detached root node and seed it with clones of the current children.
            $result = new static(null, "_root");

            foreach ($this->children as $name => $child) {
                $result->setChild(clone $child, $name);
            }

            foreach ($lists as $values) {
                foreach ($values as $key => $value) {
                    # When both sides hold a child node at this key, recurse.
                    if (
                        $result->offsetExists($key) &&
                        $result[$key] instanceof static &&
                        $value instanceof static
                    ) {
                        $result->setChild($result[$key]->merge($value), $key);
                        continue;
                    }

                    # Otherwise the incoming value overwrites.
                    $result[$key] = $value;
                }
            }

            return $result;
        }

        /**
         * Deep-merges one or more nodes into this one.
         * Unlike merge(), this method also wraps plain iterables into nodes before recursing, so
         * every level of the tree is fully merged rather than shallowly overwritten.
         * @param iterable ...$lists The nodes or iterables to merge in.
         * @return static The deep-merged node.
         */
        public function mergeRecursive (iterable ...$lists) : static {
            # Create a new detached root node and seed it with clones of the current children.
            $result = new static(null, "_root");

            foreach ($this->children as $name => $child) {
                $result->setChild(clone $child, $name);
            }

            foreach ($lists as $values) {
                foreach ($values as $key => $value) {
                    $leftIsNode  = $result->offsetExists($key) && $result[$key] instanceof static;
                    $rightIsNode = $value instanceof static || is_array($value);

                    if ($leftIsNode && $rightIsNode) {
                        # Normalise plain arrays to nodes before recursing.
                        $rhs = $value instanceof static ? $value : new static(null, (string) $key);

                        if (is_array($value)) $rhs->import($value);

                        $result->setChild($result[$key]->mergeRecursive($rhs), $key);
                    }
                    else $result[$key] = $value;
                }
            }

            return $result;
        }

        /**
         * Returns whether a child of a node is set.
         * @param mixed $name The name of the child.
         * @return bool Whether the child exists.
         */
        public function offsetExists ($name) : bool {
            return isset($this->children[$name]);
        }

        /**
         * Gets a child of a node.
         * @param mixed $name The name of the child.
         * @return mixed The child.
         */
        public function offsetGet ($name) : mixed {
            return $this->children[$name] ?? null;
        }

        /**
         * Sets a child of a node.
         * @param mixed $name The name of the child.
         * @param mixed $child The child.
         */
        public function offsetSet ($name, $child) : void {
            $this->__set($name, $child);
        }

        /**
         * Unsets a child of a node. 
         * @param mixed $name The name of the child.
         */
        public function offsetUnset ($name) : void {
            $this->__unset($name);
        }
        
        /**
         * Removes a named child from this node.
         * Equivalent to calling `detach()` on the child directly — the child is cleanly
         * removed without triggering the `__unset()` auto-prune cascade.
         * @param string $name The name of the child to remove.
         * @return static The node.
         */
        public function removeChild (string $name) : static {
            $child = $this->children[$name] ?? null;

            if ($child !== null) $child->detach();

            return $this;
        }

        /**
         * Sets (recursively if need) a qualified name of a node to a specified value.
         * @param string $qualifiedName The qualified name of the node/value.
         * @param mixed $value The value.
         * @return static The node.
         */
        public function set (string $qualifiedName, mixed $value) : static {
            return $this->import([$qualifiedName => $value]);
        }

        /**
         * Sets whether empty-leaf auto-pruning is enabled for this node.
         * When enabled, this node will automatically detach itself from its parent whenever its
         * last child is unset. Disabled by default.
         * @param bool $autoPrune Whether to enable auto-pruning.
         * @return static The node.
         */
        public function setAutoPrune (bool $autoPrune) : static {
            $this->autoPrune = $autoPrune;
            return $this;
        }

        /**
         * Sets a child to a node.
         * This is the single point of child attachment: it names the child, inserts it into the
         * NodeList, sets the parent reference, and dispatches the NODE_SET signal.
         * If the child is already attached to a different parent it is cleanly detached first.
         * @param static $child The child.
         * @param string|null $name The name to assign to the child; if omitted the child's current name is used.
         * @return static The node.
         */
        public function setChild (self $child, ?string $name = null) : static {
            if (isset($name)) {
                $child->name = $name;
            }

            # If the child is already attached to a different parent, detach it cleanly first
            # so the old parent's NodeList does not retain a stale reference.
            if (isset($child->parent) && $child->parent !== $this) {
                $child->detach();
            }

            # Insert the child into the children of the node.
            $this->children[$child->name] = $child;

            # Set the node as the parent of the child (also calculates qualifiedName and cascades).
            $child->setParent($this);

            # Dispatch the NODE_SET signal on the Corvus bus.
            Emitter::create()
                ->with(
                    qualifiedName: $child->getQualifiedName(),
                    value: $child,
                    node: $this,
                    root: $this->getRoot()
                )
                ->emit(Signal::NODE_SET);

            # Return the context.
            return $this;
        }

        /**
         * Sets the content of a node.
         * @param mixed $content The content of the node.
         * @return static The node.
         */
        public function setContent (mixed $content) : static {
            $this->content = $content;
            return $this;
        }

        /**
         * Sets the name of a node.
         * If the node is attached to a parent, the qualified name of this node and all its
         * descendants are recalculated to reflect the new name.
         * @param string $name The new name.
         * @return static The node.
         */
        public function setName (string $name) : static {
            $this->name = $name;

            # Cascade qualified-name recalculation through the subtree if the node is attached.
            if (isset($this->parent)) {
                $this->qualifiedName = static::getQualifiedNameOfNode($this);
                $this->recalculateQualifiedNames();
            }

            return $this;
        }

        /**
         * Sets the parent of a node.
         * @param Node $parent The parent.
         * @return static The node.
         * @throws Exception If the node being assigned a parent is nameless.
         * @throws InvalidArgumentException If attaching the given parent would create a circular reference.
         */
        public function setParent (self $parent) : static {
            # Throw an exception if a parent is being set without the node having a name.
            if (empty($this->name)) {
                throw new Exception("A nameless node cannot be assigned a parent.");
            }

            # Guard against circular references: walk up $parent's ancestry; if $this is found,
            # then $parent is a descendant of $this, which would form a cycle.
            $probe = $parent;
            while (isset($probe)) {
                if ($probe === $this) {
                    throw new InvalidArgumentException("Circular reference detected: cannot assign a descendant or self as parent.");
                }
                $probe = $probe->parent ?? null;
            }

            # Set the parent of the node.
            $this->parent = $parent;

            # Calculate the qualified name of the node and cascade to all descendants.
            $this->qualifiedName = static::getQualifiedNameOfNode($this);
            $this->recalculateQualifiedNames();

            # Return the context.
            return $this;
        }

        /**
         * Traverses the subtree rooted at this node in depth-first pre-order, invoking
         * the given callback on every node (including this one).
         * @param callable $callback A callable that receives each `Node` as its sole argument.
         */
        public function walk (callable $callback) : void {
            $callback($this);

            foreach ($this->children as $child) {
                $child->walk($callback);
            }
        }
    }
?>
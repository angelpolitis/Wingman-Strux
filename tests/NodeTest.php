<?php
    /**
     * Project Name:    Wingman Strux - Node Tests
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
    use Wingman\Strux\Node;

    /**
     * Tests for the Node class, covering construction, child management, tree navigation,
     * dot-notation path resolution, and the import() / set() convenience API.
     * @package Wingman\Strux\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class NodeTest extends Test {

        // ─── Construction ────────────────────────────────────────────────────────

        #[Group("Node")]
        #[Define(
            name: "Constructor — Stores Content And Name",
            description: "A Node constructed with content and a name correctly reports both via getters."
        )]
        public function testConstructorStoresContentAndName () : void {
            $node = new Node("hello", "greeting");

            $this->assertTrue($node->getContent() === "hello", "getContent() should return 'hello'.");
            $this->assertTrue($node->getName() === "greeting", "getName() should return 'greeting'.");
        }

        #[Group("Node")]
        #[Define(
            name: "New Node — Is Root And Leaf",
            description: "A freshly constructed node with no parent or children is both a root and a leaf."
        )]
        public function testNewNodeIsRootAndLeaf () : void {
            $node = new Node();

            $this->assertTrue($node->isRoot(), "A new node should be a root.");
            $this->assertTrue($node->isLeaf(), "A new node should be a leaf.");
        }

        // ─── Child Management ────────────────────────────────────────────────────

        #[Group("Node")]
        #[Define(
            name: "setChild() / hasChild() — Attaches And Confirms Child",
            description: "setChild() attaches a child node; hasChild() returns true for that name."
        )]
        public function testSetChildAndHasChild () : void {
            $parent = new Node(null, "parent");
            $child = new Node("value", "child");
            $parent->setChild($child);

            $this->assertTrue($parent->hasChild("child"), "hasChild() should return true after setChild().");
            $this->assertTrue(!$parent->isLeaf(), "Parent should no longer be a leaf after adding a child.");
        }

        #[Group("Node")]
        #[Define(
            name: "getChild() — Returns Correct Child",
            description: "getChild() returns the exact child node stored under the given name."
        )]
        public function testGetChildReturnsCorrectChild () : void {
            $parent = new Node();
            $child = new Node("content", "target");
            $parent->setChild($child);

            $this->assertTrue($parent->getChild("target") === $child, "getChild() should return the same object.");
        }

        #[Group("Node")]
        #[Define(
            name: "removeChild() — Detaches Child",
            description: "removeChild() removes the named child so hasChild() returns false and the child isRoot()."
        )]
        public function testRemoveChildDetachesChild () : void {
            $parent = new Node();
            $child = new Node("x", "x");
            $parent->setChild($child);
            $parent->removeChild("x");

            $this->assertTrue(!$parent->hasChild("x"), "Parent should not have child 'x' after removeChild().");
            $this->assertTrue($child->isRoot(), "Detached child should report isRoot().");
        }

        // ─── Tree Navigation ─────────────────────────────────────────────────────

        #[Group("Node")]
        #[Define(
            name: "getDepth() — Reflects Nesting Level",
            description: "getDepth() returns 0 for a root, 1 for its child, 2 for a grandchild, etc."
        )]
        public function testGetDepthReflectsNestingLevel () : void {
            $root = new Node(null, "root");
            $child = new Node(null, "child");
            $grandchild = new Node(null, "grandchild");

            $root->setChild($child);
            $child->setChild($grandchild);

            $this->assertTrue($root->getDepth() === 0, "Root depth should be 0.");
            $this->assertTrue($child->getDepth() === 1, "Child depth should be 1.");
            $this->assertTrue($grandchild->getDepth() === 2, "Grandchild depth should be 2.");
        }

        #[Group("Node")]
        #[Define(
            name: "getParent() / getRoot() — Navigate Up The Tree",
            description: "getParent() returns the immediate parent; getRoot() returns the root of the tree."
        )]
        public function testGetParentAndGetRoot () : void {
            $root = new Node(null, "root");
            $child = new Node(null, "child");
            $grandchild = new Node(null, "grandchild");

            $root->setChild($child);
            $child->setChild($grandchild);

            $this->assertTrue($grandchild->getParent() === $child, "getParent() of grandchild should be child.");
            $this->assertTrue($grandchild->getRoot() === $root, "getRoot() of grandchild should be root.");
        }

        // ─── Dot-Notation API ────────────────────────────────────────────────────

        #[Group("Node")]
        #[Define(
            name: "import() — Builds Nested Structure From Flat Dot-Notation Array",
            description: "import() accepts a flat array with dot-notation keys and creates the corresponding subtree."
        )]
        public function testImportBuildsNestedStructure () : void {
            $root = new Node();
            $root->import(["database.host" => "localhost", "database.port" => 3306]);

            $this->assertTrue($root->has("database.host"), "Root should have path 'database.host'.");
            $this->assertTrue($root->has("database.port"), "Root should have path 'database.port'.");
            $this->assertTrue($root->get("database.host")->getContent() === "localhost", "'database.host' content should be 'localhost'.");
        }

        #[Group("Node")]
        #[Define(
            name: "set() — Creates Or Updates A Single Nested Path",
            description: "set() creates intermediate nodes as needed and sets the leaf value."
        )]
        public function testSetCreatesNestedPath () : void {
            $node = new Node();
            $node->set("app.name", "Wingman");

            $this->assertTrue($node->has("app.name"), "Path 'app.name' should exist after set().");
            $this->assertTrue($node->get("app.name")->getContent() === "Wingman", "Content should be 'Wingman'.");
        }

        #[Group("Node")]
        #[Define(
            name: "has() — Returns False For Non-Existent Paths",
            description: "has() returns false for paths that have not been created."
        )]
        public function testHasReturnsFalseForNonExistentPaths () : void {
            $node = new Node();

            $this->assertTrue(!$node->has("non.existent"), "has() should return false for an undefined path.");
        }

        // ─── Clone ───────────────────────────────────────────────────────────────

        #[Group("Node")]
        #[Define(
            name: "__clone() — Produces An Independent Deep Copy",
            description: "Cloning a node produces a fully independent subtree; modifications to the clone do not affect the original."
        )]
        public function testCloneProducesIndependentDeepCopy () : void {
            $original = new Node(null, "root");
            $original->import(["a" => 1, "b" => 2]);

            $cloned = clone $original;
            $cloned->import(["c" => 3]);

            $this->assertTrue(!$original->has("c"), "Original should not contain 'c' added only to the clone.");
            $this->assertTrue($cloned->has("c"), "Clone should contain the newly added 'c' child.");
            $this->assertTrue($cloned->isRoot(), "Clone should be a root node (no parent).");
        }
    }
?>
<?php
    /**
     * Project Name:    Wingman Strux - Node List Tests
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
    use InvalidArgumentException;
    use Wingman\Argus\Attributes\Define;
    use Wingman\Argus\Attributes\Group;
    use Wingman\Argus\Test;
    use Wingman\Strux\Node;
    use Wingman\Strux\NodeList;

    /**
     * Tests for the NodeList class, confirming it behaves as a typed collection that
     * only accepts Node instances.
     * @package Wingman\Strux\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class NodeListTest extends Test {

        #[Group("NodeList")]
        #[Define(
            name: "Accepts Node Instances",
            description: "A NodeList accepts Node objects without error."
        )]
        public function testAcceptsNodeInstances () : void {
            $list = new NodeList();
            $list->add(new Node("value", "a"));
            $list->add(new Node("value2", "b"));

            $this->assertTrue($list->getSize() === 2, "NodeList should hold 2 Node instances.");
        }

        #[Group("NodeList")]
        #[Define(
            name: "Rejects Non-Node Items",
            description: "Adding a non-Node item to a NodeList throws an InvalidArgumentException."
        )]
        public function testRejectsNonNodeItems () : void {
            $list = new NodeList();
            $thrown = false;

            try {
                $list->add("not a node");
            } catch (InvalidArgumentException $e) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "Adding a string to a NodeList should throw InvalidArgumentException.");
        }

        #[Group("NodeList")]
        #[Define(
            name: "Is Empty On Construction",
            description: "A new NodeList is empty."
        )]
        public function testIsEmptyOnConstruction () : void {
            $list = new NodeList();

            $this->assertTrue($list->isEmpty(), "A new NodeList should be empty.");
        }
    }
?>
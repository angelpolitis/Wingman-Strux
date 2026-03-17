<?php
    /**
     * Project Name:    Wingman Strux - Typed Directed Graph Tests
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
    use Wingman\Strux\TypedDirectedGraph;

    /**
     * Tests for the TypedDirectedGraph abstract class, exercised via an anonymous
     * concrete subclass with a declared node-value type constraint.
     * @package Wingman\Strux\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class TypedDirectedGraphTest extends Test {

        #[Group("TypedDirectedGraph")]
        #[Define(
            name: "Accepts Node Values Matching Declared Type",
            description: "A TypedDirectedGraph subclass accepts node values whose type matches the declared \$type."
        )]
        public function testAcceptsNodeValuesMatchingDeclaredType () : void {
            $graph = new class extends TypedDirectedGraph {
                protected ?string $type = 'int';
            };
            $graph->addNode("a", 1)->addNode("b", 99);

            $this->assertTrue($graph->countNodes() === 2, "Directed graph should have 2 nodes with integer values.");
        }

        #[Group("TypedDirectedGraph")]
        #[Define(
            name: "Rejects Node Values Of Wrong Type",
            description: "Adding a node whose value does not match the declared type throws an InvalidArgumentException."
        )]
        public function testRejectsNodeValuesOfWrongType () : void {
            $graph = new class extends TypedDirectedGraph {
                protected ?string $type = 'int';
            };

            $thrown = false;
            try {
                $graph->addNode("x", "not an int");
            } catch (InvalidArgumentException $e) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "Adding a string node value to an int-typed directed graph should throw InvalidArgumentException.");
        }
    }
?>
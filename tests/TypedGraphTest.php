<?php
    /**
     * Project Name:    Wingman Strux - Typed Graph Tests
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
    use Wingman\Strux\TypedGraph;

    /**
     * Tests for the TypedGraph abstract class, exercised via an anonymous concrete
     * subclass with a declared node-value type constraint.
     * @package Wingman\Strux\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class TypedGraphTest extends Test {

        #[Group("TypedGraph")]
        #[Define(
            name: "Accepts Node Values Matching Declared Type",
            description: "A TypedGraph subclass accepts node values whose type matches the declared \$type."
        )]
        public function testAcceptsNodeValuesMatchingDeclaredType () : void {
            $graph = new class extends TypedGraph {
                protected ?string $type = 'string';
            };
            $graph->addNode("a", "alpha")->addNode("b", "beta");

            $this->assertTrue($graph->countNodes() === 2, "Graph should have 2 nodes with string values.");
        }

        #[Group("TypedGraph")]
        #[Define(
            name: "Rejects Node Values Of Wrong Type",
            description: "Adding a node whose value does not match the declared type throws an InvalidArgumentException."
        )]
        public function testRejectsNodeValuesOfWrongType () : void {
            $graph = new class extends TypedGraph {
                protected ?string $type = 'string';
            };

            $thrown = false;
            try {
                $graph->addNode("x", 42);
            } catch (InvalidArgumentException $e) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "Adding an integer node value to a string-typed graph should throw InvalidArgumentException.");
        }
    }
?>
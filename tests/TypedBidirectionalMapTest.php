<?php
    /**
     * Project Name:    Wingman Strux - Typed Bidirectional Map Tests
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
    use Wingman\Strux\TypedBidirectionalMap;

    /**
     * Tests for the TypedBidirectionalMap abstract class, exercised via an anonymous
     * concrete subclass with a declared value type constraint.
     * @package Wingman\Strux\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class TypedBidirectionalMapTest extends Test {

        #[Group("TypedBidirectionalMap")]
        #[Define(
            name: "Accepts Values Matching Declared Type",
            description: "A TypedBidirectionalMap subclass stores values whose type matches the declared \$type."
        )]
        public function testAcceptsValuesMatchingDeclaredType () : void {
            $map = new class extends TypedBidirectionalMap {
                protected ?string $type = 'int';
            };
            $map->set("one", 1)->set("two", 2);

            $this->assertTrue($map->getSize() === 2, "Map should hold 2 entries.");
        }

        #[Group("TypedBidirectionalMap")]
        #[Define(
            name: "Rejects Values Of Wrong Type",
            description: "Setting a value of the wrong type throws an InvalidArgumentException."
        )]
        public function testRejectsValuesOfWrongType () : void {
            $map = new class extends TypedBidirectionalMap {
                protected ?string $type = 'int';
            };

            $thrown = false;
            try {
                $map->set("key", "not an int");
            } catch (InvalidArgumentException $e) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "Setting a string value in an int-typed map should throw InvalidArgumentException.");
        }
    }
?>
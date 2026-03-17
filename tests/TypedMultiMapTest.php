<?php
    /**
     * Project Name:    Wingman Strux - Typed Multi Map Tests
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
    use Wingman\Strux\TypedMultiMap;

    /**
     * Tests for the TypedMultiMap abstract class, exercised via an anonymous concrete
     * subclass with a declared value type constraint.
     * @package Wingman\Strux\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class TypedMultiMapTest extends Test {

        #[Group("TypedMultiMap")]
        #[Define(
            name: "Accepts Values Matching Declared Type",
            description: "A TypedMultiMap subclass accepts bucket values whose type matches the declared \$type."
        )]
        public function testAcceptsValuesMatchingDeclaredType () : void {
            $map = new class extends TypedMultiMap {
                protected ?string $type = 'string';
            };
            $map->set("words", "foo")->set("words", "bar");

            $this->assertTrue(count($map->get("words")) === 2, "Bucket should hold 2 string values.");
        }

        #[Group("TypedMultiMap")]
        #[Define(
            name: "Rejects Values Of Wrong Type",
            description: "Appending a value of the wrong type throws an InvalidArgumentException."
        )]
        public function testRejectsValuesOfWrongType () : void {
            $map = new class extends TypedMultiMap {
                protected ?string $type = 'string';
            };

            $thrown = false;
            try {
                $map->set("bucket", 42);
            } catch (InvalidArgumentException $e) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "Appending an integer to a string-typed multi-map should throw InvalidArgumentException.");
        }
    }
?>
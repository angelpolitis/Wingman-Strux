<?php
    /**
     * Project Name:    Wingman Strux - Typed LRU Cache Tests
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
    use Wingman\Strux\TypedLruCache;

    /**
     * Tests for the TypedLruCache abstract class, exercised via an anonymous concrete
     * subclass with a declared value type constraint.
     * @package Wingman\Strux\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class TypedLruCacheTest extends Test {

        #[Group("TypedLruCache")]
        #[Define(
            name: "Accepts Values Matching Declared Type",
            description: "A TypedLruCache stores values whose type matches the declared \$type property."
        )]
        public function testAcceptsValuesMatchingDeclaredType () : void {
            $cache = new class(10) extends TypedLruCache {
                protected ?string $type = 'int';
            };
            $cache->put("score", 42);

            $this->assertTrue($cache->has("score"), "Cache should have 'score' after put().");
            $this->assertTrue($cache->get("score") === 42, "get() should return 42.");
        }

        #[Group("TypedLruCache")]
        #[Define(
            name: "Rejects Values Of Wrong Type",
            description: "Storing a value of the wrong type throws an InvalidArgumentException."
        )]
        public function testRejectsValuesOfWrongType () : void {
            $cache = new class(10) extends TypedLruCache {
                protected ?string $type = 'int';
            };

            $thrown = false;
            try {
                $cache->put("key", "not an int");
            } catch (InvalidArgumentException $e) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "Putting a string into an int-typed LRU cache should throw InvalidArgumentException.");
        }
    }
?>
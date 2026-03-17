<?php
    /**
     * Project Name:    Wingman Strux - Typed Trie Tests
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
    use Wingman\Strux\TypedTrie;

    /**
     * Tests for the TypedTrie abstract class, exercised via an anonymous concrete
     * subclass with a declared value type constraint.
     * @package Wingman\Strux\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class TypedTrieTest extends Test {

        #[Group("TypedTrie")]
        #[Define(
            name: "Accepts Values Matching Declared Type",
            description: "A TypedTrie subclass accepts values whose type matches the declared \$type."
        )]
        public function testAcceptsValuesMatchingDeclaredType () : void {
            $trie = new class extends TypedTrie {
                protected ?string $type = 'int';
            };
            $trie->set("score", 1)->set("rank", 2);

            $this->assertTrue($trie->getSize() === 2, "Trie should hold 2 words with integer values.");
        }

        #[Group("TypedTrie")]
        #[Define(
            name: "Rejects Values Of Wrong Type",
            description: "Inserting a value of the wrong type throws an InvalidArgumentException."
        )]
        public function testRejectsValuesOfWrongType () : void {
            $trie = new class extends TypedTrie {
                protected ?string $type = 'int';
            };

            $thrown = false;
            try {
                $trie->set("word", "not an int");
            } catch (InvalidArgumentException $e) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "Inserting a string value into an int-typed trie should throw InvalidArgumentException.");
        }
    }
?>
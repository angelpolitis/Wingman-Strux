<?php
    /**
     * Project Name:    Wingman Strux - Typed Trie
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

    # Import the following classes to the current scope.
    use LogicException;

    /**
     * Represents a prefix tree with value type enforcement.
     *
     * Concrete subclasses must declare a typed {@see Trie::$type} class property to activate
     * enforcement. Direct instantiation of this class is intentionally disallowed — use a
     * subclass that specifies the target value type.
     *
     * The type constraint applies to stored values, not to words (words are always strings).
     * It may be any PHP primitive alias, a fully-qualified class or interface name, or a
     * Verix schema expression (requires the Wingman Verix package).
     *
     * @package Wingman\Strux
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     * @template V
     * @extends Trie<V>
     */
    abstract class TypedTrie extends Trie {
        /**
         * Creates a new typed trie.
         * The type is not accepted as a constructor parameter — it must be declared as a
         * class property in the concrete subclass.
         */
        public function __construct () {
            parent::__construct(null);
        }

        /**
         * Creates a new typed trie pre-loaded with the given data.
         * The type parameter is intentionally omitted — it is declared on the subclass.
         * The array may be a plain list of words (values default to true) or an associative
         * array of word => value pairs.
         * @param array<int, string>|array<string, V> $data The words or word => value pairs to load.
         * @param string|null $type Ignored; exists only for signature compatibility.
         * @return static The created trie.
         */
        public static function from (array $data, ?string $type = null) : static {
            $trie = new static();

            foreach ($data as $key => $value) {
                if (is_int($key)) {
                    $trie->set((string) $value);
                }
                else $trie->set($key, $value);
            }

            return $trie;
        }

        /**
         * Not supported on TypedTrie; the type is declared as a class property on the subclass.
         * @throws LogicException Always.
         */
        public static function withType (string $type) : static {
            throw new LogicException(static::class . " has a fixed value type and does not support withType().");
        }
    }
?>
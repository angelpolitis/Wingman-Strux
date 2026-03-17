<?php
    /**
     * Project Name:    Wingman Strux - Trie
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

    # Import the following classes and interfaces to the current scope.
    use ArrayIterator;
    use Countable;
    use InvalidArgumentException;
    use IteratorAggregate;
    use Traversable;
    use Wingman\Strux\Bridge\Corvus\Emitter;
    use Wingman\Strux\Bridge\Verix\Validator;
    use Wingman\Strux\Enums\Signal;

    /**
     * Represents a prefix tree (trie) that maps string words to associated values.
     *
     * Each character of a word occupies one level of the tree, so the depth of the tree
     * equals the length of the longest stored word. Prefix-based queries (has, withPrefix)
     * run in O(k) time where k is the length of the query string, regardless of how many
     * words are stored. Nodes that become empty after a removal are pruned automatically.
     *
     * By default each word is stored with an associated value of true, making the Trie
     * useful as a pure word-set. Supply an explicit value to associate arbitrary payloads:
     * word → frequency count, word → translation string, prefix → metadata, etc.
     *
     * The type constraint applies to stored values, not to words (words are always strings).
     *
     * Typical use-cases: autocomplete suggestions, spell-check dictionaries,
     * IP routing tables, word frequency counters.
     *
     * @package Wingman\Strux
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     * @template V
     */
    class Trie implements Countable, IteratorAggregate {
        /**
         * Creates a new trie.
         * @param string|null $type The type constraint for stored values.
         */
        public function __construct (?string $type = null) {
            if (isset($type)) $this->type = $type;
            $this->root = $this->makeNode();
        }

        /**
         * The cached normalised (lowercased) form of the enforced type name.
         * Only populated for primitive type enforcement.
         * @var string|null
         */
        private ?string $normalisedType = null;

        /**
         * The root node of the trie.
         * Each node has the shape: ["children" => array<string, node>, "end" => bool, "value" => mixed].
         * @var array
         */
        private array $root;

        /**
         * The total number of words currently stored in the trie.
         * @var int
         */
        private int $size = 0;

        /**
         * Whether the enforced type resolves to a class or interface.
         * Lazily computed on the first invocation of enforceType.
         * @var bool|null
         */
        private ?bool $typeIsClass = null;

        /**
         * Recursively collects all words below the given node into the result array.
         * The traversal is depth-first in child-insertion order.
         * @param array $node The current trie node.
         * @param string $prefix The string accumulated so far.
         * @param array<string, V> $result The accumulator for word => value pairs.
         */
        private function collectWords (array $node, string $prefix, array &$result) : void {
            if ($node["end"]) {
                $result[$prefix] = $node["value"];
            }

            foreach ($node["children"] as $char => $child) {
                $this->collectWords($child, $prefix . $char, $result);
            }
        }

        /**
         * Navigates the trie down from the given node to the terminal character of the
         * given word, removing the end marker and pruning childless dead nodes on the
         * way back up.
         * @param array $node The current node (passed by reference for in-place modification).
         * @param string $word The word to remove.
         * @param int $depth The current character index within the word.
         * @return bool Whether a word was actually removed.
         */
        private function doRemove (array &$node, string $word, int $depth) : bool {
            if ($depth === strlen($word)) {
                if (!$node["end"]) return false;

                $node["end"] = false;
                $node["value"] = null;

                return true;
            }

            $char = $word[$depth];

            if (!isset($node["children"][$char])) return false;

            $removed = $this->doRemove($node["children"][$char], $word, $depth + 1);

            if ($removed && !$node["children"][$char]["end"] && $node["children"][$char]["children"] === []) {
                unset($node["children"][$char]);
            }

            return $removed;
        }

        /**
         * Navigates from the root to the node at the end of the given prefix string.
         * @param string $prefix The prefix to navigate to.
         * @return array|null The node at the end of the prefix, or null if not reachable.
         */
        private function findNode (string $prefix) : ?array {
            $node = $this->root;

            for ($i = 0, $len = strlen($prefix); $i < $len; $i++) {
                $char = $prefix[$i];

                if (!isset($node["children"][$char])) return null;

                $node = $node["children"][$char];
            }

            return $node;
        }

        /**
         * Navigates the trie down from the given node, creating missing children as
         * needed, and sets the end marker and value at the terminal character.
         * @param array $node The current node (passed by reference for in-place modification).
         * @param string $word The word to store.
         * @param int $depth The current character index within the word.
         * @param V $value The value to associate with the word.
         * @return bool Whether a new word was added (false if it already existed and was updated).
         */
        private function insertNode (array &$node, string $word, int $depth, mixed $value) : bool {
            if ($depth === strlen($word)) {
                $isNew = !$node["end"];
                $node["end"] = true;
                $node["value"] = $value;

                return $isNew;
            }

            $char = $word[$depth];

            if (!isset($node["children"][$char])) {
                $node["children"][$char] = $this->makeNode();
            }

            return $this->insertNode($node["children"][$char], $word, $depth + 1, $value);
        }

        /**
         * Creates a new, empty trie node.
         * @return array The node.
         */
        private function makeNode () : array {
            return ["children" => [], "end" => false, "value" => null];
        }

        /**
         * The type that every stored value must conform to, or null for no enforcement.
         * @var string|null
         */
        protected ?string $type = null;

        /**
         * Enforces the trie's type constraint against each given value.
         * The class/interface vs. primitive distinction and the normalised type name are
         * lazily cached after the first invocation.
         * @param mixed ...$items The values to validate.
         * @throws InvalidArgumentException If any value does not conform to the type.
         */
        protected function enforceType (mixed ...$items) : void {
            if (!isset($this->type)) return;

            if (Validator::isSchemaExpression($this->type)) {
                foreach ($items as $i => $item) {
                    Validator::validate($item, $this->type, $i);
                }

                return;
            }

            $this->typeIsClass ??= class_exists($this->type) || interface_exists($this->type);

            if ($this->typeIsClass) {
                foreach ($items as $i => $item) {
                    if (!($item instanceof $this->type)) {
                        throw new InvalidArgumentException("The value (index: $i) doesn't match the type '{$this->type}'.");
                    }
                }

                return;
            }

            $this->normalisedType ??= strtolower($this->type);

            foreach ($items as $i => $item) {
                $valid = match ($this->normalisedType) {
                    "int", "integer" => is_int($item),
                    "float", "double" => is_float($item),
                    "string" => is_string($item),
                    "bool", "boolean" => is_bool($item),
                    "array" => is_array($item),
                    "callable" => is_callable($item),
                    "object" => is_object($item),
                    default => throw new InvalidArgumentException("Unknown type '{$this->type}' for type enforcement.")
                };

                if (!$valid) {
                    $actual = is_object($item) ? get_class($item) : gettype($item);
                    throw new InvalidArgumentException("The value (index: $i) is of type '{$actual}' but expected '{$this->type}'.");
                }
            }
        }

        /**
         * Gets the number of words currently stored in the trie.
         * @return int The word count.
         */
        public function count () : int {
            return $this->getSize();
        }

        /**
         * Invokes the given callback for each word-value pair and returns the trie unchanged.
         * Traversal order is depth-first in child-insertion order.
         * The callback receives the value, word, and trie as its arguments.
         * @param callable(V, string, static): void $callback The callback to invoke.
         * @return static The trie.
         */
        public function each (callable $callback) : static {
            foreach ($this->toArray() as $word => $value) {
                $callback($value, $word, $this);
            }
            return $this;
        }

        /**
         * Determines whether all word-value pairs satisfy the given predicate.
         * @param callable(V, string): bool $predicate A predicate receiving the value and word.
         * @return bool Whether all pairs pass.
         */
        public function every (callable $predicate) : bool {
            foreach ($this->toArray() as $word => $value) {
                if (!$predicate($value, $word)) return false;
            }
            return true;
        }

        /**
         * Finds the first value (in traversal order) that satisfies the given predicate.
         * @param callable(V, string): bool $predicate A predicate receiving the value and word.
         * @return V|null The first matching value, or null if none is found.
         */
        public function find (callable $predicate) : mixed {
            foreach ($this->toArray() as $word => $value) {
                if ($predicate($value, $word)) return $value;
            }
            return null;
        }

        /**
         * Creates a new trie pre-loaded with the given data.
         * The array may be a plain list of words (values default to true) or an associative
         * array of word => value pairs.
         * @param array<int, string>|array<string, V> $data The words or word => value pairs to load.
         * @param string|null $type The type constraint for stored values.
         * @return static The created trie.
         */
        public static function from (array $data, ?string $type = null) : static {
            $trie = new static($type);

            foreach ($data as $key => $value) {
                if (is_int($key)) {
                    $trie->set((string) $value);
                }
                else $trie->set($key, $value);
            }

            return $trie;
        }

        /**
         * Gets the value associated with the given word, or null if the word is not stored.
         * @param string $word The word to look up.
         * @return V|null The associated value, or null.
         */
        public function get (string $word) : mixed {
            $node = $this->findNode($word);
            return ($node !== null && $node["end"]) ? $node["value"] : null;
        }

        /**
         * Gets an iterator that yields word => value pairs in depth-first traversal order.
         * @return Traversable<string, V> The iterator.
         */
        public function getIterator () : Traversable {
            return new ArrayIterator($this->toArray());
        }

        /**
         * Gets all stored words as a plain array in depth-first traversal order.
         * @return string[] The words.
         */
        public function getKeys () : array {
            return array_keys($this->toArray());
        }

        /**
         * Gets the number of words currently stored in the trie.
         * @return int The size.
         */
        public function getSize () : int {
            return $this->size;
        }

        /**
         * Gets the type constraint enforced on stored values, or null if unrestricted.
         * @return string|null The type.
         */
        public function getType () : ?string {
            return $this->type;
        }

        /**
         * Gets all stored values as a plain array in depth-first traversal order.
         * @return V[] The values.
         */
        public function getValues () : array {
            return array_values($this->toArray());
        }

        /**
         * Determines whether the given word is stored in the trie.
         * @param string $word The word to check.
         * @return bool Whether the word exists.
         */
        public function has (string $word) : bool {
            $node = $this->findNode($word);

            return $node !== null && $node['end'];
        }

        /**
         * Determines whether any stored word begins with the given prefix.
         * @param string $prefix The prefix to check.
         * @return bool Whether at least one word starts with the prefix.
         */
        public function hasPrefix (string $prefix) : bool {
            return $this->findNode($prefix) !== null;
        }

        /**
         * Determines whether the trie contains no words.
         * @return bool Whether the trie is empty.
         */
        public function isEmpty () : bool {
            return $this->size === 0;
        }

        /**
         * Determines whether no word-value pairs satisfy the given predicate.
         * Delegates to the inverse of some().
         * @param callable(V, string): bool $predicate A predicate receiving the value and word.
         * @return bool Whether no pairs pass.
         */
        public function none (callable $predicate) : bool {
            return !$this->some($predicate);
        }

        /**
         * Removes the given word from the trie.
         * Removes any now-childless nodes along the path as well.
         * Has no effect if the word is not stored.
         * @param string $word The word to remove.
         * @return static The trie.
         */
        public function remove (string $word) : static {
            if ($this->doRemove($this->root, $word, 0)) {
                $this->size--;
                Emitter::create()->with(word: $word, trie: $this)->emit(Signal::MAP_ENTRY_REMOVED);
            }

            return $this;
        }

        /**
         * Stores the given word in the trie with an optional associated value.
         * If the word is already stored, its value is updated without changing the count.
         * @param string $word The word to store.
         * @param V $value The value to associate with the word. Defaults to true.
         * @return static The trie.
         * @throws InvalidArgumentException If the value fails type enforcement.
         */
        public function set (string $word, mixed $value = true) : static {
            $this->enforceType($value);

            if ($this->insertNode($this->root, $word, 0, $value)) {
                $this->size++;
            }

            Emitter::create()->with(word: $word, value: $value, trie: $this)->emit(Signal::MAP_ENTRY_SET);

            return $this;
        }

        /**
         * Determines whether at least one word-value pair satisfies the given predicate.
         * @param callable(V, string): bool $predicate A predicate receiving the value and word.
         * @return bool Whether any pair passes.
         */
        public function some (callable $predicate) : bool {
            foreach ($this->toArray() as $word => $value) {
                if ($predicate($value, $word)) return true;
            }

            return false;
        }

        /**
         * Invokes the given callback with the trie and returns the trie unchanged.
         * Useful for debugging or side-effectful inspection in a method chain.
         * @param callable(static): void $callback The callback to invoke.
         * @return static The trie.
         */
        public function tap (callable $callback) : static {
            $callback($this);
            return $this;
        }

        /**
         * Gets all word => value pairs as a plain associative array in depth-first traversal order.
         * @return array<string, V> The pairs.
         */
        public function toArray () : array {
            $result = [];
            $this->collectWords($this->root, '', $result);

            return $result;
        }

        /**
         * Returns a new trie containing all words that start with the given prefix.
         * Returns an empty trie if no words match the prefix.
         * @param string $prefix The prefix to search for.
         * @return static A new trie with all matching words.
         */
        public function withPrefix (string $prefix) : static {
            $node = $this->findNode($prefix);

            if ($node === null) return new static($this->type);

            $result = [];
            $this->collectWords($node, $prefix, $result);

            return static::from($result, $this->type);
        }

        /**
         * Creates a new trie with the given type constraint for stored values.
         * @param string $type The type of values the trie will enforce.
         * @return static The trie.
         */
        public static function withType (string $type) : static {
            return new static($type);
        }
    }
?>
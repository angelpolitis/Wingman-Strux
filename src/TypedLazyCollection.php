<?php
    /**
     * Project Name:    Wingman Strux - Typed Lazy Collection
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
    use InvalidArgumentException;
    use Traversable;
    use Wingman\Strux\Bridge\Verix\Validator;

    /**
     * Represents a lazily-evaluated sequence with per-item type enforcement.
     *
     * Concrete subclasses must declare a typed {@see LazyCollection::$type} class property
     * to activate enforcement. Direct instantiation of this class is intentionally
     * disallowed — use a subclass that specifies the target type.
     *
     * Unlike eager collections, type enforcement fires at *output* time (inside getIterator)
     * rather than at input time, because a LazyCollection's source is an opaque callable or
     * iterable. Every item yielded by the pipeline is validated before being passed to the
     * consumer. An InvalidArgumentException is thrown on the first non-conforming item.
     *
     * The type constraint may be any PHP primitive alias, a fully-qualified class or
     * interface name, or a Verix schema expression (requires the Wingman Verix package).
     *
     * @package Wingman\Strux
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     * @template T
     * @extends LazyCollection<T>
     */
    abstract class TypedLazyCollection extends LazyCollection {
        /**
         * The cached normalised (lowercased) form of the enforced type name.
         * Only populated for primitive type enforcement.
         * @var string|null
         */
        private ?string $normalisedType = null;

        /**
         * Whether the enforced type resolves to a class or interface.
         * Lazily computed on the first invocation of enforceType.
         * @var bool|null
         */
        private ?bool $typeIsClass = null;

        /**
         * The type that every item in the sequence must conform to.
         * Must be overridden in the concrete subclass.
         * @var class-string<T>|string
         */
        protected string $type;

        /**
         * Validates the given item against the declared type constraint.
         * The class/interface vs. primitive distinction and the normalised type name are
         * lazily cached after the first invocation.
         * @param mixed $item The item to validate.
         * @param int|string $key The key of the item (used in the exception message).
         * @throws InvalidArgumentException If the item does not conform to the type.
         */
        protected function enforceType (mixed $item, int|string $key = 0) : void {
            if (Validator::isSchemaExpression($this->type)) {
                Validator::validate($item, $this->type, $key);
                return;
            }

            $this->typeIsClass ??= class_exists($this->type) || interface_exists($this->type);

            if ($this->typeIsClass) {
                if (!($item instanceof $this->type)) {
                    throw new InvalidArgumentException("The item (key: $key) doesn't match the type '{$this->type}'.");
                }

                return;
            }

            $this->normalisedType ??= strtolower($this->type);

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
                throw new InvalidArgumentException("The item (key: $key) is of type '{$actual}' but expected '{$this->type}'.");
            }
        }

        /**
         * Gets an iterator that yields items in source order, validating each one against the
         * declared type constraint before passing it to the consumer.
         * Type errors are raised on the first non-conforming item encountered during iteration.
         * @return Traversable<int|string, T> The type-enforcing iterator.
         */
        public function getIterator () : Traversable {
            foreach (parent::getIterator() as $key => $item) {
                $this->enforceType($item, $key);
                yield $key => $item;
            }
        }
    }
?>
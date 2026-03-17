<?php
    /**
     * Project Name:    Wingman Strux - Enum Collection
     * Created by:      Angel Politis
     * Creation Date:   Mar 14 2026
     * Last Modified:   Mar 17 2026
     *
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Strux namespace.
    namespace Wingman\Strux;

    # Import the following classes to the current scope.
    use BackedEnum;
    use InvalidArgumentException;
    use LogicException;
    use UnitEnum;
    use ValueError;

    /**
     * Represents a type-safe collection restricted to cases of a specific PHP 8.1+ enum.
     *
     * EnumCollection extends the base Collection with an additional layer of validation
     * that enforces every item to be a case of a given enum class. For BackedEnum types,
     * the addValue() method allows items to be added by their raw backing value (int or
     * string), and getValues() returns the raw values in collection order. This is ideal for
     * modelling domain lists such as "allowed order states" or "active user roles".
     *
     * Example usage:
     * ```php
     * $states = EnumCollection::forEnum(OrderStatus::class)
     *     ->addValue("pending")
     *     ->addValue("processing");
     * ```
     *
     * @package Wingman\Strux
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     * @template T of UnitEnum
     */
    class EnumCollection extends Collection {
        /**
         * The fully-qualified class name of the enum this collection is restricted to.
         * @var class-string<T>
         */
        private string $enumClass;

        /**
         * Creates a new enum collection for the given enum class.
         *
         * The enum class is validated immediately. The $type property is set before
         * delegating to the parent constructor so that all items passed in $items are
         * checked against the enum type during initialisation.
         *
         * @param class-string<T> $enumClass The fully-qualified name of the enum class.
         * @param array<T> $items The initial items.
         * @param int|null $cap An optional maximum capacity.
         * @param bool $frozen Whether the collection should be immediately frozen.
         * @throws InvalidArgumentException If $enumClass is not a valid enum.
         */
        public function __construct (string $enumClass, array $items = [], ?int $cap = null, bool $frozen = false) {
            if (!enum_exists($enumClass)) {
                throw new InvalidArgumentException("'{$enumClass}' is not a valid enum class.");
            }

            $this->enumClass = $enumClass;
            $this->type = $enumClass;

            parent::__construct($items, null, $cap, $frozen);
        }

        /**
         * Overrides the base factory method so that Collection methods that internally
         * call createInstance() (filter, sort, orderBy, with, without, etc.) produce
         * correctly-typed EnumCollection instances with the same enum class.
         * @param array<T> $items The items to pre-load.
         * @return static A new EnumCollection for the same enum class.
         */
        protected function createInstance (array $items = []) : static {
            return new static($this->enumClass, $items);
        }

        /**
         * Adds one or more enum cases to the collection by their backing value.
         * Only available for BackedEnum types.
         * @param int|string ...$values The raw backing values of the cases to add.
         * @return static The collection.
         * @throws LogicException If the enum is not a BackedEnum.
         * @throws ValueError If any value does not correspond to a valid case.
         */
        public function addValue (int|string ...$values) : static {
            if (!is_a($this->enumClass, BackedEnum::class, true)) {
                throw new LogicException("Cannot add by value: '{$this->enumClass}' is not a BackedEnum.");
            }

            foreach ($values as $value) {
                $this->add($this->enumClass::from($value));
            }

            return $this;
        }

        /**
         * Creates a new empty enum collection for the given enum class.
         * @param class-string<T> $enumClass The fully-qualified name of the enum class.
         * @return static The enum collection.
         */
        public static function forEnum (string $enumClass) : static {
            return new static($enumClass);
        }

        /**
         * Creates a new enum collection from an array of enum cases.
         * The second parameter maps to the enum class name and must not be null.
         * @param array<T> $items The initial enum cases.
         * @param string|null $type The fully-qualified name of the enum class.
         * @param int|null $cap An optional maximum capacity.
         * @param bool $frozen Whether the collection should be immediately frozen.
         * @return static The enum collection.
         * @throws InvalidArgumentException If $type is null.
         */
        public static function from (array $items, ?string $type = null, ?int $cap = null, bool $frozen = false) : static {
            if ($type === null) {
                throw new InvalidArgumentException("EnumCollection::from() requires a valid enum class name as the second argument.");
            }

            return new static($type, $items, $cap, $frozen);
        }

        /**
         * Creates a new enum collection from an array of enum cases.
         * @param class-string<T> $enumClass The fully-qualified name of the enum class.
         * @param array<T> $items The initial enum cases.
         * @return static The enum collection.
         */
        public static function fromEnum (string $enumClass, array $items = []) : static {
            return new static($enumClass, $items);
        }

        /**
         * Gets the fully-qualified class name of the enum this collection is restricted to.
         * @return class-string<T> The enum class name.
         */
        public function getEnumClass () : string {
            return $this->enumClass;
        }

        /**
         * Gets the name property of every case in the collection as a plain array.
         * Works for both UnitEnum and BackedEnum.
         * @return string[] The names.
         */
        public function getNames () : array {
            return array_map(fn (UnitEnum $case) => $case->name, $this->items);
        }

        /**
         * Gets the backing value (for BackedEnum) or name (for UnitEnum) of every case
         * in the collection as a plain array.
         * @return (int|string)[] The values or names.
         */
        public function getValues () : array {
            if (is_a($this->enumClass, BackedEnum::class, true)) {
                return array_map(fn (BackedEnum $case) => $case->value, $this->items);
            }

            return array_map(fn (UnitEnum $case) => $case->name, $this->items);
        }

        /**
         * Determines whether a case with the given backing value is present in the collection.
         * Only available for BackedEnum types.
         * @param int|string $value The raw backing value to look for.
         * @return bool Whether the value is present.
         * @throws LogicException If the enum is not a BackedEnum.
         */
        public function hasValue (int|string $value) : bool {
            if (!is_a($this->enumClass, BackedEnum::class, true)) {
                throw new LogicException("Cannot check by value: '{$this->enumClass}' is not a BackedEnum.");
            }

            foreach ($this->items as $case) {
                if ($case->value === $value) return true;
            }

            return false;
        }
    }
?>
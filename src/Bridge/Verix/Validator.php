<?php
    /**
     * Project Name:    Wingman Strux - Verix Bridge - Validator
     * Created by:      Angel Politis
     * Creation Date:   Mar 16 2026
     * Last Modified:   Mar 17 2026
     *
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Strux.Bridge.Verix namespace.
    namespace Wingman\Strux\Bridge\Verix;

    # Import the following classes to the current scope.
    use InvalidArgumentException;
    use LogicException;
    use Wingman\Verix\Facades\Schema;

    /**
     * Bridges Strux's type enforcement with the Wingman Verix validation package.
     *
     * When Verix is installed, this class delegates schema parsing and validation to
     * {@see Schema}. When Verix is absent and a complex schema
     * expression is encountered, a {@see LogicException} is thrown explaining that Verix
     * must be installed to use schema-based type enforcement.
     *
     * A schema expression is considered complex (Verix-only) if it contains any character
     * that cannot appear in a plain PHP class name or primitive type name: `<`, `>`, `{`,
     * `}`, `[`, `]`. Named schema references registered via {@see Schema::register()} are
     * also detected by their leading `@` sigil (e.g. `@User`). Plain types such as `int`,
     * `string`, or `App\Models\User` continue to be handled entirely by Collection's
     * built-in enforcement and are never routed here.
     *
     * @package Wingman\Strux\Bridge\Verix
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class Validator {
        /**
         * Determines whether the given type expression requires Verix to validate.
         * Returns true if the expression contains characters that cannot appear in a plain
         * PHP class name or primitive type alias, or starts with `@` (a named schema reference).
         * @param string $type The type expression to inspect.
         * @return bool Whether the expression is a Verix schema.
         */
        public static function isSchemaExpression (string $type) : bool {
            return (bool) preg_match('/[<>{}\[\]@]/', $type);
        }

        /**
         * Validates a single value against the given Verix schema expression.
         * Throws a {@see LogicException} if Verix is not installed.
         * Throws an {@see InvalidArgumentException} if the value does not satisfy the schema.
         * @param mixed $value The value to validate.
         * @param string $schema The Verix schema expression.
         * @param int $index The index of the value within the collection (used in error messages).
         * @throws LogicException If the Wingman Verix package is not installed.
         * @throws InvalidArgumentException If the value does not satisfy the schema.
         */
        public static function validate (mixed $value, string $schema, int $index) : void {
            if (!class_exists(Schema::class)) {
                throw new LogicException(
                    "The type '{$schema}' is a Verix schema expression but the Wingman Verix " .
                    "package is not installed. Install Verix to use schema-based type enforcement."
                );
            }

            $result = (new Schema($schema))->validate($value);

            if ($result->valid) return;

            $messages = array_map(fn ($e) => $e->getMessage(), $result->errors);
            $detail = implode("; ", $messages);

            throw new InvalidArgumentException(
                "The item (index: {$index}) does not satisfy the schema '{$schema}': {$detail}"
            );
        }
    }
?>
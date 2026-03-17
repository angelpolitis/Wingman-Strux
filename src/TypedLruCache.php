<?php
    /**
     * Project Name:    Wingman Strux - Typed LRU Cache
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

    /**
     * Represents a fixed-capacity LRU cache with value type enforcement.
     *
     * Concrete subclasses must declare a typed {@see LruCache::$type} class property to
     * activate enforcement. Direct instantiation of this class is intentionally disallowed —
     * use a subclass that specifies the target type.
     *
     * The type constraint may be any PHP primitive alias, a fully-qualified class or
     * interface name, or a Verix schema expression (requires the Wingman Verix package).
     *
     * @package Wingman\Strux
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     * @template V
     * @extends LruCache<V>
     */
    abstract class TypedLruCache extends LruCache {
        /**
         * Creates a new typed LRU cache with the given fixed capacity.
         * The type is not accepted as a constructor parameter — it must be declared as a
         * class property in the concrete subclass.
         * @param int $cap The maximum number of entries the cache can hold. Must be at least 1.
         */
        public function __construct (int $cap) {
            parent::__construct($cap, null);
        }

        /**
         * Creates a new typed LRU cache pre-loaded with the given entries.
         * The type parameter is intentionally omitted — it is declared on the subclass.
         * If no capacity is specified, it defaults to the number of entries (minimum 1).
         * @param array<string|int, V> $data The initial key-value entries (first = oldest).
         * @param int|null $cap The fixed capacity. Defaults to max(1, count($data)).
         * @param string|null $type Ignored; exists only for signature compatibility.
         * @return static The created cache.
         */
        public static function from (array $data, ?int $cap = null, ?string $type = null) : static {
            $cache = new static($cap ?? max(1, count($data)));

            foreach ($data as $key => $value) {
                $cache->put($key, $value);
            }

            return $cache;
        }
    }
?>
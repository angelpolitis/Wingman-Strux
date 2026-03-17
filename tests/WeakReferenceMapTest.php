<?php
    /**
     * Project Name:    Wingman Strux - Weak Reference Map Tests
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
    use stdClass;
    use Wingman\Argus\Attributes\Define;
    use Wingman\Argus\Attributes\Group;
    use Wingman\Argus\Test;
    use Wingman\Strux\Interfaces\MapInterface;
    use Wingman\Strux\WeakReferenceMap;

    /**
     * Tests for the WeakReferenceMap class, covering object-keyed storage, scalar
     * key rejection, core map operations, and interface compliance.
     * @package Wingman\Strux\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class WeakReferenceMapTest extends Test {

        // ─── Key Validation ──────────────────────────────────────────────────────

        #[Group("WeakReferenceMap")]
        #[Define(
            name: "Rejects Scalar Keys",
            description: "Setting a scalar (non-object) key throws an InvalidArgumentException."
        )]
        public function testRejectsScalarKeys () : void {
            $map = new WeakReferenceMap();
            $thrown = false;

            try {
                $map->set("scalar_key", "value");
            } catch (InvalidArgumentException $e) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "A scalar key should throw InvalidArgumentException.");
        }

        // ─── Core CRUD ───────────────────────────────────────────────────────────

        #[Group("WeakReferenceMap")]
        #[Define(
            name: "set() / get() / has() — Object Key Operations",
            description: "set() stores a value by object reference, get() retrieves it, has() confirms presence."
        )]
        public function testObjectKeyOperations () : void {
            $map = new WeakReferenceMap();
            $key = new stdClass();
            $map->set($key, "associated_value");

            $this->assertTrue($map->has($key), "has() should return true for a stored object key.");
            $this->assertTrue($map->get($key) === "associated_value", "get() should return 'associated_value'.");
        }

        #[Group("WeakReferenceMap")]
        #[Define(
            name: "remove() — Removes Entry By Object Key",
            description: "remove() deletes the entry identified by the given object key."
        )]
        public function testRemoveDeletesEntry () : void {
            $map = new WeakReferenceMap();
            $key = new stdClass();
            $map->set($key, "to_remove");
            $map->remove($key);

            $this->assertTrue(!$map->has($key), "Entry should be absent after remove().");
        }

        // ─── Enumeration ─────────────────────────────────────────────────────────

        #[Group("WeakReferenceMap")]
        #[Define(
            name: "getKeys() / getValues() — Enumerate Stored Entries",
            description: "getKeys() returns the stored object keys and getValues() returns their corresponding values."
        )]
        public function testGetKeysAndGetValues () : void {
            $map = new WeakReferenceMap();
            $k1 = new stdClass();
            $k2 = new stdClass();
            $map->set($k1, "one")->set($k2, "two");

            $this->assertTrue(count($map->getKeys()) === 2, "getKeys() should return 2 object keys.");
            $this->assertTrue(count($map->getValues()) === 2, "getValues() should return 2 values.");
        }

        // ─── Interface Compliance ────────────────────────────────────────────────

        #[Group("WeakReferenceMap")]
        #[Define(
            name: "Implements MapInterface",
            description: "WeakReferenceMap implements MapInterface."
        )]
        public function testImplementsMapInterface () : void {
            $this->assertTrue(new WeakReferenceMap() instanceof MapInterface, "WeakReferenceMap must implement MapInterface.");
        }
    }
?>
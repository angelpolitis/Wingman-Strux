<?php
    /**
     * Project Name:    Wingman Strux - Signal
     * Created by:      Angel Politis
     * Creation Date:   Mar 14 2026
     * Last Modified:   Mar 17 2026
     *
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Strux.Enums namespace.
    namespace Wingman\Strux\Enums;

    /**
     * Represents a signal emitted by Strux during node lifecycle operations.
     *
     * Each case maps to a camelCase dot-notation string identifier consumed by Corvus listeners.
     * Cases can be passed directly to `emit()` — coercion to their string value via `->value` is
     * required when the method expects a plain string.
     *
     * @package Wingman\Strux\Enums
     * @author  Angel Politis <info@angelpolitis.com>
     * @since   1.0
     */
    enum Signal : string {

        // ─── Buffer ──────────────────────────────────────────────────────────────

        /**
         * Emitted after an item has been read (and consumed) from a CircularBuffer.
         * Payload: `item` (mixed), `buffer` (CircularBuffer).
         */
        case BUFFER_ITEM_READ = "strux.buffer.item.read";

        /**
         * Emitted after an item has been written into a CircularBuffer.
         * When the buffer is at capacity the oldest item was silently overwritten first.
         * Payload: `item` (mixed), `buffer` (CircularBuffer).
         */
        case BUFFER_ITEM_WRITTEN = "strux.buffer.item.written";

        /**
         * Emitted after a CircularBuffer has been flushed (all items cleared).
         * Payload: `buffer` (CircularBuffer).
         */
        case BUFFER_FLUSHED = "strux.buffer.flushed";

        // ─── Collection ──────────────────────────────────────────────────────────

        /**
         * Emitted after one or more items have been appended to a Collection or SortedList.
         * Payload: `items` (array), `collection` (Collection|SortedList).
         */
        case COLLECTION_ITEM_ADDED = "strux.collection.item.added";

        /**
         * Emitted after an item has been removed from a Collection or SortedList.
         * Payload: `collection` (Collection|SortedList).
         */
        case COLLECTION_ITEM_REMOVED = "strux.collection.item.removed";

        /**
         * Emitted after a Collection or Set has been frozen (made immutable).
         * Payload: `collection` (Collection|Set).
         */
        case COLLECTION_FROZEN = "strux.collection.frozen";

        /**
         * Emitted after a Collection or Set has been unfrozen (made mutable again).
         * Payload: `collection` (Collection|Set).
         */
        case COLLECTION_UNFROZEN = "strux.collection.unfrozen";

        // ─── Graph ───────────────────────────────────────────────────────────────

        /**
         * Emitted after an edge has been added to a Graph or DirectedGraph.
         * Payload: `from` (int|string), `to` (int|string), `attributes` (array), `graph` (Graph).
         */
        case GRAPH_EDGE_ADDED = "strux.graph.edge.added";

        /**
         * Emitted after an edge has been removed from a Graph or DirectedGraph.
         * Payload: `from` (int|string), `to` (int|string), `graph` (Graph).
         */
        case GRAPH_EDGE_REMOVED = "strux.graph.edge.removed";

        /**
         * Emitted after a node has been added to a Graph or DirectedGraph.
         * Payload: `id` (int|string), `value` (mixed), `graph` (Graph).
         */
        case GRAPH_NODE_ADDED = "strux.graph.node.added";

        /**
         * Emitted after a node (and all its incident edges) has been removed from a Graph or DirectedGraph.
         * Payload: `id` (int|string), `graph` (Graph).
         */
        case GRAPH_NODE_REMOVED = "strux.graph.node.removed";

        // ─── Map ─────────────────────────────────────────────────────────────────

        /**
         * Emitted after all entries have been cleared from a map.
         * Payload: `map` (HashMap|WeakReferenceMap).
         */
        case MAP_CLEARED = "strux.map.cleared";

        /**
         * Emitted after a key-value pair has been set or updated in a map.
         * Payload: `key` (mixed), `value` (mixed), `map` (HashMap|BidirectionalMap|MultiMap|LruCache|Trie|WeakReferenceMap).
         */
        case MAP_ENTRY_SET = "strux.map.entry.set";

        /**
         * Emitted after a key-value pair has been removed from a map.
         * Payload: `key` (mixed), `map` (HashMap|BidirectionalMap|MultiMap|LruCache|Trie|WeakReferenceMap).
         */
        case MAP_ENTRY_REMOVED = "strux.map.entry.removed";

        // ─── Node ────────────────────────────────────────────────────────────────

        /**
         * Emitted after a child node has been successfully set via `__set()` or `set()`.
         * Payload: `qualifiedName` (string), `value` (Node), `node` (Node), `root` (Node).
         */
        case NODE_SET = "strux.node.set";

        /**
         * Emitted after a child node has been successfully unset via `__unset()`.
         * Payload: `qualifiedName` (string), `node` (Node), `root` (Node).
         */
        case NODE_UNSET = "strux.node.unset";

        /**
         * Emitted after a node has been detached from its parent via `detach()` or `removeChild()`.
         * Payload: `node` (Node).
         */
        case NODE_REMOVED = "strux.node.removed";

        // ─── Queue ───────────────────────────────────────────────────────────────

        /**
         * Emitted after an item has been dequeued from a Queue or PriorityQueue.
         * Payload: `item` (mixed), `queue` (Queue|PriorityQueue).
         */
        case QUEUE_ITEM_DEQUEUED = "strux.queue.item.dequeued";

        /**
         * Emitted after one or more items have been enqueued in a Queue or PriorityQueue.
         * Payload: `items` (array), `queue` (Queue|PriorityQueue).
         */
        case QUEUE_ITEM_ENQUEUED = "strux.queue.item.enqueued";

        // ─── Set ─────────────────────────────────────────────────────────────────

        /**
         * Emitted after one or more items have been added to a Set.
         * Payload: `items` (array), `set` (Set).
         */
        case SET_ITEM_ADDED = "strux.set.item.added";

        /**
         * Emitted after an item has been removed from a Set.
         * Payload: `item` (mixed), `set` (Set).
         */
        case SET_ITEM_REMOVED = "strux.set.item.removed";

        // ─── Stack ───────────────────────────────────────────────────────────────

        /**
         * Emitted after an item has been popped from a Stack.
         * Payload: `item` (mixed), `stack` (Stack).
         */
        case STACK_ITEM_POPPED = "strux.stack.item.popped";

        /**
         * Emitted after one or more items have been pushed onto a Stack.
         * Payload: `items` (array), `stack` (Stack).
         */
        case STACK_ITEM_PUSHED = "strux.stack.item.pushed";
    }
?>
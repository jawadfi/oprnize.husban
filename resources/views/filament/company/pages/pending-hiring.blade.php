<x-filament-panels::page>
    <div
        x-data="{ draggingAssignmentId: null, justDropped: false, dragOverBranchId: null, dropMessage: '' }"
        x-init="window.pendingHiringDraggingAssignmentId = null"
        x-on:pending-hiring-drag-start.window="draggingAssignmentId = $event.detail.assignmentId"
        x-on:dragend.window="draggingAssignmentId = null; dragOverBranchId = null; window.pendingHiringDraggingAssignmentId = null"
    >
        @if ($this->isClientSide())
            <div class="mb-4">
                <div class="mb-2 text-sm font-semibold text-gray-700">الفروع / Branches</div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($this->getBranchCards() as $branch)
                        @php
                            $isDropTarget = !$branch['is_unassigned'] && $branch['id'] !== null;
                        @endphp
                        <div
                            @class([
                                'rounded-xl border p-3 transition-all',
                                'border-sky-500 bg-sky-50' => $branch['is_active'],
                                'border-gray-200 bg-white' => !$branch['is_active'],
                            ])
                            x-on:click="
                                if (justDropped) {
                                    justDropped = false;
                                    return;
                                }
                                $wire.selectBranchFilter({{ $branch['id'] === null ? 'null' : $branch['id'] }});
                            "
                            x-on:dragover.prevent="if (draggingAssignmentId && {{ $isDropTarget ? 'true' : 'false' }}) $el.classList.add('ring-2','ring-sky-400')"
                            x-on:dragenter.prevent="if (draggingAssignmentId && {{ $isDropTarget ? 'true' : 'false' }}) dragOverBranchId = {{ (int) ($branch['id'] ?? 0) }}"
                            x-on:dragleave.prevent="$el.classList.remove('ring-2','ring-sky-400'); if (dragOverBranchId === {{ (int) ($branch['id'] ?? 0) }}) dragOverBranchId = null"
                            x-on:drop.prevent="
                                $el.classList.remove('ring-2','ring-sky-400');
                                const droppedId = window.pendingHiringGetDraggedAssignment($event) || draggingAssignmentId;
                                console.log('[PendingHiring][DROP] droppedId=', droppedId, 'branchId=', Number({{ (int) ($branch['id'] ?? 0) }}), 'isDropTarget=', {{ $isDropTarget ? 'true' : 'false' }});
                                if (!droppedId || {{ $isDropTarget ? 'false' : 'true' }}) return;
                                dropMessage = 'جاري تعيين الموظف...';
                                $wire.call('assignToBranch', Number(droppedId), Number({{ (int) ($branch['id'] ?? 0) }}))
                                    .then(() => console.log('[PendingHiring][DROP] assignToBranch call completed'))
                                    .catch((e) => console.error('[PendingHiring][DROP] assignToBranch call failed', e));
                                draggingAssignmentId = null;
                                dragOverBranchId = null;
                                window.pendingHiringDraggingAssignmentId = null;
                                dropMessage = 'تم التعيين بنجاح';
                                setTimeout(() => dropMessage = '', 1800);
                                justDropped = true;
                                setTimeout(() => justDropped = false, 250);
                            "
                        >
                            <div class="flex items-center justify-between gap-2">
                                <div class="text-sm font-semibold text-gray-800">{{ $branch['name'] }}</div>
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700">
                                    {{ $branch['count'] }}
                                </span>
                            </div>
                            <div
                                class="mt-1 text-xs"
                                :class="dragOverBranchId === {{ (int) ($branch['id'] ?? 0) }} ? 'text-emerald-600 font-semibold' : 'text-gray-500'"
                            >
                                <span x-show="dragOverBranchId === {{ (int) ($branch['id'] ?? 0) }}">افلت الآن للتعيين في هذا الفرع</span>
                                <span x-show="dragOverBranchId !== {{ (int) ($branch['id'] ?? 0) }}">اضغط للفلترة أو اسحب موظفًا هنا للتعيين</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div x-show="dropMessage" class="mb-2 inline-flex items-center rounded-lg bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700" x-text="dropMessage"></div>

        <div class="mb-3 text-xs text-gray-500">
            Tip: تقدر تسحب الموظف من الاسم أو من عمود Drag ثم تفلته فوق بطاقة الفرع.
        </div>

        <div>
        {{ $this->table }}
        </div>
    </div>

    <script>
        // ── Pending Hiring drag-drop ─────────────────────────────────────────
        // Uses document-level event delegation so bindings survive Livewire
        // DOM morphing.  The _phDragListening flag prevents duplicate listeners
        // on SPA navigation while always keeping rows draggable.

        window.pendingHiringDraggingAssignmentId = null;

        function _phFindAssignmentNode(target) {
            if (!target || !target.closest) return null;
            var node = target.closest('[data-assignment-id]');
            if (!node) {
                var row = target.closest('tr');
                if (row) node = row.querySelector('[data-assignment-id]');
            }
            return node || null;
        }

        function _phMakeRowsDraggable() {
            document.querySelectorAll('[data-assignment-id]').forEach(function (node) {
                var row = node.closest('tr');
                if (row && row.getAttribute('draggable') !== 'true') {
                    row.setAttribute('draggable', 'true');
                    row.style.cursor = 'grab';
                }
            });
        }

        if (!window._phDragListening) {
            window._phDragListening = true;

            document.addEventListener('mousedown', function (e) {
                var node = _phFindAssignmentNode(e.target);
                if (node) {
                    var id = parseInt(node.getAttribute('data-assignment-id'), 10);
                    if (id > 0) {
                        window.pendingHiringDraggingAssignmentId = id;
                        console.log('[PendingHiring][DRAG_PRIME] id=', id);
                    }
                }
            });

            document.addEventListener('dragstart', function (e) {
                var node = _phFindAssignmentNode(e.target);
                if (node) {
                    var id = parseInt(node.getAttribute('data-assignment-id'), 10);
                    if (id > 0) {
                        window.pendingHiringDraggingAssignmentId = id;
                        try {
                            e.dataTransfer.effectAllowed = 'move';
                            e.dataTransfer.setData('text/plain', String(id));
                        } catch (err) {}
                        console.log('[PendingHiring][DRAG_START] id=', id);
                        document.dispatchEvent(new CustomEvent('pending-hiring-drag-start', {
                            detail: { assignmentId: id },
                            bubbles: true
                        }));
                    }
                } else {
                    console.log('[PendingHiring][DRAG_START] no assignment node found, target tag=', e.target && e.target.tagName);
                }
            });

            document.addEventListener('dragend', function () {
                window.pendingHiringDraggingAssignmentId = null;
                console.log('[PendingHiring][DRAG_END]');
            });

            // Keep rows draggable through Livewire DOM morphing
            new MutationObserver(_phMakeRowsDraggable)
                .observe(document.body, { childList: true, subtree: true });
        }

        // Make rows draggable immediately (also runs after each Livewire render
        // via the MutationObserver above)
        _phMakeRowsDraggable();

        // Always overwrite so it reflects latest code on every render
        window.pendingHiringGetDraggedAssignment = function (event) {
            var fromEvent = null;
            if (event && event.dataTransfer) {
                var raw = event.dataTransfer.getData('text/plain');
                var parsed = parseInt(raw, 10);
                if (parsed > 0) fromEvent = parsed;
            }
            console.log('[PendingHiring][DRAG_READ] fromEvent=', fromEvent,
                'fromWindow=', window.pendingHiringDraggingAssignmentId);
            return fromEvent || window.pendingHiringDraggingAssignmentId || null;
        };
    </script>
</x-filament-panels::page>

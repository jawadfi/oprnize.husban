<x-filament-panels::page>
    <div
        x-data="{ draggingAssignmentId: null, justDropped: false, dragOverBranchId: null, dropMessage: '' }"
        x-init="window.pendingHiringDraggingAssignmentId = null; if (window.initPendingHiringRowDrag) { window.initPendingHiringRowDrag($el); }"
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
                                window.pendingHiringClearDraggedAssignment();
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
        if (!window.pendingHiringPrimeDraggedAssignment) {
            window.pendingHiringPrimeDraggedAssignment = function (assignmentId) {
                const id = Number(assignmentId) || null;
                window.pendingHiringDraggingAssignmentId = id;
                console.log('[PendingHiring][DRAG_PRIME] assignmentId=', id);
            };
        }

        if (!window.pendingHiringSetDraggedAssignment) {
            window.pendingHiringSetDraggedAssignment = function (assignmentId, event) {
                const id = Number(assignmentId) || null;
                window.pendingHiringDraggingAssignmentId = id;

                console.log('[PendingHiring][DRAG_START] assignmentId=', id);

                if (event && event.dataTransfer && id) {
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', String(id));
                }

                window.dispatchEvent(new CustomEvent('pending-hiring-drag-start', {
                    detail: { assignmentId: id },
                }));
            };
        }

        if (!window.pendingHiringGetDraggedAssignment) {
            window.pendingHiringGetDraggedAssignment = function (event) {
                const rawFromEvent = event && event.dataTransfer
                    ? event.dataTransfer.getData('text/plain')
                    : '';

                const parsedFromEvent = Number(rawFromEvent);
                const fromEvent = Number.isFinite(parsedFromEvent) && parsedFromEvent > 0
                    ? parsedFromEvent
                    : null;

                console.log('[PendingHiring][DRAG_READ] fromEvent=', fromEvent, 'fromWindow=', window.pendingHiringDraggingAssignmentId);

                return fromEvent || window.pendingHiringDraggingAssignmentId || null;
            };
        }

        if (!window.pendingHiringClearDraggedAssignment) {
            window.pendingHiringClearDraggedAssignment = function () {
                console.log('[PendingHiring][DRAG_END] clearing drag state');
                window.pendingHiringDraggingAssignmentId = null;
                window.dispatchEvent(new Event('dragend'));
            };
        }

        if (!window.initPendingHiringRowDrag) {
            window.initPendingHiringRowDrag = function (rootEl) {
                const bindRows = () => {
                    const rows = rootEl.querySelectorAll('tr.fi-ta-row, .fi-ta-row');

                    rows.forEach((row) => {
                        if (row.dataset.rowDragBound === '1') {
                            return;
                        }

                        const assignmentNode = row.querySelector('[data-assignment-id]');
                        if (!assignmentNode) {
                            return;
                        }

                        const assignmentId = Number(assignmentNode.getAttribute('data-assignment-id'));
                        if (!assignmentId) {
                            return;
                        }

                        row.dataset.rowDragBound = '1';
                        row.setAttribute('draggable', 'true');
                        row.style.cursor = 'grab';

                        row.addEventListener('dragstart', (event) => {
                            window.pendingHiringSetDraggedAssignment(assignmentId, event);
                        });

                        row.addEventListener('dragend', () => {
                            window.pendingHiringClearDraggedAssignment();
                        });
                    });
                };

                bindRows();

                const observer = new MutationObserver(() => bindRows());
                observer.observe(rootEl, { childList: true, subtree: true });
            };
        }
    </script>
</x-filament-panels::page>

<x-filament-panels::page>
    <div
        x-data="{ draggingAssignmentId: null }"
        x-init="window.pendingHiringDraggingAssignmentId = null; if (window.initPendingHiringRowDrag) { window.initPendingHiringRowDrag($el); }"
        x-on:pending-hiring-drag-start.window="draggingAssignmentId = $event.detail.assignmentId"
        x-on:dragend.window="draggingAssignmentId = null; window.pendingHiringDraggingAssignmentId = null"
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
                            wire:click="selectBranchFilter({{ $branch['id'] === null ? 'null' : $branch['id'] }})"
                            x-on:dragover.prevent="if (draggingAssignmentId && {{ $isDropTarget ? 'true' : 'false' }}) $el.classList.add('ring-2','ring-sky-400')"
                            x-on:dragleave.prevent="$el.classList.remove('ring-2','ring-sky-400')"
                            x-on:drop.prevent="
                                $el.classList.remove('ring-2','ring-sky-400');
                                const droppedId = draggingAssignmentId || Number($event.dataTransfer.getData('text/plain')) || window.pendingHiringDraggingAssignmentId;
                                if (!droppedId || {{ $isDropTarget ? 'false' : 'true' }}) return;
                                $wire.assignToBranch(droppedId, {{ (int) ($branch['id'] ?? 0) }});
                                draggingAssignmentId = null;
                                window.pendingHiringDraggingAssignmentId = null;
                            "
                        >
                            <div class="flex items-center justify-between gap-2">
                                <div class="text-sm font-semibold text-gray-800">{{ $branch['name'] }}</div>
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700">
                                    {{ $branch['count'] }}
                                </span>
                            </div>
                            <div class="mt-1 text-xs text-gray-500">
                                اضغط للفلترة أو اسحب موظفًا هنا للتعيين
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="mb-3 text-xs text-gray-500">
            Tip: تقدر تسحب الموظف من الاسم أو من عمود Drag ثم تفلته فوق بطاقة الفرع.
        </div>

        <div>
        {{ $this->table }}
        </div>
    </div>

    <script>
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
                            event.dataTransfer.setData('text/plain', String(assignmentId));
                            window.pendingHiringDraggingAssignmentId = assignmentId;
                            window.dispatchEvent(new CustomEvent('pending-hiring-drag-start', {
                                detail: { assignmentId },
                            }));
                        });

                        row.addEventListener('dragend', () => {
                            window.pendingHiringDraggingAssignmentId = null;
                            window.dispatchEvent(new Event('dragend'));
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

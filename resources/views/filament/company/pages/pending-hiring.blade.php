<x-filament-panels::page>
    <div
        x-data="{ draggingAssignmentId: null }"
        x-init="window.pendingHiringDraggingAssignmentId = null"
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
</x-filament-panels::page>

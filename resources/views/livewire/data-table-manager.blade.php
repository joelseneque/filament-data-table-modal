@php
    $columns = $this->columnObjects;
    $rows = $this->rows;
    $numbers = $this->numbers;
    $selectable = $config['selectable'] ?? false;
    $reorderable = $config['reorderable'] ?? false;
    $dragAndDrop = $config['dragAndDrop'] ?? false;
    $numbering = $config['numbering'] ?? false;
    $disabled = $this->isDisabled();
    $confirmDelete = $config['confirmDelete'] ?? true;
    $colspan = count($columns)
        + ($selectable ? 1 : 0)
        + ($reorderable ? 1 : 0)
        + ($numbering ? 1 : 0)
        + 1; // actions
    $recordClasses = ! empty($config['recordClasses'])
        ? unserialize($config['recordClasses'])->getClosure()
        : null;
@endphp

<div
    class="space-y-4"
    x-effect="panelOpen = $wire.modalOpen"
    x-data="{
        panelOpen: @js($modalOpen),
        editingCell: null,
        editingValue: null,
        startEditing(itemId, field, currentValue) {
            this.editingCell = { itemId, field };
            this.editingValue = currentValue ?? '';
            this.$nextTick(() => {
                const input = this.$refs['input_' + itemId + '_' + field];
                if (input) { input.focus(); if (input.select) input.select(); }
            });
        },
        isEditing(itemId, field) {
            return this.editingCell && this.editingCell.itemId === itemId && this.editingCell.field === field;
        },
        async saveEdit(itemId) {
            if (! this.editingCell) return;
            const field = this.editingCell.field;
            await $wire.updateField(itemId, field, this.editingValue);
            this.cancelEditing();
        },
        cancelEditing() { this.editingCell = null; this.editingValue = null; },
    }"
>
    {{-- Bulk actions bar --}}
    @if($selectable && count($selectedRowIds) > 0 && count($this->bulkActions))
        <div class="flex items-center gap-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2">
            <span class="text-sm text-gray-600 dark:text-gray-300">{{ count($selectedRowIds) }} selected</span>
            @foreach($this->bulkActions as $bulk)
                <button
                    type="button"
                    wire:click="runBulkAction(@js($bulk['name']))"
                    @if($bulk['requires_confirmation'] ?? false) onclick="return confirm('Apply this action to the selected rows?')" @endif
                    class="inline-flex items-center gap-1 px-3 py-1 text-sm font-medium rounded-md text-{{ $bulk['color'] }}-600 hover:text-{{ $bulk['color'] }}-800 dark:text-{{ $bulk['color'] }}-400"
                >
                    {{ $bulk['label'] }}
                </button>
            @endforeach
        </div>
    @endif

    {{-- Search --}}
    @if($config['searchable'] ?? false)
        <input
            type="search"
            wire:model.live.debounce.300ms="search"
            placeholder="Search…"
            class="w-full sm:w-64 px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
        />
    @endif

    {{-- Table --}}
    <div class="rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden">
        <table class="w-full divide-y divide-gray-300 dark:divide-gray-600">
            <thead class="bg-gray-50 dark:bg-gray-800/60">
                <tr>
                    @if($selectable)
                        <th class="px-3 py-2 w-10 text-center">
                            <input type="checkbox" wire:click="toggleSelectAll"
                                @checked(count($selectedRowIds) > 0 && count($selectedRowIds) === count($rows))
                                class="rounded border-gray-300 dark:border-gray-600" />
                        </th>
                    @endif
                    @if($reorderable)
                        <th class="px-1 py-2 w-10"></th>
                    @endif
                    @if($numbering)
                        <th class="px-3 py-2 w-10 text-left text-xs font-medium uppercase text-gray-700 dark:text-gray-300">#</th>
                    @endif
                    @foreach($columns as $column)
                        @continue(! $column->isVisible())
                        <th class="px-3 py-2 text-xs font-medium uppercase text-gray-700 dark:text-gray-300 {{ $column->getAlignment()->textClass() }} {{ $column->getWidth() }}">
                            {{ $column->getLabel() }}
                        </th>
                    @endforeach
                    <th class="px-3 py-2 text-right text-xs font-medium uppercase text-gray-700 dark:text-gray-300">Actions</th>
                </tr>
            </thead>

            <tbody
                class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700"
                @if($dragAndDrop && ! $disabled)
                    x-data="dtmSortable({ disabled: @js($config['parentChild'] ?? false) })"
                    data-dtm-sortable
                @endif
            >
                @forelse($rows as $row)
                    @php
                        $rid = $row->id;
                        $rowClasses = $recordClasses ? ($recordClasses($row) ?? '') : '';
                    @endphp
                    <tr
                        wire:key="dtm-row-{{ $rid }}"
                        data-id="{{ $rid }}"
                        class="hover:bg-gray-50 dark:hover:bg-gray-800/60 {{ $row->isSummary() ? 'bg-primary-50 dark:bg-primary-900/20' : '' }} {{ $rowClasses }}"
                        @if($row->hasParent() && ! $row->isSummary()) style="background-color: rgba(0,0,0,0.02);" @endif
                    >
                        @if($selectable)
                            <td class="px-3 py-2 text-center">
                                <input type="checkbox" value="{{ $rid }}" wire:model.live="selectedRowIds"
                                    class="rounded border-gray-300 dark:border-gray-600" />
                            </td>
                        @endif

                        @if($reorderable)
                            <td class="px-1 py-2 text-center">
                                <div class="flex flex-col items-center gap-0">
                                    @if($dragAndDrop)
                                        <span class="dtm-drag-handle cursor-move text-gray-400 hover:text-gray-600" title="Drag to reorder">⠿</span>
                                    @else
                                        <button type="button" wire:click="moveUp(@js($rid))" @disabled($disabled)
                                            class="p-0 text-gray-400 hover:text-gray-600 disabled:opacity-30" title="Move up">
                                            <x-filament::icon icon="heroicon-o-chevron-up" class="h-3 w-3" />
                                            <span class="sr-only">Move up</span>
                                        </button>
                                        <button type="button" wire:click="moveDown(@js($rid))" @disabled($disabled)
                                            class="p-0 text-gray-400 hover:text-gray-600 disabled:opacity-30" title="Move down">
                                            <x-filament::icon icon="heroicon-o-chevron-down" class="h-3 w-3" />
                                            <span class="sr-only">Move down</span>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        @endif

                        @if($numbering)
                            <td class="px-3 py-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $numbers[(string) $rid] ?? '' }}
                            </td>
                        @endif

                        @foreach($columns as $column)
                            @continue(! $column->isVisible())
                            @php $field = $column->getName(); @endphp
                            <td class="px-3 py-2 text-sm text-gray-900 dark:text-gray-100 {{ $column->getAlignment()->textClass() }}">
                                @if($column->isInlineToggle())
                                    <button type="button" wire:click="toggleColumn(@js($rid), @js($field))" @disabled($disabled)
                                        class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors {{ $row->get($field) ? 'bg-primary-600' : 'bg-gray-200 dark:bg-gray-600' }}"
                                        role="switch" aria-checked="{{ $row->get($field) ? 'true' : 'false' }}">
                                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition {{ $row->get($field) ? 'translate-x-4' : 'translate-x-0.5' }}"></span>
                                    </button>
                                @elseif($column->isInlineEditable() && ! $disabled)
                                    <template x-if="isEditing(@js($rid), @js($field))">
                                        <input
                                            type="{{ $column->getInlineInputType() }}"
                                            @if($column->getInlineInputType() === 'number') step="0.01" @endif
                                            x-model="editingValue"
                                            x-ref="input_{{ $rid }}_{{ $field }}"
                                            @blur="saveEdit(@js($rid))"
                                            @keydown.enter="saveEdit(@js($rid))"
                                            @keydown.escape="cancelEditing()"
                                            class="w-full px-2 py-1 text-sm border border-primary-500 rounded dark:bg-gray-700 dark:text-gray-100"
                                        />
                                    </template>
                                    <template x-if="! isEditing(@js($rid), @js($field))">
                                        <span @click="startEditing(@js($rid), @js($field), {{ \Illuminate\Support\Js::from($column->getState($row)) }})"
                                            class="cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 px-2 py-1 -mx-2 -my-1 rounded block min-h-6">
                                            @if($row->hasParent() && $loop->first)
                                                <span class="ml-2">↳ </span>
                                            @endif
                                            @if($column->rendersRawHtml()) {!! $column->formatState($row) !!} @else {{ $column->formatState($row) }} @endif
                                        </span>
                                    </template>
                                @elseif($column->getOpensModal() && ! $disabled)
                                    <div wire:click="openEditModal(@js($rid))" x-on:click="panelOpen = true" class="cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 px-2 py-1 -mx-2 -my-1 rounded min-h-6" title="Click to edit">
                                        @if($column->rendersRawHtml()) {!! $column->formatState($row) !!} @else {{ $column->formatState($row) }} @endif
                                    </div>
                                @else
                                    @if($column->rendersRawHtml()) {!! $column->formatState($row) !!} @else {{ $column->formatState($row) }} @endif
                                @endif
                            </td>
                        @endforeach

                        {{-- Row actions --}}
                        <td class="px-3 py-2 text-right whitespace-nowrap space-x-2">
                            @foreach($this->rowActions as $action)
                                @if($this->rowActionVisible($action['name'], $row))
                                    @php
                                        $click = match($action['type']) {
                                            'edit' => 'openEditModal(' . \Illuminate\Support\Js::from($rid) . ')',
                                            'delete' => 'deleteRow(' . \Illuminate\Support\Js::from($rid) . ')',
                                            'duplicate' => 'duplicateRow(' . \Illuminate\Support\Js::from($rid) . ')',
                                            default => 'runRowAction(' . \Illuminate\Support\Js::from($action['name']) . ', ' . \Illuminate\Support\Js::from($rid) . ')',
                                        };
                                    @endphp
                                    <button
                                        type="button"
                                        wire:click="{{ $click }}"
                                        @if($action['type'] === 'edit') x-on:click="panelOpen = true" @endif
                                        @disabled($disabled)
                                        @if(($action['requires_confirmation'] ?? false) && $confirmDelete)
                                            onclick="return confirm({{ \Illuminate\Support\Js::from($action['confirmation_message'] ?? 'Are you sure?') }})"
                                        @endif
                                        @class([
                                            'text-'.$action['color'].'-600 hover:text-'.$action['color'].'-800 dark:text-'.$action['color'].'-400 text-sm',
                                            'inline-flex items-center justify-center rounded-lg p-1.5 hover:bg-gray-100 dark:hover:bg-white/5' => ! empty($action['icon']),
                                        ])
                                        title="{{ $action['label'] }}"
                                    >
                                        @if(! empty($action['icon']))
                                            <x-filament::icon :icon="$action['icon']" class="h-4 w-4" />
                                            <span class="sr-only">{{ $action['label'] }}</span>
                                        @else
                                            {{ $action['label'] }}
                                        @endif
                                    </button>
                                @endif
                            @endforeach
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $colspan }}" class="px-3 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            {{ $config['emptyStateHeading'] ?? 'No items yet. Click “Add” to get started.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Add button --}}
    @unless($disabled)
        <div>
            <button type="button" wire:click="openCreateModal" x-on:click="panelOpen = true"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg transition-colors">
                <span class="text-base leading-none">+</span> Add
            </button>
        </div>
    @endunless

    {{-- Modal --}}
    @include('data-table-modal::livewire.partials.modal')

    {{-- Renders modals for any Filament actions used inside the modal schema --}}
    <x-filament-actions::modals />
</div>

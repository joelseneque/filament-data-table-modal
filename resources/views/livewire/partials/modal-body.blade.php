<div class="bg-primary-600 dark:bg-primary-700 px-6 py-5">
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-white">
            {{ $heading ?? ($editingRowId ? 'Edit' : 'Add') }}
        </h2>
        <button type="button" wire:click="closeModal" x-on:click="panelOpen = false" class="text-primary-100 hover:text-white">
            <span class="text-xl leading-none">&times;</span>
        </button>
    </div>
</div>

<div class="relative flex-1 px-6 py-6 overflow-y-auto">
    {{-- Shown while the panel has slid in but the form state is still loading. --}}
    <div
        wire:loading.flex
        wire:target="openEditModal, openCreateModal"
        class="absolute inset-0 z-10 items-center justify-center bg-white/70 dark:bg-gray-800/70"
    >
        <x-filament::loading-indicator class="h-8 w-8 text-primary-600" />
    </div>

    <form wire:submit.prevent="save">
        {{ $this->form }}
    </form>
</div>

<div class="flex items-center justify-end gap-3 border-t border-gray-200 dark:border-gray-700 px-6 py-4 bg-gray-50 dark:bg-gray-900">
    <button type="button" wire:click="closeModal" x-on:click="panelOpen = false"
        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
        Cancel
    </button>
    <button type="button" wire:click="save"
        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg">
        {{ $editingRowId ? 'Update' : 'Add' }}
    </button>
</div>

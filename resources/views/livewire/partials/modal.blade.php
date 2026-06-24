@php
    $slideOver = $config['slideOver'] ?? true;
    $width = $config['modalWidth'] ?? '2xl';
    $maxWidthClass = $width === 'screen' ? 'max-w-full' : 'max-w-' . $width;
    $heading = $config['modalHeading'] ?? null;
@endphp

{{--
    Always rendered so the panel can slide/fade in the instant `panelOpen` flips
    (set optimistically on the open buttons) — the Livewire round trip then fills
    the form. `panelOpen` lives in the component root's x-data and is kept in sync
    with the server `modalOpen` via x-effect, so closing is authoritative.

    Teleported to <body> so the modal's <form> and its `required` controls live
    OUTSIDE the host page's own <form>. Nested forms are invalid HTML (the inner
    </form> closes the outer one early, breaking the host's submit button), and a
    hidden required control inside the host form blocks native submit validation
    ("invalid form control is not focusable"). The <template> is also inert at
    parse time, so the wrapping alone prevents the form-nesting breakage; the
    teleport then keeps it clear of the host form at runtime. `panelOpen`/`$wire`
    scope is preserved by x-teleport.
--}}
<template x-teleport="body">
<div x-show="panelOpen" x-cloak class="fixed inset-0 z-50 overflow-hidden" style="display: none;">
    {{-- Backdrop --}}
    <div
        x-show="panelOpen"
        x-transition:enter="transition-opacity ease-linear duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-300"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-on:click="panelOpen = false"
        wire:click="closeModal"
        class="absolute inset-0 bg-gray-500/75 dark:bg-gray-900/80"
    ></div>

    @if($slideOver)
        <div class="fixed inset-y-0 right-0 flex max-w-full pl-10">
            <div
                x-show="panelOpen"
                x-transition:enter="transform transition ease-in-out duration-300"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in-out duration-300"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
                class="w-screen {{ $maxWidthClass }}"
            >
                <div class="flex h-full flex-col bg-white dark:bg-gray-800 shadow-xl">
                    @include('data-table-modal::livewire.partials.modal-body', ['heading' => $heading])
                </div>
            </div>
        </div>
    @else
        <div class="flex min-h-full items-center justify-center p-4">
            <div
                x-show="panelOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="w-full {{ $maxWidthClass }} rounded-xl bg-white dark:bg-gray-800 shadow-xl overflow-hidden flex flex-col max-h-[90vh]"
            >
                @include('data-table-modal::livewire.partials.modal-body', ['heading' => $heading])
            </div>
        </div>
    @endif
</div>
</template>

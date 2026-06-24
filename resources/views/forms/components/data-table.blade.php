@php
    $statePath = $getStatePath();
    $mountData = $getMountData();
    $wireId = $this->getId();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{}"
        @data-table:totals.window="
            if ($event.detail?.statePath !== @js($statePath)) return;
            const component = Livewire.find(@js($wireId));
            if (! component) return;
            const values = $event.detail.values ?? {};
            Object.entries(values).forEach(([path, value]) => component.set(path, value));
        "
        @data-table:sync.window="
            if ($event.detail?.statePath !== @js($statePath)) return;
            const component = Livewire.find(@js($wireId));
            if (! component) return;
            component.set(@js($statePath), $event.detail.items ?? []);
        "
    >
        @livewire(
            'data-table-modal-manager',
            $mountData,
            key('dtm-' . $statePath)
        )
    </div>
</x-dynamic-component>

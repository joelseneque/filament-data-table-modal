import Sortable from 'sortablejs'

// Alpine component that turns a <tbody data-dtm-sortable> into a drag-and-drop
// reorderable list. On drop it reads the new row id order and calls the Livewire
// component's reorder() method, which re-threads children under parents.
document.addEventListener('alpine:init', () => {
    window.Alpine.data('dtmSortable', () => ({
        init() {
            Sortable.create(this.$el, {
                handle: '.dtm-drag-handle',
                animation: 150,
                ghostClass: 'opacity-50',
                onEnd: () => {
                    const ids = Array.from(this.$el.querySelectorAll('[data-id]'))
                        .map((el) => el.getAttribute('data-id'))

                    this.$wire.reorder(ids)
                },
            })
        },
    }))
})

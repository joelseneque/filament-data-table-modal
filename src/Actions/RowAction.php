<?php

declare(strict_types=1);

namespace Joelseneque\DataTableModal\Actions;

use Closure;
use Illuminate\Support\Str;
use Joelseneque\DataTableModal\DataSource\Row;
use Laravel\SerializableClosure\SerializableClosure;

/**
 * A per-row action button. Three built-in types (edit, delete, duplicate) are
 * handled entirely inside the DataTableManager component. Custom actions either
 * dispatch a Livewire event the host page listens for, or run a serialized
 * closure inside the component.
 *
 * Note: closure handlers are serialized into the component's state (signed by
 * Livewire). Use ->dispatch() instead if you prefer to keep logic on the host
 * page. Closures must be serializable (no bound non-serializable objects).
 */
class RowAction
{
    protected ?string $label = null;

    protected ?string $icon = null;

    protected ?string $color = null;

    protected bool $requiresConfirmation = false;

    protected ?string $confirmationMessage = null;

    protected bool|Closure $visible = true;

    protected ?Closure $handler = null;

    protected ?string $dispatchEvent = null;

    final public function __construct(
        protected string $name,
        protected string $type = 'custom',
    ) {}

    public static function make(string $name): static
    {
        return new static($name);
    }

    public static function edit(): static
    {
        return (new static('edit', 'edit'))
            ->label(__('data-table-modal::data-table-modal.actions.edit'))
            ->icon('heroicon-m-pencil-square')
            ->color('primary');
    }

    public static function delete(): static
    {
        return (new static('delete', 'delete'))
            ->label(__('data-table-modal::data-table-modal.actions.delete'))
            ->icon('heroicon-m-trash')
            ->color('danger')
            ->requiresConfirmation();
    }

    public static function duplicate(): static
    {
        return (new static('duplicate', 'duplicate'))
            ->label(__('data-table-modal::data-table-modal.actions.duplicate'))
            ->icon('heroicon-m-document-duplicate')
            ->color('gray');
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function color(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function requiresConfirmation(bool $condition = true, ?string $message = null): static
    {
        $this->requiresConfirmation = $condition;
        $this->confirmationMessage = $message;

        return $this;
    }

    public function visible(bool|Closure $condition = true): static
    {
        $this->visible = $condition;

        return $this;
    }

    public function action(Closure $handler): static
    {
        $this->handler = $handler;

        return $this;
    }

    public function dispatch(string $event): static
    {
        $this->dispatchEvent = $event;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isVisibleFor(Row $row): bool
    {
        return $this->visible instanceof Closure
            ? (bool) ($this->visible)($row)
            : $this->visible;
    }

    /**
     * Serializable descriptor for handing to the Livewire component at mount.
     *
     * @return array<string, mixed>
     */
    public function toDescriptor(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'label' => $this->label ?? Str::headline($this->name),
            'icon' => $this->icon,
            'color' => $this->color ?? 'gray',
            'requires_confirmation' => $this->requiresConfirmation,
            'confirmation_message' => $this->confirmationMessage,
            'dispatch_event' => $this->dispatchEvent,
            'visible' => $this->visible instanceof Closure
                ? serialize(new SerializableClosure($this->visible))
                : $this->visible,
            'handler' => $this->handler !== null
                ? serialize(new SerializableClosure($this->handler))
                : null,
        ];
    }
}

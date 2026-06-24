<?php

declare(strict_types=1);

namespace Joelseneque\DataTableModal\Actions;

use Closure;
use Illuminate\Support\Str;
use Laravel\SerializableClosure\SerializableClosure;

/**
 * An action that operates on the set of selected rows. A built-in `delete` type
 * is handled inside the component; custom bulk actions run a serialized closure
 * receiving the selected ids and the DataSource, or dispatch a host event.
 */
class BulkAction
{
    protected ?string $label = null;

    protected ?string $icon = null;

    protected ?string $color = null;

    protected bool $requiresConfirmation = false;

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

    public static function delete(): static
    {
        return (new static('delete', 'delete'))
            ->label(__('data-table-modal::data-table-modal.actions.delete_selected'))
            ->icon('heroicon-m-trash')
            ->color('danger')
            ->requiresConfirmation();
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

    public function requiresConfirmation(bool $condition = true): static
    {
        $this->requiresConfirmation = $condition;

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

    /**
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
            'dispatch_event' => $this->dispatchEvent,
            'handler' => $this->handler !== null
                ? serialize(new SerializableClosure($this->handler))
                : null,
        ];
    }
}

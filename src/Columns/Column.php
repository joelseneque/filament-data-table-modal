<?php

declare(strict_types=1);

namespace Joelseneque\DataTableModal\Columns;

use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Joelseneque\DataTableModal\Columns\Enums\Alignment;
use Joelseneque\DataTableModal\Columns\Enums\ColumnFormat;
use Joelseneque\DataTableModal\DataSource\Row;
use Laravel\SerializableClosure\SerializableClosure;

/**
 * Describes a single table column: how its value is read, formatted, aligned,
 * and whether it can be edited inline (text/number), toggled inline (boolean),
 * or opens the modal for rich editing.
 */
class Column
{
    protected ?string $label = null;

    protected bool $inlineEditable = false;

    protected bool $inlineToggle = false;

    protected bool $opensModal = false;

    protected ColumnFormat $format = ColumnFormat::Text;

    protected Alignment $alignment = Alignment::Left;

    protected ?string $width = null;

    protected bool|Closure $visible = true;

    protected ?Closure $formatStateUsing = null;

    protected ?Closure $getStateUsing = null;

    protected string $currencySymbol = '$';

    protected int $decimals = 2;

    protected ?string $placeholder = '—';

    final public function __construct(protected string $name) {}

    public static function make(string $name): static
    {
        return new static($name);
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function inlineEditable(bool $condition = true): static
    {
        $this->inlineEditable = $condition;

        return $this;
    }

    public function inlineToggle(bool $condition = true): static
    {
        $this->inlineToggle = $condition;
        $this->format = ColumnFormat::Boolean;

        return $this;
    }

    public function opensModal(bool $condition = true): static
    {
        $this->opensModal = $condition;

        return $this;
    }

    public function format(ColumnFormat $format): static
    {
        $this->format = $format;

        return $this;
    }

    public function align(Alignment $alignment): static
    {
        $this->alignment = $alignment;

        return $this;
    }

    public function width(string $width): static
    {
        $this->width = $width;

        return $this;
    }

    public function visible(bool|Closure $condition = true): static
    {
        $this->visible = $condition;

        return $this;
    }

    public function formatStateUsing(Closure $callback): static
    {
        $this->formatStateUsing = $callback;

        return $this;
    }

    public function getStateUsing(Closure $callback): static
    {
        $this->getStateUsing = $callback;

        return $this;
    }

    public function currency(string $symbol = '$', int $decimals = 2): static
    {
        $this->format = ColumnFormat::Currency;
        $this->currencySymbol = $symbol;
        $this->decimals = $decimals;

        return $this;
    }

    public function placeholder(?string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label ?? Str::headline($this->name);
    }

    public function isInlineEditable(): bool
    {
        return $this->inlineEditable;
    }

    public function isInlineToggle(): bool
    {
        return $this->inlineToggle;
    }

    public function getOpensModal(): bool
    {
        return $this->opensModal;
    }

    public function getFormat(): ColumnFormat
    {
        return $this->format;
    }

    public function getAlignment(): Alignment
    {
        return $this->alignment;
    }

    public function getWidth(): ?string
    {
        return $this->width;
    }

    public function isVisible(): bool
    {
        return $this->visible instanceof Closure
            ? (bool) ($this->visible)()
            : $this->visible;
    }

    public function getInlineInputType(): string
    {
        return in_array($this->format, [ColumnFormat::Number, ColumnFormat::Currency], true)
            ? 'number'
            : 'text';
    }

    public function getState(Row $row): mixed
    {
        if ($this->getStateUsing !== null) {
            return ($this->getStateUsing)($row->get($this->name), $row);
        }

        return $row->get($this->name);
    }

    /**
     * Render the display value for the cell. Returns a string (may contain HTML
     * for the Html/Badge formats — the view echoes those unescaped).
     */
    public function formatState(Row $row): string
    {
        $state = $this->getState($row);

        if ($this->formatStateUsing !== null) {
            return (string) ($this->formatStateUsing)($state, $row);
        }

        if ($state === null || $state === '') {
            return (string) ($this->placeholder ?? '');
        }

        return match ($this->format) {
            ColumnFormat::Currency => $this->currencySymbol.number_format((float) $state, $this->decimals),
            ColumnFormat::Number => number_format((float) $state, $this->decimals),
            ColumnFormat::Boolean => $state ? '✓' : '',
            ColumnFormat::Date => $this->formatDate($state),
            default => (string) $state,
        };
    }

    public function rendersRawHtml(): bool
    {
        return in_array($this->format, [ColumnFormat::Html, ColumnFormat::Badge], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function toDescriptor(): array
    {
        return [
            'name' => $this->name,
            'label' => $this->getLabel(),
            'inline_editable' => $this->inlineEditable,
            'inline_toggle' => $this->inlineToggle,
            'opens_modal' => $this->opensModal,
            'format' => $this->format->value,
            'alignment' => $this->alignment->value,
            'width' => $this->width,
            'currency_symbol' => $this->currencySymbol,
            'decimals' => $this->decimals,
            'placeholder' => $this->placeholder,
            'visible' => $this->visible instanceof Closure
                ? serialize(new SerializableClosure($this->visible))
                : $this->visible,
            'format_state_using' => $this->formatStateUsing !== null
                ? serialize(new SerializableClosure($this->formatStateUsing))
                : null,
            'get_state_using' => $this->getStateUsing !== null
                ? serialize(new SerializableClosure($this->getStateUsing))
                : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $descriptor
     */
    public static function fromDescriptor(array $descriptor): static
    {
        $column = new static($descriptor['name']);
        $column->label = $descriptor['label'] ?? null;
        $column->inlineEditable = $descriptor['inline_editable'] ?? false;
        $column->inlineToggle = $descriptor['inline_toggle'] ?? false;
        $column->opensModal = $descriptor['opens_modal'] ?? false;
        $column->format = ColumnFormat::from($descriptor['format'] ?? 'text');
        $column->alignment = Alignment::from($descriptor['alignment'] ?? 'left');
        $column->width = $descriptor['width'] ?? null;
        $column->currencySymbol = $descriptor['currency_symbol'] ?? '$';
        $column->decimals = $descriptor['decimals'] ?? 2;
        $column->placeholder = $descriptor['placeholder'] ?? '—';

        $visible = $descriptor['visible'] ?? true;
        $column->visible = is_string($visible)
            ? unserialize($visible)->getClosure()
            : $visible;

        if (! empty($descriptor['format_state_using'])) {
            $column->formatStateUsing = unserialize($descriptor['format_state_using'])->getClosure();
        }

        if (! empty($descriptor['get_state_using'])) {
            $column->getStateUsing = unserialize($descriptor['get_state_using'])->getClosure();
        }

        return $column;
    }

    protected function formatDate(mixed $state): string
    {
        try {
            return Carbon::parse($state)->format('d/m/Y');
        } catch (\Throwable) {
            return (string) $state;
        }
    }
}

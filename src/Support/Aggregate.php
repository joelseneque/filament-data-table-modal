<?php

declare(strict_types=1);

namespace Joelseneque\DataTableModal\Support;

use Closure;
use Joelseneque\DataTableModal\DataSource\Row;
use Laravel\SerializableClosure\SerializableClosure;

/**
 * A single computed value derived from the table's rows, written into a field on
 * the parent Filament form (e.g. a subtotal/GST/total). Generalizes the squared
 * "update-quote-totals" dispatch.
 */
class Aggregate
{
    protected ?Closure $where = null;

    protected ?string $dispatchTo = null;

    protected int $round = 2;

    final public function __construct(
        protected string $type,
        protected ?string $column = null,
        protected ?Closure $custom = null,
    ) {}

    public static function sum(string $column): static
    {
        return new static('sum', $column);
    }

    public static function avg(string $column): static
    {
        return new static('avg', $column);
    }

    public static function count(): static
    {
        return new static('count');
    }

    public static function custom(Closure $callback): static
    {
        return new static('custom', null, $callback);
    }

    public function where(Closure $callback): static
    {
        $this->where = $callback;

        return $this;
    }

    public function dispatchTo(string $statePath): static
    {
        $this->dispatchTo = $statePath;

        return $this;
    }

    public function round(int $precision): static
    {
        $this->round = $precision;

        return $this;
    }

    public function getDispatchTo(): ?string
    {
        return $this->dispatchTo;
    }

    /**
     * @param  array<int, Row>  $rows
     */
    public function compute(array $rows): float|int
    {
        if ($this->where !== null) {
            $rows = array_values(array_filter($rows, fn (Row $row): bool => (bool) ($this->where)($row)));
        }

        if ($this->type === 'custom' && $this->custom !== null) {
            $value = ($this->custom)($rows);

            return is_float($value) ? round($value, $this->round) : $value;
        }

        if ($this->type === 'count') {
            return count($rows);
        }

        $values = array_map(fn (Row $row) => (float) ($row->get($this->column) ?? 0), $rows);

        $result = match ($this->type) {
            'sum' => array_sum($values),
            'avg' => $values === [] ? 0 : array_sum($values) / count($values),
            default => 0,
        };

        return round($result, $this->round);
    }

    /**
     * @return array<string, mixed>
     */
    public function toDescriptor(): array
    {
        return [
            'type' => $this->type,
            'column' => $this->column,
            'dispatch_to' => $this->dispatchTo,
            'round' => $this->round,
            'where' => $this->where !== null ? serialize(new SerializableClosure($this->where)) : null,
            'custom' => $this->custom !== null ? serialize(new SerializableClosure($this->custom)) : null,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Joelseneque\DataTableModal\Support;

use Joelseneque\DataTableModal\DataSource\Row;

/**
 * A collection of aggregates computed after every mutation and pushed into the
 * parent form's fields via a browser event (the generalized totals bridge).
 */
class Footer
{
    /**
     * @param  array<int, Aggregate>  $aggregates
     */
    public function __construct(protected array $aggregates = []) {}

    public static function make(): static
    {
        return new static;
    }

    public function aggregate(Aggregate $aggregate): static
    {
        $this->aggregates[] = $aggregate;

        return $this;
    }

    /**
     * @param  array<int, Row>  $rows
     * @return array<string, mixed> map of parent-form state path => computed value
     */
    public function compute(array $rows): array
    {
        $values = [];

        foreach ($this->aggregates as $aggregate) {
            $path = $aggregate->getDispatchTo();
            if ($path !== null) {
                $values[$path] = $aggregate->compute($rows);
            }
        }

        return $values;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function toDescriptor(): array
    {
        return array_map(fn (Aggregate $aggregate): array => $aggregate->toDescriptor(), $this->aggregates);
    }
}

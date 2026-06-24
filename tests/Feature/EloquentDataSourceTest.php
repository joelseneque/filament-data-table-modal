<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Joelseneque\DataTableModal\DataSource\EloquentDataSource;

class TestLineItem extends Model
{
    protected $table = 'test_line_items';

    public $timestamps = false;

    protected $guarded = [];
}

beforeEach(function () {
    Schema::create('test_line_items', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('owner_id')->nullable();
        $table->unsignedBigInteger('parent_id')->nullable();
        $table->integer('order')->default(0);
        $table->string('name')->nullable();
        $table->decimal('price', 10, 2)->nullable();
        $table->boolean('is_summary')->default(false);
    });
});

function eloquentSource(): EloquentDataSource
{
    return new EloquentDataSource(
        model: TestLineItem::class,
        ownerKey: 'owner_id',
        ownerId: 1,
        orderColumn: 'order',
        parentColumn: 'parent_id',
        summaryColumn: 'is_summary',
    );
}

function rowNames(EloquentDataSource $s): string
{
    return implode(',', array_map(fn ($r) => $r->get('name'), $s->all()));
}

it('persists created rows scoped to the owner', function () {
    $s = eloquentSource();
    $s->create(['name' => 'A']);
    $s->create(['name' => 'B']);

    expect(TestLineItem::where('owner_id', 1)->count())->toBe(2)
        ->and(rowNames($s))->toBe('A,B');
});

it('does not see other owners rows', function () {
    eloquentSource()->create(['name' => 'mine']);
    TestLineItem::create(['owner_id' => 99, 'name' => 'theirs', 'order' => 0]);

    expect(rowNames(eloquentSource()))->toBe('mine');
});

it('inserts a child immediately after its parent and shifts orders', function () {
    $s = eloquentSource();
    $a = $s->create(['name' => 'A']);
    $s->create(['name' => 'B']);
    $s->create(['name' => 'A1', 'parent_id' => $a->id]);

    expect(rowNames($s))->toBe('A,A1,B');
});

it('updates a row but never its order via update()', function () {
    $s = eloquentSource();
    $a = $s->create(['name' => 'A']);
    $s->update($a->id, ['name' => 'Renamed', 'order' => 999]);

    $fresh = TestLineItem::find($a->id);
    expect($fresh->name)->toBe('Renamed')->and($fresh->order)->toBe(0);
});

it('reorders by writing the order column', function () {
    $s = eloquentSource();
    $a = $s->create(['name' => 'A']);
    $b = $s->create(['name' => 'B']);
    $c = $s->create(['name' => 'C']);

    $s->reorder([$c->id, $a->id, $b->id]);

    expect(rowNames($s))->toBe('C,A,B');
});

it('duplicates a row', function () {
    $s = eloquentSource();
    $a = $s->create(['name' => 'A', 'price' => 10]);
    $s->duplicate($a->id);

    expect(TestLineItem::where('name', 'A')->count())->toBe(2);
});

it('deletes a row', function () {
    $s = eloquentSource();
    $a = $s->create(['name' => 'A']);
    $s->delete($a->id);

    expect(TestLineItem::count())->toBe(0);
});

it('reports immediate persistence', function () {
    expect(eloquentSource()->supportsImmediatePersistence())->toBeTrue();
});

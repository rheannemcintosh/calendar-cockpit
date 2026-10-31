<?php

use App\Enums\TimeType;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('the sub-categories table has a default time type column', function () {
    expect(Schema::hasColumn('sub_categories', 'default_time_type'))->toBeTrue();
});

test('a sub-category saved without a default time type is exact', function () {
    $id = DB::table('sub_categories')->insertGetId([
        'category_id' => Category::factory()->create()->id,
        'emoji' => '💻',
        'name' => 'Deep work',
    ]);

    expect(SubCategory::find($id)->default_time_type)->toBe(TimeType::Exact);
});

test('a new sub-category is exact unless told otherwise', function () {
    $subCategory = Category::factory()->create()->subCategories()->create(['emoji' => '💻', 'name' => 'Deep work']);

    expect($subCategory->default_time_type)->toBe(TimeType::Exact)
        ->and($subCategory->fresh()->default_time_type)->toBe(TimeType::Exact);
});

test('a sub-category is saved and read back with its default time type', function (TimeType $timeType) {
    $subCategory = SubCategory::factory()->create(['default_time_type' => $timeType]);

    expect($subCategory->fresh()->default_time_type)->toBe($timeType);
})->with(TimeType::cases());

test('the database refuses a default time type that is not one of the four', function () {
    $insert = fn () => DB::table('sub_categories')->insert([
        'category_id' => Category::factory()->create()->id,
        'emoji' => '💻',
        'name' => 'Deep work',
        'default_time_type' => 'sometime',
    ]);

    expect($insert)->toThrow(QueryException::class, 'sub_categories_default_time_type_check')
        ->and(SubCategory::count())->toBe(0);
});

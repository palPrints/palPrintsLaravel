<?php

use App\Models\CustomFont;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->seed(RoleAndPermissionSeeder::class);

    $this->customer = User::factory()->create(['is_active' => true]);
    $this->customer->assignRole('customer');
    $this->designer = User::factory()->create(['is_active' => true]);
    $this->designer->assignRole('designer');

    $this->font = fn () => UploadedFile::fake()->create('Amiri.ttf', 20, 'font/ttf');
});

test('a customer cannot upload a font, only use the ones already there', function () {
    $this->actingAs($this->customer)->post(route('fonts.store'), ['font' => ($this->font)()], ['Accept' => 'application/json'])->assertForbidden();
    expect(CustomFont::count())->toBe(0);

    // the fonts list stays open to the customer
    $this->actingAs($this->customer)->getJson(route('fonts.index'))->assertOk();
});

test('a designer can upload a font', function () {
    $this->actingAs($this->designer)->post(route('fonts.store'), ['font' => ($this->font)()], ['Accept' => 'application/json'])->assertCreated();

    expect(CustomFont::where('uploaded_by', $this->designer->id)->value('family'))->toBe('Amiri');
});

test('the studio shows the upload button to designers only', function () {
    $this->actingAs($this->customer)->get(route('design-studio'))->assertOk()
        ->assertSee('window.PALPRINTS_STUDIO_FONT_UPLOAD = false', false);
});

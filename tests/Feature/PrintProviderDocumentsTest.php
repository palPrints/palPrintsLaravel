<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
    $this->seed(RoleAndPermissionSeeder::class);

    $this->owner = User::factory()->create(['is_active' => true]);
    $this->owner->assignRole('print_provider');
    $this->provider = $this->owner->printProvider()->create([
        'company_name' => 'مطبعة النور', 'approval_status' => 'approved', 'is_active' => true,
        'verification_document' => 'print-provider/documents/license.pdf',
        'id_document' => 'print-provider/documents/id.pdf',
    ]);

    $this->admin = User::factory()->create(['is_active' => true]);
    $this->admin->assignRole('admin');
});

function documentUrl($provider, string $field = 'id_document'): string
{
    return route('print-provider.documents', [$provider, $field]);
}

test('the admin and the shop owner can open a document, nobody else can', function () {
    Storage::disk('local')->put('print-provider/documents/id.pdf', 'secret id');

    $this->actingAs($this->admin)->get(documentUrl($this->provider))->assertOk();
    $this->actingAs($this->owner)->get(documentUrl($this->provider))->assertOk();

    $other = User::factory()->create(['is_active' => true]);
    $other->assignRole('print_provider');
    $this->actingAs($other)->get(documentUrl($this->provider))->assertForbidden();

    $customer = User::factory()->create(['is_active' => true]);
    $customer->assignRole('customer');
    $this->actingAs($customer)->get(documentUrl($this->provider))->assertForbidden();
});

test('a visitor who is not signed in is sent to the login page', function () {
    Storage::disk('local')->put('print-provider/documents/id.pdf', 'secret id');

    auth()->logout();
    $this->get(documentUrl($this->provider))->assertRedirect(route('login'));
});

test('only the two document columns can be opened', function () {
    $this->actingAs($this->admin)->get('/print-provider-documents/'.$this->provider->id.'/company_name')->assertNotFound();
});

test('a document that was uploaded before the move is still found on the public disk', function () {
    Storage::disk('public')->put('print-provider/documents/license.pdf', 'old licence');

    $this->actingAs($this->admin)->get(documentUrl($this->provider, 'verification_document'))->assertOk();
});

test('a missing file is a 404', function () {
    $this->actingAs($this->admin)->get(documentUrl($this->provider))->assertNotFound();
});

test('documents:make-private moves old files to the private disk and keeps the same paths', function () {
    Storage::disk('public')->put('print-provider/documents/license.pdf', 'old licence');
    Storage::disk('public')->put('support-attachments/ticket.png', 'screenshot');
    Storage::disk('public')->put('products/shirt.png', 'not a document');

    $this->artisan('documents:make-private', ['--dry-run' => true])->assertSuccessful();
    Storage::disk('public')->assertExists('print-provider/documents/license.pdf');
    Storage::disk('local')->assertMissing('print-provider/documents/license.pdf');

    $this->artisan('documents:make-private')->assertSuccessful();

    Storage::disk('local')->assertExists('print-provider/documents/license.pdf');
    Storage::disk('local')->assertExists('support-attachments/ticket.png');
    Storage::disk('public')->assertMissing('print-provider/documents/license.pdf');
    Storage::disk('public')->assertMissing('support-attachments/ticket.png');
    Storage::disk('public')->assertExists('products/shirt.png'); // product pictures stay public
    expect(Storage::disk('local')->get('print-provider/documents/license.pdf'))->toBe('old licence');
});

test('the shop profile and the admin users page link to the protected route, never to /storage', function () {
    Storage::disk('local')->put('print-provider/documents/id.pdf', 'secret id');
    Storage::disk('local')->put('print-provider/documents/license.pdf', 'licence');

    $this->actingAs($this->owner)->get(route('print-provider.profile'))
        ->assertOk()
        ->assertSee(documentUrl($this->provider), false)
        ->assertDontSee('/storage/print-provider/documents', false);

    $this->actingAs($this->admin)->get(route('admin.users'))
        ->assertOk()
        ->assertSee(str_replace('/', '\/', documentUrl($this->provider)), false)
        ->assertDontSee('storage\/print-provider\/documents', false);
});

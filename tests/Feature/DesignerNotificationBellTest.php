<?php

use App\Models\Notification;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

it('shows the unread count on the bell, and opening a notification marks it read and takes it off the list', function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $designer = User::factory()->create(['is_active' => true]);
    $designer->assignRole('designer');
    $designer->designerProfile()->create(['full_name' => $designer->name, 'approval_status' => 'approved']);

    $first = Notification::create(['user_id' => $designer->id, 'type' => 'support.reply', 'title' => 'تم الرد على طلب الدعم', 'message' => 'رد', 'link' => route('designer.support')]);
    Notification::create(['user_id' => $designer->id, 'type' => 'design_published', 'title' => 'تم نشر تصميمك', 'message' => 'تم', 'link' => route('designer.dashboard')]);

    $this->actingAs($designer)->get(route('designer.dashboard'))
        ->assertOk()
        ->assertSee('designer-notification-count', false)
        ->assertSee(route('designer.notifications.read', $first));

    $this->actingAs($designer)->post(route('designer.notifications.read', $first))->assertRedirect(route('designer.support'));
    expect($first->fresh()->is_read)->toBeTrue();

    $this->actingAs($designer)->get(route('designer.dashboard'))
        ->assertDontSee(route('designer.notifications.read', $first));
});

it('shows unread support answers as a count on the help-centre item of the sidebar', function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $designer = User::factory()->create(['is_active' => true]);
    $designer->assignRole('designer');
    $designer->designerProfile()->create(['full_name' => $designer->name, 'approval_status' => 'approved']);

    $this->actingAs($designer)->get(route('designer.dashboard'))->assertDontSee('profile-sidebar-badge', false);

    $note = Notification::create(['user_id' => $designer->id, 'type' => 'support.reply', 'title' => 'تم الرد على طلب الدعم', 'message' => 'رد', 'link' => route('designer.support')]);
    Notification::create(['user_id' => $designer->id, 'type' => 'design_published', 'title' => 'تم نشر تصميمك', 'message' => 'تم', 'link' => route('designer.dashboard')]);

    $this->actingAs($designer)->get(route('designer.dashboard'))->assertSee('profile-sidebar-badge', false)->assertSee('1 ردود جديدة من الدعم');

    $this->actingAs($designer)->post(route('designer.notifications.read', $note));
    $this->actingAs($designer)->get(route('designer.dashboard'))->assertDontSee('profile-sidebar-badge', false);
});

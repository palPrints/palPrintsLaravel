<?php

namespace Database\Seeders;

use App\Models\ApprovalRequest;
use App\Models\Notification;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class CoreWorkflowDemoSeeder extends Seeder
{
    public function run(): void
    {
        User::role(['designer', 'print_provider'])
            ->with(['designerProfile', 'printProvider'])
            ->get()
            ->each(function (User $user): void {
                $profile = $user->roleProfile();

                if (! $profile) {
                    return;
                }

                Wallet::firstOrCreate(['user_id' => $user->id]);

                ApprovalRequest::updateOrCreate(
                    ['request_number' => 'DEMO-APR-'.$user->id],
                    [
                        'user_id' => $user->id,
                        'role' => $user->primaryRole(),
                        'status' => 'submitted',
                        'profile_snapshot' => Arr::except($profile->toArray(), ['api_key']),
                        'submitted_at' => now(),
                        'reviewed_at' => null,
                        'reviewed_by' => null,
                        'admin_notes' => null,
                    ],
                );

                Notification::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'type' => 'demo.approval.submitted',
                    ],
                    [
                        'title' => 'طلب اعتماد تجريبي',
                        'message' => 'هذا إشعار تجريبي لاختبار تدفق اعتماد الحساب.',
                        'link' => route('account.status'),
                        'is_read' => false,
                        'read_at' => null,
                    ],
                );
            });
    }
}

<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_a_user_and_their_cor_file(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create([
            'role' => Role::Admin->value,
            'student_number' => null,
            'organization' => null,
        ]);
        $user = User::factory()->create(['cor_path' => 'cor/student.pdf']);
        Storage::disk('local')->put($user->cor_path, 'COR');

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $user));

        $response->assertRedirect();
        $response->assertSessionHas('status', "{$user->name} was removed.");
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        Storage::disk('local')->assertMissing('cor/student.pdf');
    }

    public function test_admin_cannot_delete_an_admin_account(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin->value,
            'student_number' => null,
            'organization' => null,
        ]);
        $otherAdmin = User::factory()->create([
            'role' => Role::Admin->value,
            'student_number' => null,
            'organization' => null,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $otherAdmin))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $otherAdmin->id]);
    }

    public function test_non_admin_cannot_delete_a_user(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        $this->actingAs($user)
            ->delete(route('admin.users.destroy', $anotherUser))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $anotherUser->id]);
    }
}

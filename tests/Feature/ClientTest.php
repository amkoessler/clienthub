<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_client(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/clients', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '123456789',
            'company' => 'Acme Inc',
            'status' => 'active',
            'notes' => 'A client',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'John Doe')
            ->assertJsonPath('data.email', 'john@example.com');

        $this->assertDatabaseHas('clients', [
            'email' => 'john@example.com',
            'user_id' => $user->id,
        ]);
    }

    public function test_create_client_requires_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/clients', [
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('name');
    }

    public function test_create_client_requires_email(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/clients', [
            'name' => 'John Doe',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_create_client_email_must_be_unique_globally(): void
    {
        $owner = User::factory()->create();
        Client::factory()->for($owner)->create(['email' => 'john@example.com']);

        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser, 'sanctum')->postJson('/api/clients', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_unauthenticated_user_cannot_create_client(): void
    {
        $response = $this->postJson('/api/clients', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(401);
    }

    public function test_user_can_list_only_own_clients(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Client::factory()->for($user)->count(3)->create();
        Client::factory()->for($otherUser)->count(2)->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/clients');

        $response->assertStatus(200)->assertJsonCount(3, 'data');
    }

    public function test_user_cannot_see_other_users_client(): void
    {
        $otherUser = User::factory()->create();
        $client = Client::factory()->for($otherUser)->create();

        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/clients/{$client->id}");

        $response->assertStatus(404);
    }

    public function test_user_can_update_own_client(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create();

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/clients/{$client->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(200)->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_user_cannot_update_other_users_client(): void
    {
        $otherUser = User::factory()->create();
        $client = Client::factory()->for($otherUser)->create();

        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/clients/{$client->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(404);
    }

    public function test_user_can_delete_own_client_soft_delete(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create();

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/clients/{$client->id}");

        $response->assertStatus(204)->assertNoContent();

        $this->assertSoftDeleted('clients', ['id' => $client->id]);
    }

    public function test_user_cannot_delete_other_users_client(): void
    {
        $otherUser = User::factory()->create();
        $client = Client::factory()->for($otherUser)->create();

        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/clients/{$client->id}");

        $response->assertStatus(404);
    }

    public function test_soft_deleted_client_not_in_list(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create();
        $client->delete();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/clients');

        $response->assertStatus(200)->assertJsonCount(0, 'data');
    }

    public function test_filter_clients_by_status(): void
    {
        $user = User::factory()->create();
        Client::factory()->for($user)->count(2)->create(['status' => 'active']);
        Client::factory()->for($user)->count(3)->create(['status' => 'inactive']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/clients?status=active');

        $response->assertStatus(200)->assertJsonCount(2, 'data');
    }

    public function test_filter_clients_by_status_inactive(): void
    {
        $user = User::factory()->create();
        Client::factory()->for($user)->count(2)->create(['status' => 'active']);
        Client::factory()->for($user)->count(3)->create(['status' => 'inactive']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/clients?status=inactive');

        $response->assertStatus(200)->assertJsonCount(3, 'data');
    }

    public function test_search_clients_by_name(): void
    {
        $user = User::factory()->create();
        Client::factory()->for($user)->create(['name' => 'John Smith']);
        Client::factory()->for($user)->create(['name' => 'Jane Doe']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/clients?search=john');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'John Smith');
    }

    public function test_search_clients_by_email(): void
    {
        $user = User::factory()->create();
        Client::factory()->for($user)->create(['email' => 'john@email.com']);
        Client::factory()->for($user)->create(['email' => 'jane@email.com']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/clients?search=john@email.com');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.email', 'john@email.com');
    }

    public function test_pagination_returns_15_items_per_page(): void
    {
        $user = User::factory()->create();
        Client::factory()->for($user)->count(20)->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/clients');

        $response->assertStatus(200)->assertJsonCount(15, 'data');
    }
}

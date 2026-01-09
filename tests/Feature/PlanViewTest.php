<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_plan_view_shows_remove_buttons_with_red_style(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('plan.show'));

        $response->assertOk();
        $response->assertSee('text-rose-600', false);
    }
}

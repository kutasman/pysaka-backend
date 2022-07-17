<?php

namespace Tests\Feature\Controllers;

use App\Http\Resources\GameResource;
use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameControllerTest extends TestCase
{
    use RefreshDatabase;

    public User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withAuthUser();
    }

    /**
     * @test
     */
    public function user_can_get_new_draft_game()
    {
        $this->getJson(route('games.create'))
            ->assertSuccessful()
            ->assertJsonStructure(GameResource::jsonSchema(['rounds']));
    }

    /**
     * @test
     */
    public function user_can_create_game()
    {
        $game = Game::factory()->create(['user_id' => $this->user->id]);

        $this->assertEquals(Game::STATUS_DRAFT, $game->status);

        $newData = [
            'name' => 'Test name',
            'rounds_max' => 5,
            'status' => Game::STATUS_CREATED,
            'max_lock_minutes' => 100,
        ];

        $this->putJson(route('games.update', $game), $newData)
            ->assertSuccessful()
            ->assertJsonStructure(GameResource::jsonSchema());

        $this->assertDatabaseHas('games', [ 'id' => $game->id ] + $newData );
    }

    /**
     * @test
     */
    public function user_can_start_created_game()
    {
        $game = Game::factory()->create(['user_id' => $this->user->id, 'status' => Game::STATUS_CREATED]);
        $newData = [
            'status' => Game::STATUS_STARTED,
        ];
        $this->postJson(route('games.start', $game), $newData)
            ->assertSuccessful()
            ->assertJsonStructure(GameResource::jsonSchema(['rounds']));

        $this->assertDatabaseHas('games', [
            'id' => $game->id,
            'status' => Game::STATUS_STARTED,
        ]);
    }

    /**
     * @test
     */
    public function user_can_finish_game()
    {
        $game = Game::factory()->create(['user_id' => $this->user->id, 'status' => Game::STATUS_STARTED]);

        $this->postJson(route('games.finish', $game))
            ->assertSuccessful()
            ->assertJsonStructure(GameResource::jsonSchema(['rounds']));

        $this->assertDatabaseHas('games', [
            'id' => $game->id,
            'status' => Game::STATUS_FINISHED,
        ]);
    }
}

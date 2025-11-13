<?php

namespace App\Jobs;

use App\Models\Game;
use App\Services\LeaderboardService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateLeaderboardJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;

    protected $game;

    /**
     * Create a new job instance.
     */
    public function __construct(Game $game)
    {
        $this->game = $game;
    }

    /**
     * Execute the job.
     */
    public function handle(LeaderboardService $leaderboardService): void
    {
        try {
            Log::info("Starting leaderboard update for game {$this->game->id}: {$this->game->title}");

            // Recalculate all leaderboards for this game
            $leaderboardService->recalculateGameLeaderboards($this->game);

            Log::info("Completed leaderboard update for game {$this->game->id}");

        } catch (\Exception $e) {
            Log::error("Failed to update leaderboard for game {$this->game->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("UpdateLeaderboardJob failed for game {$this->game->id}: " . $exception->getMessage());
    }
}

<?php

namespace App\Jobs;

use App\Models\Game;
use App\Services\SubmissionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CalculateScoresJob implements ShouldQueue
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
    public function handle(SubmissionService $submissionService): void
    {
        try {
            Log::info("Starting score calculation for game {$this->game->id}: {$this->game->title}");

            $submissions = $this->game->submissions()
                ->where('is_complete', true)
                ->get();

            $gradedCount = 0;
            $errorCount = 0;

            foreach ($submissions as $submission) {
                try {
                    $submissionService->gradeSubmission($submission);
                    $gradedCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    Log::error("Failed to grade submission {$submission->id}: " . $e->getMessage());
                }
            }

            Log::info("Completed score calculation for game {$this->game->id}. Graded: {$gradedCount}, Errors: {$errorCount}");

            // Dispatch leaderboard update job
            UpdateLeaderboardJob::dispatch($this->game);

        } catch (\Exception $e) {
            Log::error("Failed to calculate scores for game {$this->game->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("CalculateScoresJob failed for game {$this->game->id}: " . $exception->getMessage());
    }
}

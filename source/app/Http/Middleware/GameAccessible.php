<?php

namespace App\Http\Middleware;

use App\Models\Game;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GameAccessible
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the game ID from route parameters
        $gameId = $request->route('game');

        // If no game parameter, let the controller handle it
        if (!$gameId) {
            return $next($request);
        }

        // Find the game
        $game = Game::find($gameId);

        // Check if game exists
        if (!$game) {
            abort(404, 'Game not found.');
        }

        // Check if game is open for submissions
        if ($game->status !== 'open') {
            abort(403, 'This game is not currently open for submissions.');
        }

        // Check if game has not passed its lock date
        if ($game->lock_date && now()->isAfter($game->lock_date)) {
            abort(403, 'Submissions for this game are now locked.');
        }

        return $next($request);
    }
}

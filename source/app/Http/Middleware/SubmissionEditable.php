<?php

namespace App\Http\Middleware;

use App\Models\Submission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SubmissionEditable
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the submission ID from route parameters
        $submissionId = $request->route('submission');

        // If no submission parameter, let the controller handle it
        if (!$submissionId) {
            return $next($request);
        }

        // Find the submission with event relationship
        $submission = Submission::with('event')->find($submissionId);

        // Check if submission exists
        if (!$submission) {
            abort(404, 'Submission not found.');
        }

        // Check if user owns the submission
        if ($submission->user_id !== auth()->id()) {
            abort(403, 'You do not have permission to edit this submission.');
        }

        // Check if event has not passed its lock date
        if ($submission->event->lock_date && now()->isAfter($submission->event->lock_date)) {
            abort(403, 'This event is locked. Submissions can no longer be edited.');
        }

        // Check if event is still open or locked (not completed)
        if (in_array($submission->event->status, ['completed', 'in_progress'])) {
            abort(403, 'This event has been finalized. Submissions can no longer be edited.');
        }

        return $next($request);
    }
}

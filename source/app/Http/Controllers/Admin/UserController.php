<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $query = User::withCount(['submissions', 'groups']);

        // Filter by role
        if ($request->has('role') && $request->role !== 'all') {
            $query->where('role', $request->role);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        $users = $query->latest()->paginate(20);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['role', 'search']),
        ]);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        $user->load([
            'groups' => function ($query) {
                $query->withCount('users');
            }
        ]);

        $user->loadCount(['submissions', 'groups']);

        // Get user statistics
        $stats = [
            'total_submissions' => $user->submissions()->count(),
            'completed_submissions' => $user->submissions()->where('is_complete', true)->count(),
            'groups_joined' => $user->groups()->count(),
            'average_score' => $user->submissions()->where('is_complete', true)->avg('percentage') ?? 0,
            'best_score' => $user->submissions()->where('is_complete', true)->max('percentage') ?? 0,
            'total_points' => $user->submissions()->where('is_complete', true)->sum('total_score'),
        ];

        // Get recent submissions
        $recentSubmissions = $user->submissions()
            ->with(['game', 'group'])
            ->where('is_complete', true)
            ->latest('submitted_at')
            ->limit(10)
            ->get();

        // Get leaderboard positions
        $leaderboardPositions = \App\Models\Leaderboard::where('user_id', $user->id)
            ->with(['game', 'group'])
            ->orderBy('rank')
            ->limit(10)
            ->get();

        return Inertia::render('Admin/Users/Show', [
            'user' => $user,
            'stats' => $stats,
            'recentSubmissions' => $recentSubmissions,
            'leaderboardPositions' => $leaderboardPositions,
        ]);
    }

    /**
     * Update user role.
     */
    public function updateRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => 'required|in:admin,user',
        ]);

        // Prevent demoting yourself
        if ($user->id === auth()->id() && $validated['role'] !== 'admin') {
            return back()->with('error', 'You cannot demote yourself!');
        }

        $user->update(['role' => $validated['role']]);

        return back()->with('success', "User role updated to {$validated['role']}!");
    }

    /**
     * Delete user account.
     */
    public function destroy(User $user)
    {
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete yourself!');
        }

        $userName = $user->name;
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', "User '{$userName}' deleted successfully!");
    }

    /**
     * View user activity log.
     */
    public function activity(User $user)
    {
        // Get all submissions with games
        $submissions = $user->submissions()
            ->with(['game', 'group'])
            ->latest('created_at')
            ->paginate(20);

        // Get group memberships
        $groupActivity = $user->groups()
            ->withPivot('joined_at')
            ->orderBy('user_groups.joined_at', 'desc')
            ->get();

        return Inertia::render('Admin/Users/Activity', [
            'user' => $user,
            'submissions' => $submissions,
            'groupActivity' => $groupActivity,
        ]);
    }

    /**
     * Export users to CSV.
     */
    public function exportCSV(Request $request)
    {
        $query = User::withCount(['submissions', 'groups']);

        // Apply filters
        if ($request->has('role') && $request->role !== 'all') {
            $query->where('role', $request->role);
        }

        $users = $query->get();

        $filename = "users_" . now()->format('Y-m-d_His') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($users) {
            $file = fopen('php://output', 'w');

            // Headers
            fputcsv($file, [
                'ID',
                'Name',
                'Email',
                'Role',
                'Total Submissions',
                'Total Groups',
                'Created At',
                'Email Verified At',
            ]);

            // Data rows
            foreach ($users as $user) {
                fputcsv($file, [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->role,
                    $user->submissions_count,
                    $user->groups_count,
                    $user->created_at->format('Y-m-d H:i:s'),
                    $user->email_verified_at ? $user->email_verified_at->format('Y-m-d H:i:s') : 'Not Verified',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Bulk delete users.
     */
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        // Prevent deleting yourself
        if (in_array(auth()->id(), $validated['user_ids'])) {
            return back()->with('error', 'You cannot delete yourself!');
        }

        $count = User::whereIn('id', $validated['user_ids'])->delete();

        return back()->with('success', "{$count} users deleted successfully!");
    }

    /**
     * View user statistics dashboard.
     */
    public function statistics()
    {
        $stats = [
            'total_users' => User::count(),
            'admin_count' => User::where('role', 'admin')->count(),
            'regular_users' => User::where('role', 'user')->count(),
            'verified_users' => User::whereNotNull('email_verified_at')->count(),
            'unverified_users' => User::whereNull('email_verified_at')->count(),
            'active_users' => User::whereHas('submissions', function ($query) {
                $query->where('created_at', '>=', now()->subDays(30));
            })->count(),
        ];

        // Users by month
        $usersByMonth = User::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get();

        // Top participants
        $topParticipants = User::withCount('submissions')
            ->orderByDesc('submissions_count')
            ->limit(10)
            ->get();

        return Inertia::render('Admin/Users/Statistics', [
            'stats' => $stats,
            'usersByMonth' => $usersByMonth,
            'topParticipants' => $topParticipants,
        ]);
    }
}

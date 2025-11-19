<?php

require __DIR__.'/../source/vendor/autoload.php';

$app = require_once __DIR__.'/../source/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== PropOff Test Data Check ===\n\n";

// Check admin user
$admin = App\Models\User::where('email', 'admin@propoff.com')->first();
if ($admin) {
    echo "✓ Admin user: {$admin->name} (Role: {$admin->role})\n";
} else {
    echo "✗ Admin user NOT found\n";
}

// Check test user
$user = App\Models\User::where('email', 'user@propoff.com')->first();
if ($user) {
    echo "✓ Test user: {$user->name} (Role: {$user->role})\n";
} else {
    echo "✗ Test user NOT found\n";
}

echo "\n=== Database Counts ===\n";
echo "Users: " . App\Models\User::count() . "\n";
echo "Games: " . App\Models\Game::count() . "\n";
echo "Groups: " . App\Models\Group::count() . "\n";
echo "Questions: " . App\Models\Question::count() . "\n";
echo "Submissions: " . App\Models\Submission::count() . "\n";
echo "Group Question Answers: " . App\Models\GroupQuestionAnswer::count() . "\n";

echo "\n=== Sample Game ===\n";
$game = App\Models\Game::first();
if ($game) {
    echo "Title: {$game->title}\n";
    echo "Status: {$game->status}\n";
    echo "Event Date: {$game->event_date}\n";
    echo "Questions: " . $game->questions()->count() . "\n";
    echo "Submissions: " . $game->submissions()->count() . "\n";
    
    // Check if questions have group answers
    $question = $game->questions()->first();
    if ($question) {
        echo "\nFirst Question: {$question->question_text}\n";
        echo "Type: {$question->question_type}\n";
        echo "Points: {$question->points}\n";
        echo "Group Answers Set: " . $question->groupQuestionAnswers()->count() . "\n";
    }
}

echo "\n=== Guest System Check ===\n";
$guestUsers = App\Models\User::where('role', 'guest')->count();
echo "Guest users: {$guestUsers}\n";

$invitations = App\Models\GameGroupInvitation::count();
echo "Game invitations: {$invitations}\n";

if ($invitations > 0) {
    $invitation = App\Models\GameGroupInvitation::first();
    echo "Sample invitation token: {$invitation->token}\n";
    echo "Active: " . ($invitation->is_active ? 'Yes' : 'No') . "\n";
}

echo "\n=== Component File Check ===\n";

$guestJoinVue = file_exists(__DIR__.'/../source/resources/js/Pages/Guest/Join.vue');
echo ($guestJoinVue ? '✓' : '✗') . " Guest/Join.vue\n";

$guestMyResultsVue = file_exists(__DIR__.'/../source/resources/js/Pages/Guest/MyResults.vue');
echo ($guestMyResultsVue ? '✓' : '✗') . " Guest/MyResults.vue\n";

$confirmationVue = file_exists(__DIR__.'/../source/resources/js/Pages/Submissions/Confirmation.vue');
echo ($confirmationVue ? '✓' : '✗') . " Submissions/Confirmation.vue\n";

$adminDashboard = file_exists(__DIR__.'/../source/resources/js/Pages/Admin/Dashboard.vue');
echo ($adminDashboard ? '✓' : '✗') . " Admin/Dashboard.vue\n";

$adminGrading = file_exists(__DIR__.'/../source/resources/js/Pages/Admin/Grading/Index.vue');
echo ($adminGrading ? '✓' : '✗') . " Admin/Grading/Index.vue\n";

echo "\n";

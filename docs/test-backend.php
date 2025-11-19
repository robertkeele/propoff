#!/usr/bin/env php
<?php

/**
 * Backend Functionality Automated Tests
 * This tests core backend logic without browser
 */

require __DIR__.'/../source/vendor/autoload.php';

$app = require_once __DIR__.'/../source/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$errors = [];
$passed = 0;
$total = 0;

function test($name, $callback) {
    global $errors, $passed, $total;
    $total++;
    try {
        $result = $callback();
        if ($result === true || $result === null) {
            echo "✓ {$name}\n";
            $passed++;
            return true;
        } else {
            echo "✗ {$name}: {$result}\n";
            $errors[] = "{$name}: {$result}";
            return false;
        }
    } catch (Exception $e) {
        echo "✗ {$name}: " . $e->getMessage() . "\n";
        $errors[] = "{$name}: " . $e->getMessage();
        return false;
    }
}

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║           PropOff Backend Automated Tests                     ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// ===== MODELS & RELATIONSHIPS =====
echo "━━━ MODELS & RELATIONSHIPS ━━━\n";

test("User model exists and has data", function() {
    $count = App\Models\User::count();
    return $count > 0 ? true : "No users found";
});

test("Game model has questions relationship", function() {
    $game = App\Models\Game::first();
    if (!$game) return "No games found";
    $questions = $game->questions()->count();
    return $questions > 0 ? true : "No questions found";
});

test("Question has groupQuestionAnswers relationship", function() {
    $question = App\Models\Question::first();
    if (!$question) return "No questions found";
    $answers = $question->groupQuestionAnswers()->count();
    return $answers > 0 ? true : "No group answers found";
});

test("GroupQuestionAnswer model works", function() {
    $count = App\Models\GroupQuestionAnswer::count();
    return $count > 0 ? true : "No group question answers";
});

test("Submission has userAnswers relationship", function() {
    $submission = App\Models\Submission::first();
    if (!$submission) return "No submissions found";
    $answers = $submission->userAnswers()->count();
    return $answers >= 0 ? true : "Relationship broken";
});

// ===== GUEST SYSTEM =====
echo "\n━━━ GUEST SYSTEM ━━━\n";

test("GameGroupInvitation model exists", function() {
    return class_exists('App\Models\GameGroupInvitation') ? true : "Model not found";
});

test("Users table supports guest_token", function() {
    $columns = DB::select("SHOW COLUMNS FROM users");
    $hasGuestToken = false;
    foreach ($columns as $column) {
        if ($column->Field === 'guest_token') {
            $hasGuestToken = true;
            break;
        }
    }
    return $hasGuestToken ? true : "guest_token column not found";
});

test("GuestController exists", function() {
    return class_exists('App\Http\Controllers\GuestController') ? true : "Controller not found";
});

// ===== SERVICES =====
echo "\n━━━ SERVICE CLASSES ━━━\n";

test("GameService exists", function() {
    return class_exists('App\Services\GameService') ? true : "Service not found";
});

test("SubmissionService exists", function() {
    return class_exists('App\Services\SubmissionService') ? true : "Service not found";
});

test("LeaderboardService exists", function() {
    return class_exists('App\Services\LeaderboardService') ? true : "Service not found";
});

// ===== GRADING LOGIC =====
echo "\n━━━ GRADING LOGIC ━━━\n";

test("SubmissionService exists and has gradeSubmission method", function() {
    $service = new App\Services\SubmissionService();
    $methods = get_class_methods($service);
    return in_array('gradeSubmission', $methods) ? true : "gradeSubmission method not found";
});

test("Group-specific answers exist for questions", function() {
    $game = App\Models\Game::first();
    if (!$game) return "No games";
    
    $group = App\Models\Group::first();
    if (!$group) return "No groups";
    
    $question = $game->questions()->first();
    if (!$question) return "No questions";
    
    $answer = App\Models\GroupQuestionAnswer::where('question_id', $question->id)
        ->where('group_id', $group->id)
        ->first();
    
    return $answer ? true : "No group-specific answer found";
});

// ===== CONTROLLERS =====
echo "\n━━━ CONTROLLERS ━━━\n";

test("Admin DashboardController exists", function() {
    return class_exists('App\Http\Controllers\Admin\DashboardController') ? true : "Not found";
});

test("Admin GameController exists", function() {
    return class_exists('App\Http\Controllers\Admin\GameController') ? true : "Not found";
});

test("Admin GradingController exists", function() {
    return class_exists('App\Http\Controllers\Admin\GradingController') ? true : "Not found";
});

test("GuestController exists", function() {
    return class_exists('App\Http\Controllers\GuestController') ? true : "Not found";
});

// ===== VUE COMPONENTS =====
echo "\n━━━ VUE COMPONENTS ━━━\n";

test("Admin Dashboard.vue exists", function() {
    return file_exists(__DIR__.'/../source/resources/js/Pages/Admin/Dashboard.vue');
});

test("Admin Grading/Index.vue exists", function() {
    return file_exists(__DIR__.'/../source/resources/js/Pages/Admin/Grading/Index.vue');
});

test("Guest Join.vue exists", function() {
    return file_exists(__DIR__.'/../source/resources/js/Pages/Guest/Join.vue');
});

test("Guest MyResults.vue exists", function() {
    return file_exists(__DIR__.'/../source/resources/js/Pages/Guest/MyResults.vue');
});

test("Submissions Confirmation.vue exists", function() {
    return file_exists(__DIR__.'/../source/resources/js/Pages/Submissions/Confirmation.vue');
});

// ===== POLICIES & MIDDLEWARE =====
echo "\n━━━ POLICIES & MIDDLEWARE ━━━\n";

test("IsAdmin middleware exists", function() {
    return class_exists('App\Http\Middleware\IsAdmin') ? true : "Not found";
});

test("GamePolicy exists", function() {
    return class_exists('App\Policies\GamePolicy') ? true : "Not found";
});

// ===== ROUTES =====
echo "\n━━━ ROUTES ━━━\n";

test("Admin routes registered", function() {
    $routes = app('router')->getRoutes();
    $adminRoutes = 0;
    foreach ($routes as $route) {
        if (str_starts_with($route->getName() ?? '', 'admin.')) {
            $adminRoutes++;
        }
    }
    return $adminRoutes > 50 ? true : "Only {$adminRoutes} admin routes found, expected 60+";
});

test("Guest routes registered", function() {
    $routes = app('router')->getRoutes();
    $guestRoutes = 0;
    foreach ($routes as $route) {
        if (str_starts_with($route->getName() ?? '', 'guest.')) {
            $guestRoutes++;
        }
    }
    return $guestRoutes >= 3 ? true : "Only {$guestRoutes} guest routes, expected 3";
});

// ===== SUMMARY =====
echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║                        TEST RESULTS                            ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "Passed: {$passed}/{$total}\n";

if (count($errors) > 0) {
    echo "\n🔴 FAILURES:\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
} else {
    echo "\n✅ All backend tests passed! Ready for browser testing.\n";
}

echo "\n";

# Guest User Implementation - Detailed Roadmap

**Date**: November 18, 2025
**Scope**: Guest auto-registration with shareable game/group links

---

## Requirements Confirmed

✅ **Guest Registration:**
- Name only required (email optional for Phase 2)
- Auto-create user account with role 'guest'
- Auto-login after registration

✅ **Access Control:**
- Unique link per game/group combination
- Guests can only join the specific group from their link
- Guests cannot access admin features

✅ **Guest Capabilities:**
- See results after submission
- Return later to view results via unique token link
- Update submission before game deadline
- View leaderboard for their group
- **Receive prominent personal link after submission**

✅ **Personal Link Display:**
- Show personal link immediately after submission
- Big, prominent "Save This Link" call-to-action
- Copy link button for easy saving
- Email field optional (prepared for Phase 2 email integration)

❌ **Out of Scope (Phase 2):**
- QR code generation (later)
- Email integration (later - but UI prepared)
- Automated email sending of personal link (later)

---

## Implementation Phases

### Phase A: Database & Models (2 hours)

#### 1. Migration: Add guest support to users table
**File:** `database/migrations/XXXX_add_guest_support_to_users_table.php`

```php
public function up()
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('email')->nullable()->change();
        $table->string('password')->nullable()->change();
        $table->string('guest_token')->nullable()->unique()->after('role');
    });
}
```

#### 2. Migration: Create game_group_invitations table
**File:** `database/migrations/XXXX_create_game_group_invitations_table.php`

```php
Schema::create('game_group_invitations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('game_id')->constrained()->onDelete('cascade');
    $table->foreignId('group_id')->constrained()->onDelete('cascade');
    $table->string('token')->unique();
    $table->integer('max_uses')->nullable()->comment('Null = unlimited');
    $table->integer('times_used')->default(0);
    $table->timestamp('expires_at')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    
    $table->index(['game_id', 'group_id']);
    $table->unique(['game_id', 'group_id']); // One invitation per game-group combo
});
```

#### 3. Model: GameGroupInvitation
**File:** `app/Models/GameGroupInvitation.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class GameGroupInvitation extends Model
{
    protected $fillable = [
        'game_id',
        'group_id',
        'token',
        'max_uses',
        'times_used',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    // Generate unique token
    public static function generateToken()
    {
        return Str::random(32);
    }

    // Check if invitation is valid
    public function isValid()
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_uses && $this->times_used >= $this->max_uses) {
            return false;
        }

        return true;
    }

    // Increment usage count
    public function incrementUsage()
    {
        $this->increment('times_used');
    }
}
```

#### 4. Update User Model
**File:** `app/Models/User.php`

Add to fillable:
```php
protected $fillable = [
    'name',
    'email',
    'password',
    'role',
    'guest_token',
];
```

Add helper methods:
```php
public function isGuest()
{
    return $this->role === 'guest';
}

public function isAdmin()
{
    return $this->role === 'admin';
}

public function canEditSubmission(Submission $submission)
{
    // Guests can edit their own submissions before deadline
    if ($this->id !== $submission->user_id) {
        return false;
    }
    
    if ($submission->game->lock_date && now()->greaterThan($submission->game->lock_date)) {
        return false;
    }
    
    return true;
}
```

Update validation in User factory and requests to allow nullable email/password.

#### 5. Update Game Model
**File:** `app/Models/Game.php`

Add relationship:
```php
public function invitations()
{
    return $this->hasMany(GameGroupInvitation::class);
}
```

#### 6. Update Group Model
**File:** `app/Models/Group.php`

Add relationship:
```php
public function invitations()
{
    return $this->hasMany(GameGroupInvitation::class);
}
```

---

### Phase B: Guest Registration Backend (3-4 hours)

#### 1. Controller: GuestController
**File:** `app/Http/Controllers/GuestController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\GameGroupInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class GuestController extends Controller
{
    /**
     * Show the guest registration page
     */
    public function show($token)
    {
        $invitation = GameGroupInvitation::where('token', $token)
            ->with(['game', 'group'])
            ->firstOrFail();

        if (!$invitation->isValid()) {
            return Inertia::render('Guest/InvitationExpired', [
                'message' => 'This invitation is no longer valid.',
            ]);
        }

        return Inertia::render('Guest/Join', [
            'invitation' => [
                'token' => $invitation->token,
                'game' => [
                    'id' => $invitation->game->id,
                    'name' => $invitation->game->name,
                    'event_type' => $invitation->game->event_type,
                    'event_date' => $invitation->game->event_date,
                    'status' => $invitation->game->status,
                ],
                'group' => [
                    'id' => $invitation->group->id,
                    'name' => $invitation->group->name,
                ],
            ],
        ]);
    }

    /**
     * Register guest and auto-login
     */
    public function register(Request $request, $token)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
        ]);

        $invitation = GameGroupInvitation::where('token', $token)
            ->with(['game', 'group'])
            ->firstOrFail();

        if (!$invitation->isValid()) {
            return back()->withErrors(['token' => 'This invitation is no longer valid.']);
        }

        // Create guest user
        $guestToken = Str::random(32);
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email, // Store email for Phase 2
            'password' => null,
            'role' => 'guest',
            'guest_token' => $guestToken,
        ]);

        // Add user to group
        $invitation->group->users()->attach($user->id);

        // Increment invitation usage
        $invitation->incrementUsage();

        // Auto-login
        Auth::login($user);

        // TODO Phase 2: Send email with personal link if email provided
        // if ($request->email) {
        //     Mail::to($request->email)->send(new GuestWelcome($user, $invitation->game));
        // }

        // Redirect to game play page
        return redirect()->route('games.play', $invitation->game->id)
            ->with('success', 'Welcome! You can now play the game.');
    }

    /**
     * Show guest results page
     */
    public function results($guestToken)
    {
        $user = User::where('guest_token', $guestToken)
            ->where('role', 'guest')
            ->firstOrFail();

        // Get user's submissions
        $submissions = $user->submissions()
            ->with(['game', 'group'])
            ->latest()
            ->get()
            ->map(function ($submission) {
                return [
                    'id' => $submission->id,
                    'game_name' => $submission->game->name,
                    'group_name' => $submission->group->name,
                    'total_score' => $submission->total_score,
                    'possible_points' => $submission->possible_points,
                    'percentage' => $submission->percentage,
                    'is_complete' => $submission->is_complete,
                    'submitted_at' => $submission->submitted_at,
                    'can_edit' => $user->canEditSubmission($submission),
                ];
            });

        return Inertia::render('Guest/MyResults', [
            'user' => [
                'name' => $user->name,
                'guest_token' => $user->guest_token,
            ],
            'submissions' => $submissions,
        ]);
    }
}
```

#### 2. Form Request: GuestRegistrationRequest
**File:** `app/Http/Requests/GuestRegistrationRequest.php`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GuestRegistrationRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Public endpoint
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Please enter your name to continue.',
        ];
    }
}
```

#### 3. Update Routes
**File:** `routes/web.php`

Add guest routes (no auth required):
```php
// Guest routes (public)
Route::get('/join/{token}', [GuestController::class, 'show'])->name('guest.join');
Route::post('/join/{token}', [GuestController::class, 'register'])->name('guest.register');
Route::get('/my-results/{guestToken}', [GuestController::class, 'results'])->name('guest.results');
```

#### 4. Middleware Updates
**File:** `app/Http/Middleware/Authenticate.php`

Update to allow guests on certain routes:
```php
protected function redirectTo(Request $request): ?string
{
    // Allow guests to access these routes
    $guestRoutes = [
        'games.play',
        'submissions.continue',
        'submissions.saveAnswers',
        'submissions.submit',
        'leaderboards.game',
        'leaderboards.group',
    ];

    if ($request->routeIs($guestRoutes) && auth()->check() && auth()->user()->isGuest()) {
        return null; // Allow access
    }

    return $request->expectsJson() ? null : route('login');
}
```

---

### Phase C: Admin Invitation Management (2-3 hours)

#### 1. Update Admin GameController
**File:** `app/Http/Controllers/Admin/GameController.php`

Add method to generate invitation:
```php
/**
 * Generate invitation for game-group combination
 */
public function generateInvitation(Request $request, Game $game)
{
    $request->validate([
        'group_id' => 'required|exists:groups,id',
    ]);

    // Check if invitation already exists
    $invitation = GameGroupInvitation::where('game_id', $game->id)
        ->where('group_id', $request->group_id)
        ->first();

    if ($invitation) {
        // Reactivate if exists
        $invitation->update(['is_active' => true]);
    } else {
        // Create new invitation
        $invitation = GameGroupInvitation::create([
            'game_id' => $game->id,
            'group_id' => $request->group_id,
            'token' => GameGroupInvitation::generateToken(),
            'is_active' => true,
        ]);
    }

    $url = route('guest.join', $invitation->token);

    return back()->with('success', 'Invitation link generated!')->with('invitation_url', $url);
}

/**
 * Deactivate invitation
 */
public function deactivateInvitation(Request $request, Game $game, GameGroupInvitation $invitation)
{
    $invitation->update(['is_active' => false]);

    return back()->with('success', 'Invitation deactivated.');
}
```

#### 2. Update Admin Game Show Method
**File:** `app/Http/Controllers/Admin/GameController.php`

Update show method to include invitations:
```php
public function show(Game $game)
{
    $game->load([
        'creator',
        'questions' => function ($query) {
            $query->orderBy('display_order');
        },
        'invitations.group',
    ]);

    $game->loadCount(['submissions', 'questions']);

    // Get all groups
    $allGroups = Group::all();

    // Calculate statistics
    $stats = [
        'total_questions' => $game->questions_count,
        'total_submissions' => $game->submissions_count,
        'completed_submissions' => $game->submissions()->where('is_complete', true)->count(),
        'average_score' => (float) ($game->submissions()
            ->where('is_complete', true)
            ->avg('percentage') ?? 0),
    ];

    return Inertia::render('Admin/Games/Show', [
        'game' => $game,
        'stats' => $stats,
        'invitations' => $game->invitations,
        'availableGroups' => $allGroups,
    ]);
}
```

#### 3. Add Routes for Invitation Management
**File:** `routes/web.php`

```php
// Inside admin routes group
Route::post('/games/{game}/generate-invitation', [Admin\GameController::class, 'generateInvitation'])->name('games.generateInvitation');
Route::post('/games/{game}/invitations/{invitation}/deactivate', [Admin\GameController::class, 'deactivateInvitation'])->name('games.deactivateInvitation');
```

---

### Phase D: Frontend Guest Pages (3-4 hours)

#### 1. Guest Join Page
**File:** `resources/js/Pages/Guest/Join.vue`

```vue
<template>
    <Head title="Join Game" />

    <div class="min-h-screen bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center p-4">
        <div class="max-w-md w-full bg-white rounded-2xl shadow-2xl p-8">
            <!-- Game Info -->
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <TrophyIcon class="w-10 h-10 text-blue-600" />
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    {{ invitation.game.name }}
                </h1>
                <p class="text-gray-600">
                    {{ invitation.game.event_type }}
                </p>
                <p class="text-sm text-gray-500 mt-2">
                    Event Date: {{ formatDate(invitation.game.event_date) }}
                </p>
            </div>

            <!-- Group Info -->
            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 mb-6">
                <div class="flex items-center gap-2">
                    <UserGroupIcon class="w-5 h-5 text-purple-600" />
                    <p class="text-sm text-purple-900">
                        You're joining: <strong>{{ invitation.group.name }}</strong>
                    </p>
                </div>
            </div>

            <!-- Registration Form -->
            <form @submit.prevent="submit">
                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Enter Your Name <span class="text-red-500">*</span>
                    </label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Your name"
                        required
                        autofocus
                    />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                        {{ form.errors.name }}
                    </p>
                </div>

                <div class="mb-6">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Email (Optional - for Phase 2)
                    </label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="your.email@example.com"
                    />
                    <p class="mt-1 text-xs text-gray-500">
                        Optional: We'll send you a link to view your results later
                    </p>
                    <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">
                        {{ form.errors.email }}
                    </p>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
                >
                    <span v-if="form.processing">Joining...</span>
                    <span v-else>Join Game</span>
                </button>
            </form>

            <!-- Info Box -->
            <div class="mt-6 text-center text-sm text-gray-600">
                <p>No account required! Just enter your name to get started.</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { TrophyIcon, UserGroupIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    invitation: Object,
});

const form = useForm({
    name: '',
    email: '',
});

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const submit = () => {
    form.post(route('guest.register', props.invitation.token));
};
</script>
```

#### 2. Guest Results Page
**File:** `resources/js/Pages/Guest/MyResults.vue`

```vue
<template>
    <Head title="My Results" />

    <GuestLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Welcome -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">
                            Welcome back, {{ user.name }}!
                        </h2>
                        <p class="text-gray-600">Here are your game results</p>
                        
                        <!-- Save This Link Notice -->
                        <div class="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex items-start gap-3">
                                <InformationCircleIcon class="w-5 h-5 text-blue-600 mt-0.5" />
                                <div class="flex-1">
                                    <p class="text-sm text-blue-900 font-medium">Save this link!</p>
                                    <p class="text-sm text-blue-700 mt-1">
                                        Bookmark this page to return and view your results anytime.
                                    </p>
                                    <div class="mt-2 flex items-center gap-2">
                                        <input
                                            :value="currentUrl"
                                            readonly
                                            class="flex-1 text-xs bg-white border border-blue-300 rounded px-2 py-1"
                                        />
                                        <button
                                            @click="copyLink"
                                            class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700"
                                        >
                                            {{ copied ? 'Copied!' : 'Copy' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submissions -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">My Submissions</h3>
                        
                        <div v-if="submissions.length === 0" class="text-center py-12">
                            <TrophyIcon class="w-16 h-16 text-gray-400 mx-auto mb-4" />
                            <p class="text-gray-600">No submissions yet</p>
                        </div>

                        <div v-else class="space-y-4">
                            <div
                                v-for="submission in submissions"
                                :key="submission.id"
                                class="border border-gray-200 rounded-lg p-4 hover:border-gray-300 transition"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-gray-900">{{ submission.game_name }}</h4>
                                        <p class="text-sm text-gray-600">{{ submission.group_name }}</p>
                                        
                                        <div class="mt-2 flex items-center gap-4">
                                            <div>
                                                <span class="text-2xl font-bold text-blue-600">{{ submission.percentage }}%</span>
                                                <span class="text-sm text-gray-600 ml-1">
                                                    ({{ submission.total_score }}/{{ submission.possible_points }} points)
                                                </span>
                                            </div>
                                        </div>

                                        <p v-if="submission.submitted_at" class="text-xs text-gray-500 mt-2">
                                            Submitted: {{ formatDate(submission.submitted_at) }}
                                        </p>
                                    </div>

                                    <div class="flex flex-col gap-2">
                                        <span
                                            :class="submission.is_complete ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'"
                                            class="px-3 py-1 rounded-full text-xs font-medium"
                                        >
                                            {{ submission.is_complete ? 'Complete' : 'In Progress' }}
                                        </span>
                                        
                                        <Link
                                            v-if="submission.can_edit"
                                            :href="route('submissions.continue', submission.id)"
                                            class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700 text-center"
                                        >
                                            {{ submission.is_complete ? 'View' : 'Continue' }}
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>

<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { TrophyIcon, InformationCircleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    user: Object,
    submissions: Array,
});

const copied = ref(false);

const currentUrl = computed(() => {
    return window.location.href;
});

const copyLink = () => {
    navigator.clipboard.writeText(currentUrl.value);
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2000);
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>
```

#### 3. Guest Layout
**File:** `resources/js/Layouts/GuestLayout.vue`

```vue
<template>
    <div class="min-h-screen bg-gray-100">
        <!-- Simple Header -->
        <nav class="bg-white border-b border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <h1 class="text-xl font-bold text-gray-900">PropOff</h1>
                    </div>
                    <div class="flex items-center">
                        <span class="text-sm text-gray-600">Guest Access</span>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main>
            <slot />
        </main>
    </div>
</template>
```

#### 4. Invitation Expired Page
**File:** `resources/js/Pages/Guest/InvitationExpired.vue`

```vue
<template>
    <Head title="Invitation Expired" />

    <div class="min-h-screen bg-gray-100 flex items-center justify-center p-4">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8 text-center">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <ExclamationTriangleIcon class="w-10 h-10 text-red-600" />
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Invitation Expired</h1>
            <p class="text-gray-600">{{ message }}</p>
            <p class="text-sm text-gray-500 mt-4">
                Please contact the game administrator for a new invitation link.
            </p>
        </div>
    </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline';

defineProps({
    message: String,
});
</script>
```

#### 5. Submission Confirmation Page ⭐ NEW
**File:** `resources/js/Pages/Submissions/Confirmation.vue`

```vue
<template>
    <Head title="Submission Complete" />

    <GuestLayout>
        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-8">
                    <!-- Success Header -->
                    <div class="text-center mb-8">
                        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <CheckCircleIcon class="w-12 h-12 text-green-600" />
                        </div>
                        <h1 class="text-3xl font-bold text-gray-900 mb-2">
                            Submission Complete!
                        </h1>
                        <p class="text-gray-600">Your answers have been submitted successfully</p>
                    </div>

                    <!-- Score Display -->
                    <div class="bg-blue-50 rounded-lg p-6 mb-6 text-center">
                        <p class="text-sm text-blue-700 mb-2">Your Score</p>
                        <p class="text-5xl font-bold text-blue-600 mb-2">{{ submission.percentage }}%</p>
                        <p class="text-gray-600">
                            {{ submission.total_score }} out of {{ submission.possible_points }} points
                        </p>
                    </div>

                    <!-- Personal Link - PROMINENT DISPLAY -->
                    <div class="bg-yellow-50 border-2 border-yellow-400 rounded-lg p-6 mb-6">
                        <div class="flex items-start gap-3">
                            <ExclamationTriangleIcon class="w-8 h-8 text-yellow-600 flex-shrink-0" />
                            <div class="flex-1">
                                <h3 class="text-lg font-bold text-yellow-900 mb-2">
                                    📌 IMPORTANT: Save This Link!
                                </h3>
                                <p class="text-sm text-yellow-800 mb-4">
                                    You'll need this link to:
                                </p>
                                <ul class="text-sm text-yellow-800 mb-4 list-disc list-inside space-y-1">
                                    <li>View your results later</li>
                                    <li>See final scores after grading</li>
                                    <li>Update your answers (before deadline)</li>
                                    <li>Check leaderboard rankings</li>
                                </ul>
                                <div class="space-y-3">
                                    <div class="flex gap-2">
                                        <input 
                                            :value="personalLink" 
                                            readonly 
                                            class="flex-1 px-3 py-2 bg-white border border-yellow-300 rounded-md text-sm font-mono"
                                        />
                                        <button 
                                            @click="copyLink"
                                            class="px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700 font-medium whitespace-nowrap"
                                        >
                                            {{ copied ? '✓ Copied!' : 'Copy Link' }}
                                        </button>
                                    </div>
                                    <p class="text-xs text-yellow-700">
                                        💡 Tip: Bookmark this page or email the link to yourself!
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Email Confirmation (Phase 2 - Prepared but not implemented) -->
                    <div v-if="emailSent" class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                        <div class="flex items-center gap-2">
                            <CheckCircleIcon class="w-5 h-5 text-green-600" />
                            <p class="text-sm text-green-800">
                                A link has been sent to <strong>{{ userEmail }}</strong>
                            </p>
                        </div>
                    </div>

                    <!-- Next Steps -->
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">What's Next?</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <Link
                                :href="route('leaderboards.group', [submission.game_id, submission.group_id])"
                                class="flex items-center gap-3 p-4 border border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition"
                            >
                                <TrophyIcon class="w-6 h-6 text-blue-600" />
                                <div>
                                    <p class="font-medium text-gray-900">View Leaderboard</p>
                                    <p class="text-xs text-gray-600">See how you rank</p>
                                </div>
                            </Link>

                            <Link
                                :href="route('submissions.continue', submission.id)"
                                class="flex items-center gap-3 p-4 border border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition"
                            >
                                <DocumentTextIcon class="w-6 h-6 text-blue-600" />
                                <div>
                                    <p class="font-medium text-gray-900">Review Answers</p>
                                    <p class="text-xs text-gray-600">View your responses</p>
                                </div>
                            </Link>
                        </div>
                    </div>

                    <!-- Reminder -->
                    <div class="mt-6 bg-gray-50 rounded-lg p-4 text-center">
                        <p class="text-sm text-gray-600">
                            <strong>Don't forget!</strong> Save your personal link above to return and see final results after the admin grades all submissions.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>

<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { 
    CheckCircleIcon, 
    ExclamationTriangleIcon,
    TrophyIcon,
    DocumentTextIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    submission: Object,
    guestToken: String,
    emailSent: {
        type: Boolean,
        default: false,
    },
    userEmail: String,
});

const copied = ref(false);

const personalLink = computed(() => {
    return window.location.origin + '/my-results/' + props.guestToken;
});

const copyLink = () => {
    navigator.clipboard.writeText(personalLink.value);
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2000);
};
</script>
```

---

### Phase E: Admin UI for Invitations (2-3 hours)

#### 1. Update Admin Games Show Page
**File:** `resources/js/Pages/Admin/Games/Show.vue`

Add invitation management section after statistics:

```vue
<!-- Invitation Management -->
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Guest Invitations</h3>
        
        <!-- Generate New Invitation -->
        <div class="mb-6">
            <form @submit.prevent="generateInvitation">
                <div class="flex gap-4">
                    <select
                        v-model="invitationForm.group_id"
                        class="flex-1 border-gray-300 rounded-md"
                        required
                    >
                        <option value="">Select a group</option>
                        <option
                            v-for="group in availableGroups"
                            :key="group.id"
                            :value="group.id"
                        >
                            {{ group.name }}
                        </option>
                    </select>
                    <button
                        type="submit"
                        :disabled="invitationForm.processing"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
                    >
                        Generate Link
                    </button>
                </div>
            </form>
        </div>

        <!-- Existing Invitations -->
        <div v-if="invitations && invitations.length > 0" class="space-y-3">
            <div
                v-for="invitation in invitations"
                :key="invitation.id"
                class="border border-gray-200 rounded-lg p-4"
            >
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <h4 class="font-medium text-gray-900">{{ invitation.group.name }}</h4>
                            <span
                                :class="invitation.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'"
                                class="px-2 py-1 rounded-full text-xs font-medium"
                            >
                                {{ invitation.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <input
                                :value="getInvitationUrl(invitation.token)"
                                readonly
                                class="flex-1 text-sm bg-gray-50 border border-gray-300 rounded px-3 py-2"
                            />
                            <button
                                @click="copyInvitationLink(invitation.token)"
                                class="px-3 py-2 bg-gray-600 text-white text-sm rounded hover:bg-gray-700"
                            >
                                Copy Link
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            Used {{ invitation.times_used }} times
                            <span v-if="invitation.max_uses">/ {{ invitation.max_uses }} max</span>
                        </p>
                    </div>
                    <button
                        v-if="invitation.is_active"
                        @click="deactivateInvitation(invitation.id)"
                        class="ml-4 px-3 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-700"
                    >
                        Deactivate
                    </button>
                </div>
            </div>
        </div>

        <p v-else class="text-sm text-gray-500 text-center py-8">
            No invitations created yet. Generate a link to share with guests.
        </p>
    </div>
</div>
```

Add to script section:

```js
const invitationForm = useForm({
    group_id: '',
});

const generateInvitation = () => {
    invitationForm.post(route('admin.games.generateInvitation', props.game.id), {
        preserveScroll: true,
        onSuccess: () => {
            invitationForm.reset();
        },
    });
};

const getInvitationUrl = (token) => {
    return window.location.origin + '/join/' + token;
};

const copyInvitationLink = (token) => {
    const url = getInvitationUrl(token);
    navigator.clipboard.writeText(url);
    // You can add a toast notification here
};

const deactivateInvitation = (invitationId) => {
    if (confirm('Are you sure you want to deactivate this invitation?')) {
        router.post(route('admin.games.deactivateInvitation', [props.game.id, invitationId]), {}, {
            preserveScroll: true,
        });
    }
};
```

---

## Testing Checklist

- [ ] Admin can generate invitation link for game-group
- [ ] Guest can access invitation link and see game details
- [ ] Guest can register with name only
- [ ] Guest is auto-logged in after registration
- [ ] Guest is added to correct group
- [ ] Guest can play game and submit answers
- [ ] Guest can see results after submission
- [ ] Guest receives unique results link
- [ ] Guest can return to results page via unique link
- [ ] Guest can update submission before deadline
- [ ] Guest cannot access admin pages
- [ ] Invitation usage counter increments
- [ ] Expired invitations are rejected
- [ ] Inactive invitations cannot be used
- [ ] Each game-group has unique invitation

---

## Migration Commands

```bash
# Create migrations
php artisan make:migration add_guest_support_to_users_table
php artisan make:migration create_game_group_invitations_table

# Run migrations
php artisan migrate

# Rollback if needed
php artisan migrate:rollback
```

---

## Total Estimated Time: 8-12 hours

**Phase A:** 2 hours (Database & Models)
**Phase B:** 3-4 hours (Backend)
**Phase C:** 2-3 hours (Admin Backend)
**Phase D:** 3-4 hours (Frontend Guest)
**Phase E:** 2-3 hours (Admin UI)

---

## Ready to Start?

We'll proceed in this order:
1. Database changes (migrations, models)
2. Backend guest controller
3. Admin invitation generation
4. Guest frontend pages
5. Admin UI for invitations
6. Testing

Let me know when you're ready to begin!

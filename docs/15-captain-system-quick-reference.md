# Captain System - Quick Reference Guide

**Last Updated**: November 20, 2025

---

## 🎯 System Overview

### What We're Building:
- **Captain Role**: Per-group leaders (not global role) via `user_groups.is_captain`
- **3-Tier Questions**: Templates → Event Questions → Group Questions (customizable)
- **Dual Grading**: Captains set answers OR use admin answers
- **Games → Events**: Complete terminology change throughout app

### Key Design Decisions:
✅ Anyone with admin link can create group (become captain)
✅ Multiple captains per group have equal control
✅ Captains can modify questions even after event starts
✅ Admin answers hidden until after event ends
✅ Captain custom questions isolated to their group
✅ No cross-group leaderboard comparison

---

## 📋 Implementation Checklist

### ✅ Phase 1: Database Foundation (4-6 hours)
**Status**: ✅ COMPLETE

**Migrations to Create**:
- [x] 2025_11_20_000001_rename_games_to_events.php
- [x] 2025_11_20_000002_add_is_captain_to_user_groups.php
- [x] 2025_11_20_000003_rename_questions_to_event_questions.php
- [x] 2025_11_20_000003a_add_event_id_to_groups.php (added during migration)
- [x] 2025_11_20_000004_create_group_questions_table.php
- [x] 2025_11_20_000005_add_grading_source_to_groups.php
- [x] 2025_11_20_000006_create_event_answers_table.php
- [x] 2025_11_20_000007_create_captain_invitations_table.php
- [x] 2025_11_20_000008_update_user_answers_for_group_questions.php
- [x] 2025_11_20_000009_update_group_question_answers_for_group_questions.php

**Deliverables**:
- [x] Run `php artisan migrate` successfully (all 28 migrations ran)
- [x] Verify all foreign keys working
- [x] Database seeder updated for new structure
- [x] Automatic data migration for existing creators as captains

---

### ✅ Phase 2: Model Layer (3-4 hours)
**Status**: ✅ COMPLETE

**Models to Create**:
- [x] app/Models/GroupQuestion.php
- [x] app/Models/EventAnswer.php
- [x] app/Models/CaptainInvitation.php
- [x] app/Models/Event.php (renamed from Game)
- [x] app/Models/EventQuestion.php (renamed from Question)
- [x] app/Models/EventInvitation.php (renamed from GameGroupInvitation)

**Models to Update**:
- [x] Group.php (added captain methods: isCaptain(), addCaptain(), removeCaptain(), usesCaptainGrading(), usesAdminGrading())
- [x] User.php (added captain methods: captainOf(), isCaptainOf(), isCaptain(), getCaptainGroups())
- [x] Submission.php (changed to use event_id)
- [x] Leaderboard.php (changed to use event_id)
- [x] UserAnswer.php (changed to use group_question_id)
- [x] GroupQuestionAnswer.php (changed to use group_question_id)

**Factories Created**:
- [x] EventFactory.php
- [x] EventQuestionFactory.php

**All Relationships Updated**: ✅

---

### ✅ Phase 3: Rename Controllers & Routes (3-4 hours)
**Status**: ✅ COMPLETE

**Controllers Created**:
- [x] EventController.php (replaced GameController)
- [x] Admin/EventController.php (replaced Admin/GameController)
- [x] EventQuestionController.php (replaced QuestionController)
- [x] Admin/EventQuestionController.php (replaced Admin/QuestionController)

**Policies Created**:
- [x] EventPolicy.php (replaced GamePolicy)
- [x] EventQuestionPolicy.php (replaced QuestionPolicy)
- [x] SubmissionPolicy.php (updated to use event instead of game)

**Form Requests Created**:
- [x] StoreEventRequest.php
- [x] UpdateEventRequest.php
- [x] StoreEventQuestionRequest.php
- [x] UpdateEventQuestionRequest.php

**Routes Updated** (web.php):
- [x] /games → /events
- [x] /admin/games → /admin/events
- [x] /admin/games/{game}/questions → /admin/events/{event}/event-questions
- [x] All route names updated (games.* → events.*)
- [x] All leaderboard routes updated
- [x] All controller imports updated in routes file

---

### ✅ Phase 4: Captain Controllers (6-8 hours)
**Status**: Not Started

**Controllers to Create**:
- [ ] app/Http/Controllers/Captain/DashboardController.php
- [ ] app/Http/Controllers/Captain/GroupController.php
- [ ] app/Http/Controllers/Captain/GroupQuestionController.php
- [ ] app/Http/Controllers/Captain/GradingController.php
- [ ] app/Http/Controllers/Captain/MemberController.php

**Middleware to Create**:
- [ ] app/Http/Middleware/EnsureIsCaptainOfGroup.php
- [ ] Register in Kernel.php

**Routes to Add**:
```php
Route::middleware(['auth'])->prefix('captain')->name('captain.')->group(function () {
    Route::get('/dashboard', [Captain\DashboardController::class, 'index'])->name('dashboard');

    // Group management
    Route::get('/events/{event}/create-group/{token}', [Captain\GroupController::class, 'create'])->name('groups.create');
    Route::post('/events/{event}/groups', [Captain\GroupController::class, 'store'])->name('groups.store');

    // Question management (requires captain of group)
    Route::middleware('captain-of-group')->group(function () {
        Route::get('/groups/{group}/questions', [Captain\GroupQuestionController::class, 'index'])->name('questions.index');
        Route::post('/groups/{group}/questions', [Captain\GroupQuestionController::class, 'store'])->name('questions.store');
        Route::patch('/groups/{group}/questions/{question}', [Captain\GroupQuestionController::class, 'update'])->name('questions.update');
        Route::delete('/groups/{group}/questions/{question}', [Captain\GroupQuestionController::class, 'destroy'])->name('questions.destroy');
        Route::post('/groups/{group}/questions/reorder', [Captain\GroupQuestionController::class, 'reorder'])->name('questions.reorder');

        // Grading
        Route::get('/groups/{group}/grading', [Captain\GradingController::class, 'index'])->name('grading.index');
        Route::post('/groups/{group}/grading/set-answer', [Captain\GradingController::class, 'setAnswer'])->name('grading.setAnswer');
        Route::post('/groups/{group}/grading/calculate', [Captain\GradingController::class, 'calculate'])->name('grading.calculate');

        // Members
        Route::get('/groups/{group}/members', [Captain\MemberController::class, 'index'])->name('members.index');
        Route::post('/groups/{group}/members/{user}/promote', [Captain\MemberController::class, 'promote'])->name('members.promote');
        Route::delete('/groups/{group}/members/{user}', [Captain\MemberController::class, 'remove'])->name('members.remove');
    });
});
```

---

### ✅ Phase 5: Admin Captain Features (4-5 hours)
**Status**: Not Started

**Controllers to Create**:
- [ ] app/Http/Controllers/Admin/CaptainInvitationController.php
- [ ] app/Http/Controllers/Admin/EventAnswerController.php

**Routes to Add**:
```php
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    // Captain invitations
    Route::get('/events/{event}/captain-invitations', [Admin\CaptainInvitationController::class, 'index'])->name('captain-invitations.index');
    Route::post('/events/{event}/captain-invitations', [Admin\CaptainInvitationController::class, 'store'])->name('captain-invitations.store');
    Route::patch('/events/{event}/captain-invitations/{invitation}/deactivate', [Admin\CaptainInvitationController::class, 'deactivate'])->name('captain-invitations.deactivate');

    // Event-level grading
    Route::get('/events/{event}/answers', [Admin\EventAnswerController::class, 'index'])->name('event-answers.index');
    Route::post('/events/{event}/answers/set', [Admin\EventAnswerController::class, 'setAnswer'])->name('event-answers.set');
    Route::post('/events/{event}/answers/bulk', [Admin\EventAnswerController::class, 'bulkSet'])->name('event-answers.bulk');
});
```

---

### ✅ Phase 6: Dual Grading Logic (4-5 hours)
**Status**: Not Started

**Update SubmissionService::gradeSubmission()**:

```php
public function gradeSubmission(Submission $submission)
{
    $group = $submission->group;
    $totalScore = 0;
    $correctCount = 0;

    foreach ($submission->userAnswers as $userAnswer) {
        $groupQuestion = $userAnswer->groupQuestion;

        // Check grading source
        if ($group->grading_source === 'admin') {
            // Use event-level answers (admin-set)
            $correctAnswer = EventAnswer::where('event_id', $group->event_id)
                ->where('event_question_id', $groupQuestion->event_question_id)
                ->first();

            if (!$correctAnswer) {
                continue; // Admin hasn't set answer yet
            }

            $isVoid = $correctAnswer->is_void;
            $expectedAnswer = $correctAnswer->correct_answer;

        } else {
            // Use group-level answers (captain-set)
            $correctAnswer = GroupQuestionAnswer::where('group_id', $group->id)
                ->where('group_question_id', $groupQuestion->id)
                ->first();

            if (!$correctAnswer) {
                continue; // Captain hasn't set answer yet
            }

            $isVoid = $correctAnswer->is_void;
            $expectedAnswer = $correctAnswer->correct_answer;
        }

        if ($isVoid) {
            continue; // Skip voided questions
        }

        // Compare answers (existing logic)
        $isCorrect = $this->compareAnswers(
            $userAnswer->answer,
            $expectedAnswer,
            $groupQuestion->question_type
        );

        if ($isCorrect) {
            $totalScore += $groupQuestion->points;
            $correctCount++;
        }
    }

    // Update submission
    $submission->update([
        'total_score' => $totalScore,
        'answered_count' => $correctCount,
        'percentage' => ($submission->userAnswers->count() > 0)
            ? ($correctCount / $submission->userAnswers->count()) * 100
            : 0,
    ]);

    return $submission;
}
```

---

### ✅ Phase 7: Frontend - Captain Components (8-10 hours)
**Status**: Not Started

**Components to Create**:
- [ ] resources/js/Pages/Captain/Dashboard.vue
- [ ] resources/js/Pages/Captain/CreateGroup.vue
- [ ] resources/js/Pages/Captain/Questions/Index.vue
- [ ] resources/js/Pages/Captain/Questions/Customize.vue
- [ ] resources/js/Pages/Captain/Questions/Create.vue
- [ ] resources/js/Pages/Captain/Grading/Index.vue
- [ ] resources/js/Pages/Captain/Members/Index.vue

**Navigation Update** (AuthenticatedLayout.vue):
```vue
<Link
    v-if="$page.props.auth.user.captain_groups_count > 0"
    :href="route('captain.dashboard')"
    :active="route().current('captain.*')"
>
    Captain Dashboard
</Link>
```

---

### ✅ Phase 8: Frontend - Admin Updates (4-5 hours)
**Status**: Not Started

**Components to Update**:
- [ ] Admin/Dashboard.vue (simplify - remove statistics)
- [ ] Admin/Events/Show.vue (add captain invitations section)
- [ ] Admin/Events/Index.vue (terminology updates)

**Components to Create**:
- [ ] Admin/CaptainInvitations/Index.vue
- [ ] Admin/EventAnswers/Index.vue (event-level grading)

---

### ✅ Phase 9: Frontend - Player Updates (2-3 hours)
**Status**: Not Started

**Components to Update**:
- [ ] Dashboard.vue (Games → Events)
- [ ] Events/Available.vue (renamed from Games)
- [ ] Events/Play.vue (use group_questions)
- [ ] Submissions/Continue.vue (use group_questions)
- [ ] Navigation (Games → Events everywhere)

---

### ✅ Phase 10: Testing & Documentation (4-6 hours)
**Status**: Not Started

**Test Flows**:
1. [ ] Admin creates event with questions
2. [ ] Admin generates captain invitation link
3. [ ] Captain clicks link, creates group
4. [ ] Captain becomes captain automatically (is_captain=true)
5. [ ] Captain sees all event questions inherited
6. [ ] Captain removes 2 questions
7. [ ] Captain adds 1 custom question
8. [ ] Captain promotes another member to captain
9. [ ] Second captain can also modify questions
10. [ ] Captain sets grading_source to 'captain'
11. [ ] Captain sets answers for all questions
12. [ ] Player joins group via player invitation
13. [ ] Player plays game, submits answers
14. [ ] Captain calculates scores
15. [ ] Leaderboard updates correctly
16. [ ] Test admin grading mode (grading_source='admin')
17. [ ] Admin sets event-level answers
18. [ ] Group with admin mode scores correctly

---

## 🔑 Key Code Snippets

### Check if User is Captain of Group (Middleware)

```php
// app/Http/Middleware/EnsureIsCaptainOfGroup.php
public function handle(Request $request, Closure $next)
{
    $group = $request->route('group');

    if (!$group || !$group->isCaptain(auth()->id())) {
        abort(403, 'You must be a captain of this group.');
    }

    return $next($request);
}
```

### Create Group from Captain Invitation

```php
// Captain\GroupController::store()
public function store(Request $request, Event $event, $token)
{
    $invitation = CaptainInvitation::where('token', $token)->firstOrFail();

    if (!$invitation->canBeUsed()) {
        return redirect()->route('events.index')->with('error', 'Invitation expired or invalid');
    }

    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'grading_source' => 'required|in:captain,admin',
    ]);

    $group = Group::create([
        'event_id' => $event->id,
        'name' => $validated['name'],
        'join_code' => Group::generateCode(),
        'grading_source' => $validated['grading_source'],
        'created_by' => auth()->id(),
    ]);

    // Add creator as captain
    $group->members()->attach(auth()->id(), [
        'joined_at' => now(),
        'is_captain' => true,
    ]);

    // Copy all event questions to group questions
    foreach ($event->eventQuestions as $eventQuestion) {
        GroupQuestion::create([
            'group_id' => $group->id,
            'event_question_id' => $eventQuestion->id,
            'question_text' => $eventQuestion->question_text,
            'question_type' => $eventQuestion->question_type,
            'options' => $eventQuestion->options,
            'points' => $eventQuestion->points,
            'order' => $eventQuestion->order,
            'is_active' => true,
            'is_custom' => false,
        ]);
    }

    $invitation->incrementUsage();

    return redirect()->route('captain.questions.index', $group)
        ->with('success', 'Group created! Customize your questions below.');
}
```

### Promote Member to Captain

```php
// Captain\MemberController::promote()
public function promote(Group $group, User $user)
{
    $this->authorize('manageCaptains', $group);

    if ($group->isCaptain($user->id)) {
        return back()->with('error', 'User is already a captain.');
    }

    $group->promoteToCapta($user->id);

    return back()->with('success', "{$user->name} is now a captain!");
}
```

---

## 🎨 UI Components Reference

### Captain Dashboard Card

```vue
<template>
  <div class="bg-white rounded-lg shadow p-6">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h3 class="text-lg font-semibold">{{ group.name }}</h3>
        <p class="text-sm text-gray-600">{{ group.event.title }}</p>
      </div>
      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
        Captain
      </span>
    </div>

    <div class="grid grid-cols-3 gap-4 mb-4">
      <div class="text-center">
        <p class="text-2xl font-bold">{{ group.members_count }}</p>
        <p class="text-xs text-gray-600">Members</p>
      </div>
      <div class="text-center">
        <p class="text-2xl font-bold">{{ group.active_questions_count }}</p>
        <p class="text-xs text-gray-600">Questions</p>
      </div>
      <div class="text-center">
        <p class="text-2xl font-bold">{{ group.grading_source }}</p>
        <p class="text-xs text-gray-600">Grading</p>
      </div>
    </div>

    <div class="grid grid-cols-2 gap-2">
      <Link :href="route('captain.questions.index', group)" class="btn btn-primary">
        Manage Questions
      </Link>
      <Link :href="route('captain.grading.index', group)" class="btn btn-secondary">
        Set Answers
      </Link>
      <Link :href="route('captain.members.index', group)" class="btn btn-secondary">
        Manage Members
      </Link>
      <Link :href="route('leaderboards.group', { event: group.event_id, group: group.id })" class="btn btn-secondary">
        View Leaderboard
      </Link>
    </div>
  </div>
</template>
```

---

## 📊 Database Relationships

```
User
  - captainGroups() [belongsToMany via user_groups where is_captain=true]

Group
  - captains() [belongsToMany via user_groups where is_captain=true]
  - groupQuestions() [hasMany]
  - activeGroupQuestions() [hasMany where is_active=true]
  - event() [belongsTo Event]

Event (formerly Game)
  - eventQuestions() [hasMany EventQuestion]
  - groups() [hasMany Group]
  - eventAnswers() [hasMany EventAnswer]
  - captainInvitations() [hasMany CaptainInvitation]

EventQuestion (formerly Question)
  - event() [belongsTo Event]
  - groupQuestions() [hasMany GroupQuestion]
  - eventAnswers() [hasMany EventAnswer]

GroupQuestion
  - group() [belongsTo Group]
  - eventQuestion() [belongsTo EventQuestion]
  - userAnswers() [hasMany UserAnswer]
  - groupQuestionAnswer() [hasOne GroupQuestionAnswer]

EventAnswer (admin-set answers)
  - event() [belongsTo Event]
  - eventQuestion() [belongsTo EventQuestion]

GroupQuestionAnswer (captain-set answers)
  - group() [belongsTo Group]
  - groupQuestion() [belongsTo GroupQuestion]

UserAnswer
  - groupQuestion() [belongsTo GroupQuestion]
  - submission() [belongsTo Submission]

CaptainInvitation
  - event() [belongsTo Event]
```

---

## ⚠️ Migration Safety

**Before Starting Phase 1**:
```bash
# 1. Backup database
mysqldump -u root -p propoff > backup_before_captain_system_$(date +%Y%m%d).sql

# 2. Create feature branch
git checkout -b feature/captain-system

# 3. Commit current state
git add .
git commit -m "Backup before captain system implementation"
```

**After Each Phase**:
```bash
git add .
git commit -m "Phase X complete: [description]"
```

**If Rollback Needed**:
```bash
php artisan migrate:rollback --step=9  # Roll back all 9 captain migrations
git checkout main  # Return to main branch
```

---

## 🚀 Quick Start Commands

```bash
# Phase 1: Run migrations
php artisan migrate

# Phase 1: Test rollback
php artisan migrate:rollback --step=9
php artisan migrate  # Re-run

# Phase 2: Test models in tinker
php artisan tinker
>>> User::first()->captainGroups
>>> Group::first()->groupQuestions

# Build frontend
npm run build

# Run dev server
npm run dev
php artisan serve
```

---

## 📝 Progress Tracking

Update this section as you complete each phase:

**Started**: November 20, 2025
**Last Updated**: November 20, 2025

- [x] Phase 1: Database Foundation ✅ COMPLETE
- [x] Phase 2: Model Layer ✅ COMPLETE
- [x] Phase 3: Rename Controllers & Routes ✅ COMPLETE
- [ ] Phase 4: Captain Controllers (NOT STARTED)
- [ ] Phase 5: Admin Captain Features (NOT STARTED)
- [ ] Phase 6: Dual Grading Logic (NOT STARTED)
- [ ] Phase 7: Captain Vue Components (NOT STARTED)
- [ ] Phase 8: Admin UI Updates (NOT STARTED)
- [ ] Phase 9: Player UI Updates (NOT STARTED)
- [ ] Phase 10: Testing & Documentation (NOT STARTED)

**Backend**: ~35% Complete (Phases 1-3)
**Frontend**: 0% Complete
**Estimated Completion**: TBD

---

## 🔗 Related Documentation

- **Full Implementation Plan**: `docs/14-captain-system-implementation-plan.md`
- **Original Requirements**: `docs/MyEnhancements.txt`
- **Previous Session Notes**: `docs/claude.md`

---

**This is your go-to reference during implementation. Update checkboxes as you complete tasks!**

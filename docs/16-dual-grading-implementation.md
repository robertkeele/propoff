# Dual Grading System Implementation

**Date**: November 21, 2025
**Status**: Completed (Phase 6)

## Overview

The dual grading system allows groups to choose between two grading sources:
1. **Captain Grading**: Captain sets answers in real-time (uses `group_question_answers` table)
2. **Admin Grading**: Admin sets answers after event (uses `event_answers` table)

## Implementation Details

### 1. Grading Source Selection

Each group has a `grading_source` field:
```php
'grading_source' => 'captain' | 'admin'
```

This field is set when the captain creates the group and can be changed later (though changing mid-event should be done carefully).

### 2. SubmissionService Updates

The `gradeSubmission()` method now:
1. Checks the group's `grading_source`
2. Routes to appropriate answer table:
   - Captain: `group_question_answers` (per-group, per-question)
   - Admin: `event_answers` (per-event, per-question)
3. Handles both answer types consistently

### 3. Answer Tables

**Captain Grading** (`group_question_answers`):
```
- group_id
- group_question_id
- question_id (legacy)
- correct_answer
- points_awarded (optional custom points)
- is_void
```

**Admin Grading** (`event_answers`):
```
- event_id
- event_question_id
- correct_answer
- is_void
- set_at
- set_by
```

### 4. Key Differences

| Feature | Captain Grading | Admin Grading |
|---------|----------------|---------------|
| Answer Location | `group_question_answers` | `event_answers` |
| Custom Points | Yes (`points_awarded`) | No (uses question points) |
| Live Grading | Yes (immediate) | No (after event) |
| Per-Group Answers | Yes | No (shared across admin-graded groups) |
| Question Customization | Yes (add/remove/edit) | No (uses event questions) |

## Edge Cases Handled

### 1. No Answer Set Yet

**Scenario**: User submits before captain/admin sets answer

**Handling**:
- User answer marked as incorrect
- Points earned: 0
- Still counts toward possible points
- Can be re-graded when answer is set

**Code**:
```php
if (!$correctAnswer) {
    $userAnswer->update([
        'points_earned' => 0,
        'is_correct' => false,
    ]);
    $possiblePoints += $this->calculateMaxPointsForQuestion($groupQuestion);
    continue;
}
```

### 2. Voided Questions

**Scenario**: Question is voided after submissions

**Handling**:
- User answer marked as incorrect
- Points earned: 0
- Does NOT count toward possible points (voided = removed from scoring)

**Code**:
```php
if ($isVoid) {
    $userAnswer->update([
        'points_earned' => 0,
        'is_correct' => false,
    ]);
    continue; // Skip adding to possible points
}
```

### 3. Switching Grading Modes Mid-Event

**Scenario**: Captain changes from captain to admin grading (or vice versa)

**Risk**: Inconsistent grading if submissions exist

**Recommendation**:
- ⚠️ Only allow switching before any submissions are made
- Or: Recalculate ALL submissions when switching

**Code to Recalculate**:
```php
$submissions = $group->submissions()->where('is_complete', true)->get();
foreach ($submissions as $submission) {
    $submissionService->calculateScore($submission);
}
$leaderboardService->updateLeaderboard($event, $group);
```

### 4. Custom Captain Questions

**Scenario**: Captain adds custom question (no `event_question_id`)

**Handling**:
- Only captain grading works (admin grading requires `event_question_id`)
- If group switches to admin grading, custom questions become ungraded

**Code**:
```php
if ($group->grading_source === 'admin') {
    if ($groupQuestion->event_question_id) {
        // Find admin answer
    } else {
        // No admin answer for custom question
        $correctAnswer = null;
    }
}
```

### 5. Deleted Event Questions

**Scenario**: Admin deletes event question but group questions remain

**Handling**:
- Captain-graded groups unaffected (they have their own copy)
- Admin-graded groups lose the question (no event answer to reference)

**Prevention**:
- Event questions should cascade delete to group questions (check migration)
- Or: Soft delete event questions

### 6. Multiple Groups, Different Questions

**Scenario**: Two groups in same event have different questions

**Handling**:
- ✅ Fully supported (by design)
- Each group has independent `group_questions`
- Leaderboards are group-specific
- No cross-group comparison (can't compare apples to oranges)

**Important**:
```php
// CORRECT: Group-specific leaderboard
$leaderboard = $group->leaderboard;

// WRONG: Event-wide leaderboard (different questions per group)
$leaderboard = $event->leaderboard; // Don't use if questions differ!
```

## Automatic Score Recalculation

Scores are automatically recalculated when:

1. **Captain sets/updates answer**:
```php
// In CaptainInvitationController::setAnswer()
$submissionService->calculateScore($submission);
$leaderboardService->updateLeaderboard($event, $group);
```

2. **Admin sets/updates event answer**:
```php
// In EventAnswerController::setAnswer()
$adminGroups = $event->groups()->where('grading_source', 'admin')->get();
foreach ($adminGroups as $group) {
    // Recalculate for admin-graded groups only
}
```

3. **Question voided/unvoided**:
- Same as above

## Testing Checklist

- [ ] Captain creates group with captain grading
- [ ] Captain sets answers → scores update
- [ ] Captain voids question → scores update
- [ ] Admin creates group with admin grading
- [ ] Admin sets event answers → scores update for admin groups
- [ ] Admin sets event answers → captain groups unaffected
- [ ] User submits before answer set → 0 points, can regrade
- [ ] Group switches grading mode → all submissions recalculated
- [ ] Captain adds custom question → works with captain grading
- [ ] Captain adds custom question → group switches to admin → custom question ungraded

## Files Modified

### Services:
- `app/Services/SubmissionService.php` - Dual grading logic
- `app/Services/LeaderboardService.php` - Added `updateLeaderboard()` method

### Controllers:
- `app/Http/Controllers/Captain/GradingController.php` - Captain grading
- `app/Http/Controllers/Admin/EventAnswerController.php` - Admin grading
- `app/Http/Controllers/Admin/GradingController.php` - Updated for dual grading

### Models:
- All using proper relationships

## Performance Considerations

1. **Eager Loading**: Always eager load relationships
```php
$submission->userAnswers()->with('groupQuestion')->get();
```

2. **Batch Updates**: Recalculate all submissions in group at once
```php
foreach ($group->submissions as $submission) {
    $submissionService->calculateScore($submission);
}
```

3. **Caching**: Consider caching leaderboards for large events

## Future Enhancements

1. **Audit Log**: Track when answers change and who changed them
2. **Answer History**: Keep history of answer changes
3. **Preview Mode**: Show captain what scores would be with different answers
4. **Bulk Import**: Import answers from CSV
5. **Answer Templates**: Save common answer sets for reuse

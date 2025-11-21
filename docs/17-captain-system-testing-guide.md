# Captain System - Comprehensive Manual Testing Guide

**Date**: November 21, 2025
**Version**: 1.0
**Purpose**: Complete manual testing checklist for Captain System implementation

---

## Testing Overview

This guide provides step-by-step instructions to manually test all features of the Captain System. Each test scenario includes:
- **Prerequisites**: What needs to be set up first
- **Steps**: Detailed actions to perform
- **Expected Results**: What should happen
- **Checkpoint**: Mark when test passes

**Estimated Testing Time**: 4-6 hours
**Recommended Order**: Follow sections sequentially

---

## Pre-Testing Setup

### Environment Preparation
- [ ] Fresh database with `php artisan migrate:fresh --seed`
- [ ] Frontend assets compiled with `npm run build` or `npm run dev`
- [ ] Server running with `php artisan serve`
- [ ] Browser console open (F12) to check for JavaScript errors
- [ ] Test in Chrome/Edge/Firefox

### Test Accounts
Create these accounts for testing:

| Role | Email | Password | Purpose |
|------|-------|----------|---------|
| Admin | admin@test.com | password | Create events, generate invitations |
| Captain 1 | captain1@test.com | password | Primary captain testing |
| Captain 2 | captain2@test.com | password | Multi-captain testing |
| Player 1 | player1@test.com | password | Regular player |
| Player 2 | player2@test.com | password | Regular player |
| Player 3 | player3@test.com | password | Captain promotion testing |

### Database Check
```bash
# Verify all tables exist
php artisan tinker
>>> Schema::hasTable('events')  // should return true
>>> Schema::hasTable('event_questions')  // should return true
>>> Schema::hasTable('group_questions')  // should return true
>>> Schema::hasTable('event_answers')  // should return true
>>> Schema::hasTable('captain_invitations')  // should return true
```

---

## Section 1: Admin Event Management

### Test 1.1: Create Event with Questions
**Goal**: Verify admin can create a new event with questions

**Prerequisites**: Logged in as admin@test.com

**Steps**:
1. Navigate to `/admin/events`
2. Click "Create New Event" button
3. Fill in event details:
   - Title: "Super Bowl LVIII Predictions"
   - Description: "Predict the outcomes of Super Bowl 58"
   - Event Date: [Next Sunday]
   - Lock Date: [Day before event date]
   - Status: "Active"
4. Click "Save Event"
5. On event detail page, click "Add Question"
6. Create Question 1 (Multiple Choice):
   - Question Text: "Who will win the Super Bowl?"
   - Type: Multiple Choice
   - Options: ["Chiefs", "49ers", "Tie"]
   - Points: 10
7. Click "Save Question"
8. Create Question 2 (Numeric):
   - Question Text: "Total points scored by both teams?"
   - Type: Numeric
   - Points: 5
9. Click "Save Question"
10. Create Question 3 (Yes/No):
    - Question Text: "Will there be a safety?"
    - Type: Yes/No
    - Points: 3
11. Click "Save Question"

**Expected Results**:
- [ ] Event created successfully
- [ ] Event appears in admin events list
- [ ] Event shows status badge (Active/Upcoming/Completed)
- [ ] All 3 questions visible on event detail page
- [ ] Questions are numbered 1-3
- [ ] Points displayed correctly for each question
- [ ] No JavaScript errors in console

**Notes**: _________________________

---

### Test 1.2: Generate Captain Invitation
**Goal**: Verify admin can create captain invitation links

**Prerequisites**: Event created in Test 1.1

**Steps**:
1. Navigate to event detail page (`/admin/events/{event-id}`)
2. Find "Captain Invitations" section
3. Click "Generate Captain Invitation" button
4. Fill in invitation details:
   - Max Uses: 5
   - Expires At: [1 week from now]
5. Click "Generate Link"
6. Copy the invitation URL to clipboard
7. Click "Generate Link" again to create a second invitation (unlimited uses)

**Expected Results**:
- [ ] First invitation created with 32-character token
- [ ] Invitation URL format: `/events/{id}/create-group/{token}`
- [ ] Invitation shows "0 / 5 uses"
- [ ] Invitation shows expiration date
- [ ] Second invitation created with different token
- [ ] Second invitation shows "0 / ∞ uses"
- [ ] Both invitations listed in "Captain Invitations" table
- [ ] Copy button works correctly

**Copy Invitation URL Here**: _________________________

**Notes**: _________________________

---

### Test 1.3: Set Event-Level Answers (Admin Grading)
**Goal**: Verify admin can set correct answers at event level

**Prerequisites**: Event with 3 questions from Test 1.1

**Steps**:
1. Navigate to `/admin/events/{event-id}/answers`
2. For Question 1 (Who will win):
   - Select "Chiefs" as correct answer
   - Click "Set Answer"
3. For Question 2 (Total points):
   - Enter "51" as correct answer
   - Click "Set Answer"
4. For Question 3 (Safety):
   - Select "No" as correct answer
   - Click "Set Answer"
5. Return to event answers page
6. Verify all answers are saved

**Expected Results**:
- [ ] All 3 answers saved in `event_answers` table
- [ ] Success message after each answer is set
- [ ] Answers visible on event answers page
- [ ] Timestamp "Set at" displayed
- [ ] "Set by" shows admin username
- [ ] Option to mark question as "void" available

**Notes**: _________________________

---

### Test 1.4: Deactivate Captain Invitation
**Goal**: Verify admin can deactivate invitation links

**Prerequisites**: Captain invitations from Test 1.2

**Steps**:
1. Navigate to event detail page
2. Find first captain invitation (max 5 uses)
3. Click "Deactivate" button
4. Confirm deactivation
5. Copy the deactivated invitation URL
6. Log out from admin account
7. Paste deactivated URL in browser
8. Attempt to create group

**Expected Results**:
- [ ] Invitation marked as "Inactive" after deactivation
- [ ] Inactive badge displayed (red/gray)
- [ ] Accessing deactivated link shows error message
- [ ] Error: "This invitation is no longer active"
- [ ] User redirected to events list or login
- [ ] Second invitation still active

**Notes**: _________________________

---

## Section 2: Captain Group Creation

### Test 2.1: Create Group from Captain Invitation
**Goal**: Verify user can become captain via invitation link

**Prerequisites**:
- Active captain invitation from Test 1.2
- Logged in as captain1@test.com

**Steps**:
1. Paste captain invitation URL from Test 1.2 into browser
2. Should see "Create Group" page
3. Fill in group details:
   - Group Name: "Captain 1's Team"
   - Grading Mode: Select "Captain Grading" (captain sets answers)
4. Click "Create Group"
5. Copy the join code displayed

**Expected Results**:
- [ ] "Create Group" form loads successfully
- [ ] Event details visible on page
- [ ] Grading mode options clearly explained:
  - Captain Grading: "You set the answers"
  - Admin Grading: "Admin sets answers after event"
- [ ] Group created successfully
- [ ] User automatically added as captain (`is_captain=true`)
- [ ] Group has 6-character join code
- [ ] Redirected to Captain Questions page
- [ ] All 3 event questions inherited as group questions
- [ ] Captain invitation "times_used" incremented to 1

**Group Join Code**: _________________________

**Verify in Database**:
```bash
php artisan tinker
>>> $group = Group::latest()->first()
>>> $group->captains()->count()  // should be 1
>>> $group->groupQuestions()->count()  // should be 3
>>> $group->grading_source  // should be "captain"
```

**Notes**: _________________________

---

### Test 2.2: Create Second Group (Admin Grading Mode)
**Goal**: Verify groups can use admin grading mode

**Prerequisites**:
- Active captain invitation (unlimited uses)
- Logged in as captain2@test.com

**Steps**:
1. Use same captain invitation URL from Test 1.2
2. On "Create Group" page, fill in:
   - Group Name: "Captain 2's Team"
   - Grading Mode: Select "Admin Grading" (admin sets answers)
3. Click "Create Group"

**Expected Results**:
- [ ] Group created successfully
- [ ] `grading_source = 'admin'`
- [ ] captain2@test.com added as captain
- [ ] All 3 event questions inherited
- [ ] Captain invitation "times_used" incremented to 2
- [ ] Both groups exist independently

**Verify in Database**:
```bash
>>> Group::where('grading_source', 'admin')->count()  // should be 1
>>> Group::where('grading_source', 'captain')->count()  // should be 1
```

**Notes**: _________________________

---

## Section 3: Captain Question Customization

### Test 3.1: View Inherited Questions
**Goal**: Verify captain sees all event questions

**Prerequisites**: Captain 1's Team from Test 2.1, logged in as captain1@test.com

**Steps**:
1. Navigate to Captain Dashboard (`/captain/dashboard`)
2. Should see "Captain 1's Team" card
3. Click "Manage Questions"
4. Review questions list

**Expected Results**:
- [ ] Dashboard shows captain groups only
- [ ] Group card displays:
  - Group name
  - Event title
  - Member count: 1
  - Questions count: 3
  - Grading mode: "captain"
  - "Captain" badge visible
- [ ] Questions page lists all 3 inherited questions
- [ ] Each question shows:
  - Question text
  - Question type
  - Points value
  - "Inherited from event" badge
  - Edit button
  - Delete button
  - Active toggle
- [ ] "Add Custom Question" button visible

**Notes**: _________________________

---

### Test 3.2: Remove a Question
**Goal**: Verify captain can remove inherited questions

**Prerequisites**: Questions list from Test 3.1

**Steps**:
1. Find Question 3 (Will there be a safety?)
2. Click "Delete" or "Remove" button
3. Confirm deletion
4. Refresh page

**Expected Results**:
- [ ] Confirmation dialog appears: "Remove this question from your group?"
- [ ] Success message after deletion
- [ ] Question removed from list
- [ ] Remaining questions: 2 (Who will win, Total points)
- [ ] Other groups not affected (Captain 2's Team still has 3 questions)
- [ ] `is_active=false` in database OR question deleted

**Verify in Database**:
```bash
>>> $group = Group::where('name', "Captain 1's Team")->first()
>>> $group->groupQuestions()->count()  // should be 2 or 3
>>> $group->activeGroupQuestions()->count()  // should be 2
```

**Notes**: _________________________

---

### Test 3.3: Add Custom Question
**Goal**: Verify captain can create custom questions

**Prerequisites**: Questions list with 2 active questions

**Steps**:
1. Click "Add Custom Question" button
2. Fill in custom question:
   - Question Text: "Will Chiefs score first?"
   - Type: Yes/No
   - Points: 5
3. Click "Save Question"
4. Verify question appears in list

**Expected Results**:
- [ ] "Create Custom Question" form appears
- [ ] All question types available (Multiple Choice, Yes/No, Numeric, Text)
- [ ] For Multiple Choice, can add/remove options
- [ ] Custom question created successfully
- [ ] Question marked as "Custom" (not inherited)
- [ ] Question appears at bottom of list
- [ ] Total questions now: 3 (2 inherited, 1 custom)
- [ ] `is_custom=true` in database
- [ ] `event_question_id=null` in database

**Notes**: _________________________

---

### Test 3.4: Edit Question Details
**Goal**: Verify captain can modify question text and points

**Prerequisites**: Questions list with custom question

**Steps**:
1. Find custom question ("Will Chiefs score first?")
2. Click "Edit" button
3. Change question text to: "Will Chiefs score in first quarter?"
4. Change points from 5 to 8
5. Click "Save Changes"

**Expected Results**:
- [ ] Edit form pre-filled with current values
- [ ] Changes saved successfully
- [ ] Updated text displayed in questions list
- [ ] Updated points displayed
- [ ] Question type cannot be changed (if this is expected behavior)
- [ ] For inherited questions, edit creates a "customized" version

**Notes**: _________________________

---

### Test 3.5: Reorder Questions
**Goal**: Verify captain can change question order

**Prerequisites**: Questions list with 3 questions

**Steps**:
1. Locate drag handles or up/down arrows on questions
2. Move custom question to position 1 (top)
3. Move "Total points" question to position 2
4. Move "Who will win" question to position 3
5. Save order or verify auto-save

**Expected Results**:
- [ ] Questions reorder visually
- [ ] Order persists after page refresh
- [ ] Players see questions in this new order
- [ ] `order` column updated in database (1, 2, 3)

**Notes**: _________________________

---

## Section 4: Captain Grading (Setting Answers)

### Test 4.1: Set Answers for Captain-Graded Group
**Goal**: Verify captain can set correct answers for their group

**Prerequisites**: Captain 1's Team with captain grading mode

**Steps**:
1. Navigate to Captain Dashboard
2. Click "Set Answers" or "Grading" button for Captain 1's Team
3. For each question, set correct answer:
   - Question 1 (Chiefs score first quarter?): Select "Yes"
   - Question 2 (Who will win?): Select "49ers"
   - Question 3 (Total points?): Enter "48"
4. Click "Save Answers" or save individually
5. Verify answers are saved

**Expected Results**:
- [ ] Grading page lists all 3 active group questions
- [ ] Each question shows appropriate input based on type
- [ ] "Mark as Void" checkbox available per question
- [ ] All answers saved to `group_question_answers` table
- [ ] Success message displayed
- [ ] Timestamp "Set at" visible
- [ ] Can update answers later if needed

**Verify in Database**:
```bash
>>> $group = Group::where('name', "Captain 1's Team")->first()
>>> $group->groupQuestionAnswers()->count()  // should be 3
>>> $group->groupQuestionAnswers()->first()->correct_answer
```

**Notes**: _________________________

---

### Test 4.2: Mark Question as Void
**Goal**: Verify captain can void questions

**Prerequisites**: Answers set in Test 4.1

**Steps**:
1. Return to grading page
2. Find Question 3 (Total points?)
3. Check "Mark as Void" checkbox
4. Save changes
5. Verify void status

**Expected Results**:
- [ ] Void checkbox toggles on
- [ ] Question marked with "Voided" badge
- [ ] Voided questions won't affect scoring
- [ ] `is_void=true` in database
- [ ] Players still see the question but won't be penalized

**Notes**: _________________________

---

## Section 5: Multi-Captain Management

### Test 5.1: Invite Players to Group
**Goal**: Verify players can join captain's group

**Prerequisites**:
- Captain 1's Team with join code from Test 2.1
- Logged in as player1@test.com

**Steps**:
1. Navigate to Dashboard or Join Group page
2. Enter join code from Test 2.1
3. Click "Join Group"
4. Confirm membership

**Expected Results**:
- [ ] Join form accepts 6-character code
- [ ] Player added to group successfully
- [ ] Player sees group in their dashboard
- [ ] Player is NOT a captain (`is_captain=false`)
- [ ] Group member count increased to 2
- [ ] Player can start submissions

**Repeat for player2@test.com and player3@test.com**

**Verify in Database**:
```bash
>>> $group = Group::where('name', "Captain 1's Team")->first()
>>> $group->members()->count()  // should be 4 (1 captain + 3 players)
```

**Notes**: _________________________

---

### Test 5.2: Promote Member to Captain
**Goal**: Verify captain can promote other members

**Prerequisites**:
- Captain 1's Team with 4 members
- Logged in as captain1@test.com

**Steps**:
1. Navigate to Captain Dashboard
2. Click "Manage Members" for Captain 1's Team
3. Find player3@test.com in members list
4. Click "Promote to Captain" button
5. Confirm promotion
6. Log out and log in as player3@test.com
7. Check if Captain Dashboard is accessible

**Expected Results**:
- [ ] Members page lists all 4 group members
- [ ] Captain badge shown for captain1@test.com
- [ ] "Promote to Captain" button visible for non-captains
- [ ] Promotion confirmation dialog appears
- [ ] player3@test.com promoted successfully
- [ ] Success message: "Player 3 is now a captain!"
- [ ] player3@test.com now sees Captain Dashboard
- [ ] player3@test.com can manage questions and set answers
- [ ] `is_captain=true` for player3 in `user_groups` table
- [ ] Group now has 2 captains

**Verify in Database**:
```bash
>>> $group->captains()->count()  // should be 2
>>> $group->captains()->pluck('email')  // should show both emails
```

**Notes**: _________________________

---

### Test 5.3: Second Captain Can Modify Questions
**Goal**: Verify multiple captains have equal control

**Prerequisites**:
- Two captains (captain1 and player3) for Captain 1's Team
- Logged in as player3@test.com

**Steps**:
1. Navigate to Captain Dashboard (as player3)
2. Click "Manage Questions" for Captain 1's Team
3. Add a new custom question:
   - Question Text: "Will there be overtime?"
   - Type: Yes/No
   - Points: 10
4. Click "Save Question"
5. Return to questions list
6. Edit Question 1 (Chiefs score first quarter):
   - Change points from 5 to 7
7. Save changes

**Expected Results**:
- [ ] player3 has full captain access
- [ ] Can view all questions
- [ ] Can add custom questions
- [ ] Can edit existing questions
- [ ] Can delete questions
- [ ] Can set answers
- [ ] All changes persist
- [ ] Total questions now: 4
- [ ] Question 1 points updated to 7
- [ ] No errors or permission denied messages

**Log back in as captain1@test.com and verify changes are visible**

**Notes**: _________________________

---

### Test 5.4: Remove Member from Group
**Goal**: Verify captain can remove members

**Prerequisites**: Logged in as captain1@test.com

**Steps**:
1. Navigate to "Manage Members" page
2. Find player2@test.com
3. Click "Remove from Group" button
4. Confirm removal
5. Verify member removed
6. Log in as player2@test.com
7. Verify access to group is revoked

**Expected Results**:
- [ ] Removal confirmation dialog appears
- [ ] player2@test.com removed successfully
- [ ] Member count decreased to 3
- [ ] player2 no longer appears in members list
- [ ] player2 cannot access group anymore
- [ ] player2 can rejoin with join code if desired
- [ ] Cannot remove captains (or requires warning)

**Notes**: _________________________

---

## Section 6: Player Submission Flow

### Test 6.1: Start New Submission
**Goal**: Verify player can start a submission in captain's group

**Prerequisites**:
- Logged in as player1@test.com
- Member of Captain 1's Team

**Steps**:
1. Navigate to Dashboard
2. Find "Captain 1's Team" in "My Groups"
3. Click "Play" or "Start Submission"
4. Should see questions page

**Expected Results**:
- [ ] Dashboard shows groups user is member of
- [ ] Event details visible
- [ ] "Play Now" or "Start Submission" button available
- [ ] Questions page loads with captain's custom questions
- [ ] Total of 4 questions visible (after Test 5.3)
- [ ] Questions in order set by captain
- [ ] Custom questions included
- [ ] Removed question (Safety) NOT visible
- [ ] Points displayed for each question
- [ ] Progress indicator shows 0/4 completed

**Notes**: _________________________

---

### Test 6.2: Submit Answers
**Goal**: Verify player can answer and submit

**Prerequisites**: Submission started in Test 6.1

**Steps**:
1. Answer all 4 questions:
   - Q1 (Chiefs score first quarter?): Select "Yes"
   - Q2 (Who will win?): Select "Chiefs"
   - Q3 (Total points?): Enter "55"
   - Q4 (Overtime?): Select "No"
2. Click "Submit Answers" or "Submit"
3. Confirm submission if prompted

**Expected Results**:
- [ ] Can navigate through questions
- [ ] Can change answers before submitting
- [ ] Input validation works (can't submit empty numeric)
- [ ] Submission confirmation page appears
- [ ] Submission timestamp recorded
- [ ] User answers saved to `user_answers` table
- [ ] Linked to correct `group_question_id`
- [ ] Submission status: "pending" (not graded yet)
- [ ] Cannot edit answers after submission
- [ ] Success message: "Answers submitted successfully!"

**Verify in Database**:
```bash
>>> $submission = Submission::latest()->first()
>>> $submission->userAnswers()->count()  // should be 4
>>> $submission->status  // should be "pending" or "submitted"
>>> $submission->userAnswers()->first()->groupQuestion()->exists()  // true
```

**Notes**: _________________________

---

### Test 6.3: Player 3 Submits Answers
**Goal**: Generate more data for leaderboard testing

**Prerequisites**:
- Logged in as player3@test.com
- player3 is also a captain but submitting as player

**Steps**:
1. Navigate to Dashboard
2. Start submission for Captain 1's Team
3. Answer all 4 questions:
   - Q1: "No"
   - Q2: "49ers"
   - Q3: "45"
   - Q4: "Yes"
4. Submit answers

**Expected Results**:
- [ ] Captains can also be players (submit answers)
- [ ] Submission created successfully
- [ ] Now 2 submissions exist for this group
- [ ] Both submissions visible in admin grading view

**Notes**: _________________________

---

## Section 7: Scoring and Leaderboards (Captain Grading Mode)

### Test 7.1: Calculate Scores for Captain-Graded Group
**Goal**: Verify scoring works with captain-set answers

**Prerequisites**:
- 2 submissions in Captain 1's Team (player1, player3)
- Answers set by captain in Test 4.1 (Yes, 49ers, 48, voided)
- Logged in as captain1@test.com

**Steps**:
1. Navigate to Captain Dashboard
2. Click "Calculate Scores" or grading page for Captain 1's Team
3. Click "Grade All Submissions" or "Calculate Leaderboard"
4. Wait for processing

**Expected Results**:
- [ ] Scoring uses captain-set answers from `group_question_answers`
- [ ] player1's score:
  - Q1 (Yes): ✓ Correct = 7 points
  - Q2 (49ers): ✓ Correct = 10 points
  - Q3 (48): ✗ Wrong (answered 55) = 0 points
  - Q4: Voided = 0 points (everyone gets void)
  - **Total: 17 points**
- [ ] player3's score:
  - Q1 (Yes): ✗ Wrong (answered No) = 0 points
  - Q2 (49ers): ✓ Correct = 10 points
  - Q3 (48): ✗ Wrong (answered 45) = 0 points
  - Q4: Voided = 0 points
  - **Total: 10 points**
- [ ] Scores updated in `submissions` table
- [ ] `total_score` and `answered_count` calculated
- [ ] No JavaScript errors

**Verify in Database**:
```bash
>>> Submission::where('group_id', $group->id)->get()->pluck('total_score')
// Should show [17, 10] or [10, 17]
```

**Notes**: _________________________

---

### Test 7.2: View Group Leaderboard (Captain Mode)
**Goal**: Verify leaderboard displays correctly for captain-graded group

**Prerequisites**: Scores calculated in Test 7.1

**Steps**:
1. Navigate to `/leaderboards/events/{event}/groups/{group}`
2. Review leaderboard
3. Verify rankings

**Expected Results**:
- [ ] Leaderboard page loads successfully
- [ ] Event title and group name displayed
- [ ] Grading mode badge: "Captain Grading"
- [ ] Leaderboard shows 2 entries:
  - Rank 1: player1 - 17 points
  - Rank 2: player3 - 10 points
- [ ] Each entry shows:
  - Rank number
  - Player name
  - Total score
  - Submission timestamp
- [ ] Leaderboard updates when new submissions are graded
- [ ] Message: "Leaderboard for this group only (questions may differ from other groups)"

**Notes**: _________________________

---

## Section 8: Admin Grading Mode Testing

### Test 8.1: Player Joins Admin-Graded Group
**Goal**: Verify players can join groups using admin grading

**Prerequisites**:
- Captain 2's Team (admin grading mode) from Test 2.2
- Get join code for Captain 2's Team
- Logged in as player1@test.com

**Steps**:
1. Navigate to Groups or Dashboard
2. Join Captain 2's Team using join code
3. Verify membership
4. Start submission
5. Answer all 3 questions (standard event questions, not customized)
6. Submit answers

**Expected Results**:
- [ ] player1 successfully joins second group
- [ ] player1 can be member of multiple groups
- [ ] Submission page shows 3 questions (captain2 didn't customize)
- [ ] Questions match original event questions
- [ ] Submission created successfully

**Notes**: _________________________

---

### Test 8.2: Score with Admin-Set Answers
**Goal**: Verify admin grading mode uses event_answers table

**Prerequisites**:
- Admin-set answers from Test 1.3 (Chiefs, 51, No)
- Submission in Captain 2's Team
- Event has ended (or lock date passed, or manual grade triggered)

**Steps**:
1. Log in as admin@test.com
2. Navigate to `/admin/events/{event}/grading`
3. Find Captain 2's Team in groups list
4. Click "Calculate Scores" or "Grade Group"
5. Verify scoring source
6. Log in as captain2@test.com
7. Check if captain can see scores but not modify answers

**Expected Results**:
- [ ] Admin can trigger grading for admin-mode groups
- [ ] Scoring uses `event_answers` table (not group_question_answers)
- [ ] player1's score calculated based on admin answers:
  - Q1 (Who will win - Chiefs): Based on player1's answer
  - Q2 (Total points - 51): Based on player1's answer
  - Q3 (Safety - No): Based on player1's answer
- [ ] Scores saved correctly
- [ ] Captain can VIEW answers but cannot change them
- [ ] Captain sees message: "This group uses admin grading"
- [ ] Admin answers only visible after event lock date

**Notes**: _________________________

---

### Test 8.3: Compare Leaderboards Between Modes
**Goal**: Verify groups are scored independently

**Prerequisites**: Both groups have submissions and scores

**Steps**:
1. View leaderboard for Captain 1's Team (captain grading)
2. View leaderboard for Captain 2's Team (admin grading)
3. Compare results

**Expected Results**:
- [ ] Captain 1's Team leaderboard uses captain-set answers
- [ ] Captain 2's Team leaderboard uses admin-set answers
- [ ] Scores differ between groups even for same player
- [ ] No cross-group comparison message or feature
- [ ] Each leaderboard clearly indicates grading mode
- [ ] Grading source badge visible: "Captain Grading" vs "Admin Grading"

**Notes**: _________________________

---

## Section 9: Permissions and Security

### Test 9.1: Non-Captain Cannot Access Captain Features
**Goal**: Verify permissions are enforced

**Prerequisites**: Logged in as player1@test.com (not a captain)

**Steps**:
1. Attempt to navigate directly to:
   - `/captain/dashboard`
   - `/captain/groups/{group}/questions`
   - `/captain/groups/{group}/grading`
   - `/captain/groups/{group}/members`
2. Try to access captain features via URL manipulation

**Expected Results**:
- [ ] All captain routes return 403 Forbidden or redirect
- [ ] Error message: "You must be a captain to access this page"
- [ ] Captain navigation menu NOT visible in layout
- [ ] Cannot modify questions
- [ ] Cannot set answers
- [ ] Cannot promote members

**Notes**: _________________________

---

### Test 9.2: Captain Cannot Access Other Group's Settings
**Goal**: Verify captains are isolated to their groups

**Prerequisites**:
- Logged in as captain1@test.com (captain of Captain 1's Team)
- Captain 2's Team exists with different captain

**Steps**:
1. Get group ID for Captain 2's Team
2. Attempt to navigate to:
   - `/captain/groups/{captain2-group-id}/questions`
   - `/captain/groups/{captain2-group-id}/grading`
3. Try to modify questions via API if exposed

**Expected Results**:
- [ ] Access denied (403) to other captain's group
- [ ] Error: "You must be a captain of this group"
- [ ] Cannot view or modify other group's questions
- [ ] Cannot set answers for other groups
- [ ] Middleware `EnsureIsCaptainOfGroup` working correctly

**Notes**: _________________________

---

### Test 9.3: Regular User Cannot Access Admin Features
**Goal**: Verify admin middleware works

**Prerequisites**: Logged in as player1@test.com

**Steps**:
1. Attempt to access:
   - `/admin/dashboard`
   - `/admin/events`
   - `/admin/events/{event}/answers`
   - `/admin/events/{event}/captain-invitations`
2. Try URL manipulation

**Expected Results**:
- [ ] All admin routes return 403 or redirect to login
- [ ] Error message: "Unauthorized" or "Admin access required"
- [ ] Admin menu not visible in navigation
- [ ] User cannot create events
- [ ] User cannot set event-level answers
- [ ] User cannot generate captain invitations

**Notes**: _________________________

---

## Section 10: Edge Cases and Error Handling

### Test 10.1: Expired Captain Invitation
**Goal**: Verify expired invitations cannot be used

**Prerequisites**: Admin access

**Steps**:
1. Log in as admin@test.com
2. Create new event "Test Event for Expiry"
3. Generate captain invitation with:
   - Max Uses: 3
   - Expires At: [Yesterday's date]
4. Copy invitation URL
5. Log out and access invitation URL
6. Attempt to create group

**Expected Results**:
- [ ] Invitation shows as expired in admin panel
- [ ] Accessing expired link shows error
- [ ] Error message: "This invitation has expired"
- [ ] Cannot create group with expired invitation
- [ ] Redirected to events list
- [ ] `canBeUsed()` method returns false

**Notes**: _________________________

---

### Test 10.2: Max Uses Reached
**Goal**: Verify invitation stops working after max uses

**Prerequisites**: Create invitation with max_uses=1

**Steps**:
1. Create captain invitation with max_uses=1
2. Use invitation to create first group (captain1)
3. Copy same invitation URL
4. Log in as different user (captain2)
5. Attempt to use same invitation URL
6. Try to create group

**Expected Results**:
- [ ] First use successful (times_used=1)
- [ ] Second use blocked
- [ ] Error: "This invitation has reached its maximum uses"
- [ ] Cannot create second group
- [ ] `times_used >= max_uses` check working

**Notes**: _________________________

---

### Test 10.3: Submit Answers After Event Lock Date
**Goal**: Verify submissions blocked after lock date

**Prerequisites**: Event with lock date in the past

**Steps**:
1. Modify event lock_date to yesterday
2. Log in as new player
3. Join group
4. Attempt to start submission
5. Try to access submission page via URL

**Expected Results**:
- [ ] "Play" button disabled or hidden
- [ ] Message: "Event is locked - no new submissions"
- [ ] Direct URL access blocked
- [ ] Existing submissions remain accessible (view-only)
- [ ] Error if trying to submit via API

**Notes**: _________________________

---

### Test 10.4: Delete Group with Active Submissions
**Goal**: Verify data integrity on deletion

**Prerequisites**: Group with submissions

**Steps**:
1. Log in as admin
2. Attempt to delete event with groups
3. Verify cascade behavior
4. Check database for orphaned records

**Expected Results**:
- [ ] Deleting event warns about active groups
- [ ] Confirmation required
- [ ] Cascade delete removes:
  - Event questions
  - Group questions
  - Event answers
  - Captain invitations
  - Groups (if allowed)
  - Submissions (if allowed)
- [ ] Or deletion blocked if groups/submissions exist
- [ ] No orphaned foreign keys
- [ ] Database integrity maintained

**Verify in Database**:
```bash
# Check for orphaned records
>>> GroupQuestion::whereNull('group_id')->count()  // should be 0
>>> UserAnswer::whereNull('submission_id')->count()  // should be 0
```

**Notes**: _________________________

---

### Test 10.5: Submit with Missing Answers
**Goal**: Verify validation handles incomplete submissions

**Prerequisites**: Active submission

**Steps**:
1. Start new submission
2. Answer only 2 out of 4 questions
3. Leave 2 questions blank
4. Attempt to submit

**Expected Results**:
- [ ] Validation catches missing answers OR
- [ ] Submission allowed with partial answers (depending on requirements)
- [ ] If partial allowed:
  - Missing answers scored as incorrect
  - Score calculated only for answered questions
- [ ] If partial not allowed:
  - Error message: "Please answer all questions"
  - Cannot submit until all answered
- [ ] Clear indication of which questions are incomplete

**Notes**: _________________________

---

### Test 10.6: Switch Grading Mode Mid-Event
**Goal**: Test behavior when changing grading source

**Prerequisites**:
- Group with existing submissions
- Captain access

**Steps**:
1. Captain 1's Team currently uses captain grading
2. Change `grading_source` from 'captain' to 'admin' (via database or UI if available)
3. Recalculate scores
4. Check which answers are used

**Expected Results**:
- [ ] System detects grading mode change
- [ ] Warning message if mid-event change
- [ ] Scores recalculated with new source
- [ ] If changed to admin: uses event_answers
- [ ] If changed to captain: uses group_question_answers
- [ ] Leaderboard updates accordingly
- [ ] Consider: UI may prevent mid-event changes

**Notes**: _________________________

---

## Section 11: Data Integrity Checks

### Test 11.1: Verify Foreign Key Relationships
**Goal**: Ensure all relationships work correctly

**Steps**:
```bash
php artisan tinker

# Test Event relationships
>>> $event = Event::first()
>>> $event->eventQuestions()->count()  // should be > 0
>>> $event->groups()->count()  // should be > 0
>>> $event->captainInvitations()->count()  // should be > 0
>>> $event->eventAnswers()->count()  // should be > 0

# Test Group relationships
>>> $group = Group::first()
>>> $group->event()->exists()  // should be true
>>> $group->captains()->count()  // should be > 0
>>> $group->members()->count()  // should be > captains count
>>> $group->groupQuestions()->count()  // should be > 0
>>> $group->submissions()->count()  // should be > 0

# Test GroupQuestion relationships
>>> $gq = GroupQuestion::first()
>>> $gq->group()->exists()  // should be true
>>> $gq->eventQuestion()->exists()  // should be true (if not custom)
>>> $gq->userAnswers()->count()  // should be >= 0

# Test Submission relationships
>>> $sub = Submission::first()
>>> $sub->event()->exists()  // should be true
>>> $sub->group()->exists()  // should be true
>>> $sub->user()->exists()  // should be true
>>> $sub->userAnswers()->count()  // should be > 0

# Test UserAnswer relationships
>>> $ua = UserAnswer::first()
>>> $ua->groupQuestion()->exists()  // should be true
>>> $ua->submission()->exists()  // should be true
```

**Expected Results**:
- [ ] All relationships return expected results
- [ ] No null foreign keys where required
- [ ] Cascade deletes work correctly
- [ ] No N+1 query issues (use debugbar if available)

**Notes**: _________________________

---

### Test 11.2: Verify Migration Rollback
**Goal**: Ensure migrations can be rolled back safely

**WARNING**: Only do this on development database!

**Steps**:
```bash
# Backup first
php artisan db:seed  # ensure data exists

# Rollback captain migrations
php artisan migrate:rollback --step=9

# Verify old structure
php artisan tinker
>>> Schema::hasTable('games')  // should be true
>>> Schema::hasTable('events')  // should be false

# Re-run migrations
php artisan migrate

# Verify new structure
>>> Schema::hasTable('events')  // should be true
>>> Schema::hasTable('games')  // should be false
```

**Expected Results**:
- [ ] Rollback completes without errors
- [ ] Old tables restored (games, questions)
- [ ] New tables removed
- [ ] Re-running migrations works
- [ ] Data migrated correctly again

**Notes**: _________________________

---

## Section 12: User Experience and UI

### Test 12.1: Navigation Updates
**Goal**: Verify terminology changed from Games to Events

**Steps**:
1. Log in as each user type (admin, captain, player)
2. Check navigation menu
3. Check all page titles and headings
4. Check breadcrumbs
5. Check button labels

**Expected Results**:
- [ ] Admin menu: "Events" not "Games"
- [ ] Player menu: "Browse Events" not "Browse Games"
- [ ] Captain menu: "Captain Dashboard" visible for captains
- [ ] All page titles use "Event" terminology
- [ ] URLs use /events not /games
- [ ] Breadcrumbs: Home > Events > Event Name
- [ ] No references to "Game" anywhere in UI

**Scan for old terminology**:
```bash
cd source/resources/js
grep -r "Game" --include="*.vue"  # should return minimal results
grep -r "game" --include="*.vue"  # check context of matches
```

**Notes**: _________________________

---

### Test 12.2: Responsive Design
**Goal**: Verify UI works on mobile devices

**Steps**:
1. Open Chrome DevTools (F12)
2. Toggle device toolbar (Ctrl+Shift+M)
3. Test on:
   - iPhone SE (375x667)
   - iPad (768x1024)
   - Desktop (1920x1080)
4. Test captain dashboard on mobile
5. Test question management on mobile
6. Test submission flow on mobile

**Expected Results**:
- [ ] Layout adapts to screen size
- [ ] Navigation menu collapses on mobile
- [ ] Tables responsive or scrollable
- [ ] Buttons and forms usable on touch
- [ ] Captain question cards stack vertically on mobile
- [ ] No horizontal scrolling
- [ ] Text readable without zooming

**Notes**: _________________________

---

### Test 12.3: Error Messages and User Feedback
**Goal**: Verify user receives clear feedback

**Steps**:
1. Test various error conditions:
   - Invalid join code
   - Expired invitation
   - Missing required fields
   - Duplicate group name
2. Test success messages:
   - Group created
   - Question added
   - Answers submitted
   - Member promoted

**Expected Results**:
- [ ] Error messages displayed prominently
- [ ] Errors use red/danger color scheme
- [ ] Success messages use green color
- [ ] Messages auto-dismiss after 5 seconds OR have dismiss button
- [ ] Error messages actionable (tell user what to do)
- [ ] Validation errors displayed near relevant fields
- [ ] No generic "Something went wrong" messages

**Notes**: _________________________

---

## Section 13: Performance Testing

### Test 13.1: Load Testing with Multiple Groups
**Goal**: Verify performance with realistic data

**Prerequisites**: Seed database with more data

**Steps**:
```bash
# Seed large dataset
php artisan tinker
>>> factory(Group::class, 50)->create()
>>> factory(Submission::class, 200)->create()
>>> factory(UserAnswer::class, 2000)->create()
```

1. Navigate to admin events list (50+ groups)
2. Navigate to leaderboard with 100+ submissions
3. Captain manages 20+ questions
4. Check page load times (use browser Network tab)

**Expected Results**:
- [ ] Events list loads in < 2 seconds
- [ ] Leaderboard loads in < 3 seconds
- [ ] Question management loads in < 2 seconds
- [ ] No database query timeouts
- [ ] Pagination works for large lists
- [ ] No memory errors
- [ ] Eager loading used to prevent N+1 queries

**Notes**: _________________________

---

## Section 14: Browser Compatibility

### Test 14.1: Cross-Browser Testing
**Goal**: Verify application works in major browsers

**Steps**:
1. Test key flows in:
   - Chrome/Edge (Chromium)
   - Firefox
   - Safari (if on Mac)
2. Test captain dashboard
3. Test submission flow
4. Test admin panel

**Expected Results**:
- [ ] Chrome: Full functionality
- [ ] Firefox: Full functionality
- [ ] Safari: Full functionality
- [ ] No console errors in any browser
- [ ] CSS displays consistently
- [ ] JavaScript features work
- [ ] Forms submit correctly

**Notes**: _________________________

---

## Section 15: Final Integration Test

### Test 15.1: Complete End-to-End Flow
**Goal**: Run through entire system as all user types

**Time Required**: 30-45 minutes

**Complete Flow**:
1. **Admin** creates event "Final Integration Test"
2. **Admin** adds 5 questions
3. **Admin** sets event-level answers for all 5 questions
4. **Admin** generates captain invitation (unlimited uses)
5. **Captain 1** uses invitation to create "Team Alpha" (captain grading)
6. **Captain 1** removes 1 question
7. **Captain 1** adds 2 custom questions (total: 6 questions)
8. **Captain 1** sets answers for all 6 questions
9. **Player 1** joins Team Alpha
10. **Player 2** joins Team Alpha
11. **Player 1** submits answers
12. **Player 2** submits answers
13. **Captain 1** promotes Player 2 to captain
14. **Player 2 (now captain)** views questions and grading
15. **Captain 1** calculates scores
16. **All users** view leaderboard
17. **Captain 2** uses same invitation to create "Team Beta" (admin grading)
18. **Player 3** joins Team Beta
19. **Player 3** submits answers (sees original 5 questions)
20. **Admin** grades Team Beta (uses admin answers)
21. **All users** view Team Beta leaderboard
22. **Verify** both groups scored independently

**Expected Results**:
- [ ] All steps complete without errors
- [ ] Team Alpha uses captain grading (6 questions)
- [ ] Team Beta uses admin grading (5 questions)
- [ ] Leaderboards show correct scores
- [ ] No JavaScript errors throughout flow
- [ ] All permissions enforced correctly
- [ ] Data integrity maintained

**Final Verification**:
```bash
php artisan tinker
>>> Event::count()
>>> Group::count()  // should be 2
>>> GroupQuestion::count()
>>> Submission::count()  // should be 3
>>> UserAnswer::count()
>>> GroupQuestionAnswer::count()  // for Team Alpha
>>> EventAnswer::count()  // should be 5
>>> CaptainInvitation::count()
```

**Notes**: _________________________

---

## Testing Summary Checklist

### Core Functionality
- [ ] Admin can create events with questions
- [ ] Admin can generate captain invitations
- [ ] Admin can set event-level answers
- [ ] Captains can create groups from invitations
- [ ] Captains can customize group questions
- [ ] Captains can set answers for their group
- [ ] Captains can promote other members
- [ ] Players can join groups
- [ ] Players can submit answers
- [ ] Scoring works for both grading modes
- [ ] Leaderboards display correctly

### Data Integrity
- [ ] All foreign keys working
- [ ] Cascade deletes configured
- [ ] No orphaned records
- [ ] Migrations rollback successfully

### Security & Permissions
- [ ] Non-captains cannot access captain features
- [ ] Captains isolated to their groups
- [ ] Admin features require admin role
- [ ] Expired invitations blocked
- [ ] Max uses enforced

### User Experience
- [ ] Terminology changed to "Events"
- [ ] Navigation clear and intuitive
- [ ] Error messages helpful
- [ ] Success feedback provided
- [ ] Responsive on mobile

### Edge Cases
- [ ] Expired invitations handled
- [ ] Max uses enforced
- [ ] Lock date prevents late submissions
- [ ] Voided questions scored correctly
- [ ] Grading mode switch handled

---

## Bug Tracking Template

Use this section to document any bugs found during testing:

### Bug #1
**Date Found**: _______________
**Severity**: [ ] Critical [ ] Major [ ] Minor
**Test**: _______________
**Description**:
_______________________________________________________________

**Steps to Reproduce**:
1.
2.
3.

**Expected**: _______________
**Actual**: _______________
**Status**: [ ] Open [ ] Fixed [ ] Won't Fix

---

### Bug #2
**Date Found**: _______________
**Severity**: [ ] Critical [ ] Major [ ] Minor
**Test**: _______________
**Description**:
_______________________________________________________________

---

## Testing Sign-Off

**Tester Name**: _______________
**Date Started**: _______________
**Date Completed**: _______________
**Total Bugs Found**: _______________
**Critical Bugs**: _______________
**Ready for Production**: [ ] Yes [ ] No

**Notes**:
_______________________________________________________________
_______________________________________________________________
_______________________________________________________________

---

## Next Steps After Testing

### If Tests Pass:
1. [ ] Update documentation with any changes
2. [ ] Create production deployment plan
3. [ ] Backup production database
4. [ ] Run migrations on production
5. [ ] Monitor for errors
6. [ ] Notify users of new features

### If Tests Fail:
1. [ ] Document all bugs in issue tracker
2. [ ] Prioritize fixes (critical first)
3. [ ] Fix bugs and re-test
4. [ ] Regression test after fixes
5. [ ] Repeat until all tests pass

---

**END OF TESTING GUIDE**

*This document should be printed and used as a checklist during manual testing. Check off each box as you complete the test.*

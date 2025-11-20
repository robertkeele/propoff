# PropOff - Comprehensive Testing Guide

**Project**: PropOff Game Prediction Application
**Version**: 2.1
**Last Updated**: November 20, 2025
**Status**: Ready for Testing

---

## Table of Contents
1. [Quick Start](#quick-start)
2. [Environment Setup](#environment-setup)
3. [Test Accounts](#test-accounts)
4. [Automated Backend Tests](#automated-backend-tests)
5. [Database Status Check](#database-status-check)
6. [Manual Testing Guide](#manual-testing-guide)
7. [Testing Priorities](#testing-priorities)
8. [Bug Reporting](#bug-reporting)

---

## Quick Start

### 1. Start Servers
```bash
# Terminal 1: Laravel backend
cd source
php artisan serve

# Terminal 2: Vite frontend
cd source
npm run dev
```

### 2. Access Application
- **Frontend (Vite)**: http://localhost:5173
- **Backend (Laravel)**: http://127.0.0.1:8000
- **Login Page**: http://127.0.0.1:8000/login

### 3. Run Automated Tests
```bash
# Backend automated tests (26 tests)
php docs/test-backend.php

# Database status check
php docs/test-data.php
```

### 4. Login and Test
- **Admin**: admin@propoff.com / password
- **User**: user@propoff.com / password

---

## Environment Setup

### Required Services ✅
- ✅ MySQL Database running
- ✅ Vite Dev Server: http://localhost:5173
- ✅ Laravel Server: http://127.0.0.1:8000
- ✅ Database seeded with test data

### Test Data Available
- **Users**: 22 (including test accounts)
- **Games**: 3 sample games
- **Groups**: 5 groups with members
- **Questions**: 30 questions across games
- **Submissions**: 53 test submissions
- **Group Question Answers**: 150 group-specific correct answers

---

## Test Accounts

### Admin Account
- **Email**: admin@propoff.com
- **Password**: password
- **Access**: Full admin dashboard and all features

### Regular User Account
- **Email**: user@propoff.com
- **Password**: password
- **Access**: User dashboard, game playing, group management

### Guest Users
- Created dynamically via invitation links
- No password required (name only)
- Personal results link for each guest

---

## Automated Backend Tests

Run the automated test suite to verify backend functionality:

```bash
cd docs
php test-backend.php
```

### Test Coverage (26 Total Tests)

#### Models & Relationships (5 tests)
- ✅ User model exists and has data
- ✅ Game model has questions relationship
- ✅ Question has groupQuestionAnswers relationship
- ✅ GroupQuestionAnswer model works
- ✅ Submission has userAnswers relationship

#### Guest System (3 tests)
- ✅ GameGroupInvitation model exists
- ✅ Users table supports guest_token field
- ✅ GuestController exists

#### Service Classes (3 tests)
- ✅ GameService exists
- ✅ SubmissionService exists
- ✅ LeaderboardService exists

#### Grading Logic (2 tests)
- ✅ SubmissionService has gradeSubmission method
- ✅ Group-specific answers exist for questions

#### Controllers (4 tests)
- ✅ Admin DashboardController exists
- ✅ Admin GameController exists
- ✅ Admin GradingController exists
- ✅ GuestController exists

#### Vue Components (5 tests)
- ✅ Admin/Dashboard.vue exists
- ✅ Admin/Grading/Index.vue exists
- ✅ Guest/Join.vue exists
- ✅ Guest/MyResults.vue exists
- ✅ Submissions/Confirmation.vue exists

#### Policies & Middleware (2 tests)
- ✅ IsAdmin middleware exists
- ✅ GamePolicy exists

#### Routes (2 tests)
- ✅ 60+ admin routes registered
- ✅ 3+ guest routes registered

### Expected Output
```
╔════════════════════════════════════════════════════════════════╗
║           PropOff Backend Automated Tests                     ║
╚════════════════════════════════════════════════════════════════╝

━━━ MODELS & RELATIONSHIPS ━━━
✓ User model exists and has data
✓ Game model has questions relationship
...

Passed: 26/26

✅ All backend tests passed! Ready for browser testing.
```

---

## Database Status Check

Run quick database verification:

```bash
cd docs
php test-data.php
```

### Output Includes
- Admin and test user verification
- Database record counts
- Sample game information
- Guest system status
- Component file verification

### Example Output
```
=== PropOff Test Data Check ===

✓ Admin user: Admin User (Role: admin)
✓ Test user: Test User (Role: user)

=== Database Counts ===
Users: 22
Games: 3
Groups: 5
Questions: 30
Submissions: 53
Group Question Answers: 150
```

---

## Manual Testing Guide

### PHASE 1: Authentication & Basic Access

#### 1. Login as Admin
- **URL**: http://127.0.0.1:8000/login
- **Credentials**: admin@propoff.com / password
- **Expected**: Redirect to /dashboard
- **Verify**: Admin navigation shows "Admin" link

#### 2. Check Admin Dashboard
- **URL**: http://127.0.0.1:8000/admin/dashboard
- **Verify**:
  - Statistics cards display (games, questions, users, groups, submissions, completed)
  - Recent games list visible
  - Recent submissions list visible
  - Recent users table visible
  - Quick actions grid functional

---

### PHASE 2: Admin Game Management

#### 3. View Games List
- **URL**: http://127.0.0.1:8000/admin/games
- **Verify**:
  - List of games displays
  - Search bar functional
  - Status filter works
  - Pagination works (if > 20 games)
  - Status badges color-coded

#### 4. Create New Game
- **Steps**:
  1. Click "Create Game" button
  2. Fill form:
     - Name: "Test Game"
     - Category: "sports"
     - Description: "Test description"
     - Event Date: Future date
     - Lock Date: (optional)
     - Status: "Draft"
  3. Submit
- **Verify**: Game created, redirected to game show page

#### 5. Edit Game
- **Steps**:
  1. Go to game show page
  2. Click "Edit" button
  3. Modify fields
  4. Save changes
- **Verify**: Changes saved, success message shown

#### 6. View Game Details
- **URL**: http://127.0.0.1:8000/admin/games/{id}
- **Verify**:
  - Game information displays
  - Statistics visible (questions, submissions, avg score)
  - Quick action cards (Questions, Grading, Statistics)
  - Duplicate and Delete buttons visible

---

### PHASE 3: Admin Question Management

#### 7. Create Questions from Templates
- **URL**: http://127.0.0.1:8000/admin/games/{id}/questions/create
- **Verify**:
  - Templates filtered by game category
  - "Select All" / "Deselect All" buttons work
  - "Add Selected" button adds multiple templates
  - Templates with variables show modal for input
  - Live preview updates as you type variable values

#### 8. Create Custom Question
- **Steps**:
  1. Click "Add Custom Question"
  2. Fill question text
  3. Select question type
  4. For multiple choice:
     - Add options (label)
     - Set bonus points per option (e.g., "Yes" +2, "No" +0)
  5. Set base points
  6. Set display order
  7. Submit
- **Verify**:
  - Question created
  - Bonus points saved correctly
  - Preview shows point values

#### 9. Edit Question
- **Steps**:
  1. Click edit on a question
  2. Modify fields
  3. Change bonus points on options
  4. Save
- **Verify**:
  - Changes saved
  - Bonus points updated
  - Warning shown if answers exist

#### 10. Reorder Questions
- **Steps**:
  1. Go to questions list
  2. Drag and drop questions
  3. Click "Save Order"
- **Verify**: Order saved and persists on page refresh

---

### PHASE 4: Admin Grading System ⭐ CRITICAL

#### 11. Access Grading Interface
- **URL**: http://127.0.0.1:8000/admin/games/{id}/grading
- **Verify**:
  - Group selector dropdown visible
  - All groups appear in dropdown
  - Instructions clear

#### 12. Set Group-Specific Answers
- **Steps**:
  1. Select a group from dropdown
  2. Question list appears
  3. For each question:
     - **Multiple Choice**: Select correct option from dropdown
     - **Yes/No**: Select Yes or No
     - **Numeric**: Enter number
     - **Text**: Enter text answer
  4. Click "Set Answer"
- **Verify**:
  - Visual indicator changes (checkmark or color)
  - Success message appears
  - Answer persists on page refresh

#### 13. Toggle Void Status
- **Steps**:
  1. Click "Void" button on a question
  2. Confirm action
- **Verify**:
  - Button changes to "Unvoid"
  - Question marked as voided (visual indicator)
  - Voided questions award 0 points

#### 14. Calculate Scores
- **Steps**:
  1. Ensure answers set for all questions for at least one group
  2. Click "Calculate Scores" button
  3. Wait for success message
- **Verify**:
  - Success message shown
  - Leaderboards update
  - User scores calculated correctly using base + bonus points

#### 15. Export Results
- **Steps**:
  1. Click "Export Summary CSV"
  2. Click "Export Detailed CSV"
- **Verify**:
  - Both CSV files download
  - Summary CSV has columns: User, Group, Score, Percentage
  - Detailed CSV has all questions and answers

---

### PHASE 5: Guest User Flow ⭐ CRITICAL

#### 16. Generate Invitation Link (as Admin)
- **Steps**:
  1. Go to game details page
  2. Find "Game Invitations" section
  3. Select a group from dropdown
  4. Click "Generate Invitation"
  5. Copy the invitation URL
- **Verify**:
  - Invitation created
  - Token displayed
  - Copy button works
  - URL format: `/join/{token}`

#### 17. Join as Guest (Incognito/New Browser)
- **Steps**:
  1. Open incognito window
  2. Paste invitation URL
  3. Should see Guest/Join.vue page
  4. Enter name only (no password)
  5. Submit
- **Verify**:
  - Registration page loads
  - Name field only (no password field)
  - Submit works

#### 18. Guest Auto-Login
- **Verify**:
  - Automatically logged in after registration
  - Redirect to game page
  - Guest role assigned
  - Guest token created

#### 19. Guest Plays Game
- **Steps**:
  1. View game questions
  2. See point values displayed (base + bonus)
  3. Answer questions
  4. Save progress
  5. Navigate between questions
  6. Submit final answers
- **Verify**:
  - Point values visible: "Base: 5 pts + any bonus shown"
  - Bonus badges show on options: "+2 bonus"
  - Progress saves
  - Navigation works
  - Final submit works

#### 20. Confirmation Page ⭐ MOST CRITICAL
- **After submission, verify**:
  - Redirected to confirmation page
  - **HUGE YELLOW WARNING BOX** visible
  - Personal results link displayed: `/my-results/{guest_token}`
  - **Copy Link button** works
  - Instructions to save link clear:
    - "Save this link to view results anytime"
    - "No login required"
  - Shows score and percentage

#### 21. Personal Results Link (No Login Required)
- **Steps**:
  1. Logout or close browser
  2. Open personal results link directly: `/my-results/{token}`
- **Verify**:
  - Results page loads WITHOUT login prompt
  - Shows: score, percentage, answers
  - Shows leaderboard position
  - Shows game name and group
  - All data visible without authentication

---

### PHASE 6: User Dashboard & Game Playing

#### 22. Login as Regular User
- **Steps**:
  1. Logout from admin
  2. Login: user@propoff.com / password
  3. Should redirect to user dashboard
- **Verify**:
  - No "Admin" link in navigation
  - "My Games" link visible
  - Dashboard shows user statistics

#### 23. Browse Available Games
- **Steps**:
  1. Click "Browse Games" or go to /games
  2. View list of open games
- **Verify**:
  - Only "Open" games visible
  - Game cards show info
  - "Play" button visible

#### 24. Play Game Flow
- **Steps**:
  1. Click "Play" on a game
  2. Select group (if member of multiple)
  3. Start game
  4. See point values for each option
  5. Answer questions
  6. Save progress
  7. Submit final answers
- **Verify**:
  - Group selection works
  - Point values visible while answering
  - Bonus points shown: "+2 bonus"
  - Progress saves
  - Submission succeeds

#### 25. View Results
- **Steps**:
  1. Go to dashboard
  2. View "Recent Results"
  3. Click on a submission
- **Verify**:
  - Score displayed
  - Answers shown
  - Correct/incorrect marked

---

### PHASE 7: Admin User & Group Management

#### 26. Manage Users
- **URL**: http://127.0.0.1:8000/admin/users
- **Verify**:
  - Users list displays
  - Search works
  - Role filter works
  - Inline role changing works
  - View details works
  - Export CSV works
  - Bulk select and delete works

#### 27. Manage Groups
- **URL**: http://127.0.0.1:8000/admin/groups
- **Verify**:
  - Groups list displays
  - "Create Group" button works
  - Edit group works
  - Add/remove members works
  - View members works
  - Export CSV works
  - Bulk delete works

#### 28. Create Group
- **Steps**:
  1. Click "Create Group"
  2. Enter group name
  3. Submit
- **Verify**:
  - Group created
  - Unique code auto-generated
  - Redirect to group details

#### 29. Edit Group
- **Steps**:
  1. Click "Edit" on a group
  2. Modify name or code
  3. Save
- **Verify**:
  - Changes saved
  - Warning about code change shown
  - Update successful

---

### PHASE 8: Question Templates

#### 30. Create Template with Variables
- **URL**: http://127.0.0.1:8000/admin/question-templates/create
- **Steps**:
  1. Enter template name
  2. Select category
  3. Enter question text with variables: "Who will win {team1} vs {team2}?"
  4. Add variables: ["team1", "team2"]
  5. Add options using variables: ["{team1}", "{team2}", "Tie"]
  6. Submit
- **Verify**:
  - Template created
  - Variables saved
  - Preview shows variable placeholders

#### 31. Use Template with Variables
- **Steps**:
  1. Go to create questions for a game
  2. Click "+" on template with variables
  3. Modal appears
  4. Enter variable values: team1="Eagles", team2="Cowboys"
  5. Watch live preview update
  6. Submit
- **Verify**:
  - Modal displays
  - Live preview works
  - Variables substituted in created question
  - Final question text: "Who will win Eagles vs Cowboys?"

---

## Testing Priorities

### 🔴 CRITICAL - Test First

1. **Admin Grading Interface** ⭐
   - Set group-specific correct answers
   - Toggle void status
   - Calculate scores with base + bonus points
   - Verify scoring logic correct

2. **Guest User Flow** ⭐
   - Generate invitation link
   - Guest registration (name only)
   - Auto-login after registration
   - Play game and see point values
   - **Confirmation page with personal link** (MOST CRITICAL)
   - Personal results link (no login required)

3. **Weighted Scoring System** ⭐ NEW
   - Create questions with bonus points per option
   - Players see bonuses when answering
   - Scores calculated as: Base + Bonus
   - Max possible points calculated correctly

4. **Question Templates with Variables** ⭐
   - Create template with {variables}
   - Modal appears when using template
   - Live preview updates
   - Variables substituted correctly

### 🟡 HIGH PRIORITY

5. **Admin Game Management**
   - Create/edit games
   - View game details
   - Change game status
   - Duplicate games

6. **Admin Question Management**
   - Create questions from templates or custom
   - Edit questions (with bonus points)
   - Reorder questions
   - Delete questions

7. **User Game Playing**
   - Browse games
   - Play games
   - Submit answers
   - View results

8. **Leaderboards**
   - Global leaderboard
   - Group-specific leaderboard
   - User positions and rankings

### 🟢 MEDIUM PRIORITY

9. **Admin User Management**
   - List users
   - Change roles
   - Delete users
   - Export CSV

10. **Admin Group Management**
    - Create/edit groups
    - Manage members
    - Delete groups
    - Export CSV

11. **User Dashboard**
    - Statistics display
    - Active games list
    - Recent results

12. **Profile & Settings**
    - Update profile
    - Change password
    - Avatar upload

---

## Bug Reporting

When you find an issue, please provide:

### Bug Report Template
```
**Issue Title**: Brief description

**What I Tried**:
1. Step 1
2. Step 2
3. Step 3

**Expected Result**:
What should have happened

**Actual Result**:
What actually happened

**Error Messages**:
- On-screen error: [paste message]
- Browser console error (F12 → Console): [paste error]

**Environment**:
- URL: http://127.0.0.1:8000/path
- Browser: Chrome/Firefox/Safari
- User Account: Admin/User/Guest
- Screenshot: [if applicable]
```

### How to Check Browser Console
1. Press **F12** (or right-click → Inspect)
2. Click **Console** tab
3. Look for red error messages
4. Copy and paste entire error

### Common Issues to Check
- **500 Server Error**: Check Laravel logs in `source/storage/logs/laravel.log`
- **404 Not Found**: Route might not be registered
- **403 Forbidden**: Authorization issue (Policy/Middleware)
- **Ziggy Error**: Run `php artisan ziggy:generate` and hard refresh browser

---

## Additional Test Scripts

### Run All Tests at Once
```bash
# Backend tests
php docs/test-backend.php

# Database check
php docs/test-data.php
```

### Check Component Files
```bash
# Verify all Vue components exist
cd source/resources/js/Pages
ls -R | grep .vue
```

### Check Routes
```bash
cd source
php artisan route:list | grep admin
php artisan route:list | grep guest
```

---

## Success Criteria

### System is Working When:

✅ **Backend**:
- All 26 automated tests pass
- Database has test data
- All models, services, controllers exist
- Routes registered

✅ **Admin Features**:
- Can create/edit games
- Can create questions with bonus points
- Can set group-specific answers
- Can calculate scores correctly
- Can export CSV

✅ **Guest Features**:
- Can generate invitation links
- Guests can register with name only
- Auto-login works
- Confirmation page shows personal link
- Personal link works without login

✅ **Scoring**:
- Base points + bonus points calculated correctly
- Players see point values when answering
- Grading uses predefined points
- Leaderboards update correctly

✅ **User Features**:
- Can browse and play games
- Can see point values while playing
- Can submit answers
- Can view results

---

## Known Issues

### None Currently
(This section will be updated as testing progresses)

---

## Next Steps After Testing

### Phase 1: Fix Critical Bugs
- Address any blocking issues immediately
- Re-test after fixes
- Document resolutions

### Phase 2: Polish & Enhancement
- UI/UX improvements
- Performance optimization
- Additional features

### Phase 3: Production Readiness
- Security audit
- Load testing
- Backup strategy
- Deployment configuration

---

## Ready to Test!

✅ **Backend**: 100% functional
✅ **Components**: All exist
✅ **Test Data**: Populated
✅ **Servers**: Ready to start

**Start Here**: http://127.0.0.1:8000/login

Good luck with testing! Report any issues you find.

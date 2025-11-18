# Guest User Flow - Final Specification

**Date**: November 18, 2025
**Status**: Ready to Implement

---

## Complete User Journey

### 1. Admin Creates Game & Generates Invitation
```
Admin → Creates Game "Super Bowl 2025"
Admin → Creates Groups: "Friends", "Family", "Coworkers"
Admin → Goes to Game Details
Admin → Clicks "Generate Invitation"
Admin → Selects Group: "Friends"
Admin → System generates link: https://propoff.com/join/abc123xyz
Admin → Copies link and shares via text/email/WhatsApp
```

### 2. Guest Receives & Clicks Link
```
Guest receives text: "Join my Super Bowl predictions! https://propoff.com/join/abc123xyz"
Guest clicks link
Guest lands on beautiful registration page showing:
  - Game name: "Super Bowl 2025"
  - Event date
  - Group: "Friends"
  - Simple form: Name (required), Email (optional)
Guest enters: "John Smith"
Guest clicks "Join Game"
```

### 3. System Auto-Creates Guest Account
```
System creates User record:
  - name: "John Smith"
  - email: null (or john@email.com if provided)
  - password: null
  - role: 'guest'
  - guest_token: "xyz789abc" (unique 32-char token)

System adds user to group "Friends"
System increments invitation usage counter
System auto-logs in guest (session)
System redirects to game play page
```

### 4. Guest Plays Game
```
Guest sees questions and answer options
Guest answers all 20 questions
Guest clicks "Submit"
System grades submission
System redirects to CONFIRMATION PAGE ⭐
```

### 5. Guest Sees Submission Confirmation ⭐ KEY PAGE
```
┌─────────────────────────────────────────────────────────┐
│                  ✅ Submission Complete!                 │
│                                                          │
│                    Your Score: 85%                       │
│                   (17 out of 20 points)                  │
│                                                          │
│  ⚠️ IMPORTANT: Save This Link!                          │
│  ┌────────────────────────────────────────────────────┐ │
│  │ https://propoff.com/my-results/xyz789abc           │ │
│  │ [Copy Link]                                        │ │
│  └────────────────────────────────────────────────────┘ │
│                                                          │
│  You'll need this link to:                              │
│  • View your results later                              │
│  • See final scores after grading                       │
│  • Update answers (before deadline)                     │
│  • Check leaderboard rankings                           │
│                                                          │
│  💡 Tip: Bookmark this page or email link to yourself!  │
│                                                          │
│  [View Leaderboard]  [Review Answers]                   │
└─────────────────────────────────────────────────────────┘
```

### 6. Guest Saves Link (Multiple Ways)
```
Option 1: Guest clicks "Copy Link" button
  → Link copied to clipboard
  → Guest pastes in Notes app / Email to self / Text to self

Option 2: Guest bookmarks the confirmation page
  → Browser bookmark saved

Option 3: Guest takes screenshot
  → Has visual record of link

Option 4 (Phase 2): Guest entered email during registration
  → System sends email with link
  → Guest has permanent record in inbox
```

### 7. Guest Returns Later (Via Personal Link)
```
Hours/Days later...
Guest opens saved link: https://propoff.com/my-results/xyz789abc

System looks up user by guest_token "xyz789abc"
System loads "My Results" page showing:
  - All guest's submissions
  - Scores and percentages
  - Completion status
  - Links to view/update each submission

Guest clicks "Continue" on a submission
  → Can update answers (if before deadline)
  → Can view answers (if after deadline)
  → Can see leaderboard
```

### 8. Guest Returns Later (While Still Logged In)
```
If guest never closed browser:
  → Session still active
  → Guest can navigate normally
  → No link needed

If guest closed browser but has cookie:
  → Might auto-login (browser dependent)
  → Otherwise needs personal link
```

---

## Key Design Decisions

### ✅ Confirmed:
1. **Email Optional**: Guest can provide email during registration (Phase 2 will send link via email)
2. **Prominent Link Display**: Big, yellow warning box with clear instructions
3. **Copy Button**: One-click copy to clipboard
4. **Multiple Access Methods**: Session + Personal Link for redundancy
5. **Clear Messaging**: Guest understands importance of saving link
6. **Prepared for Phase 2**: Email field exists, just not sending yet

### ❌ Not Doing Now (Phase 2):
1. Automatic email sending with personal link
2. QR code generation for links
3. Admin bulk email invitations
4. Password recovery for guests (they don't have passwords)

---

## Technical Implementation

### Database Changes:
```sql
-- Add to users table
ALTER TABLE users 
  MODIFY email VARCHAR(255) NULL,
  MODIFY password VARCHAR(255) NULL,
  ADD guest_token VARCHAR(32) UNIQUE NULL AFTER role;

-- New table
CREATE TABLE game_group_invitations (
  id BIGINT PRIMARY KEY,
  game_id BIGINT,
  group_id BIGINT,
  token VARCHAR(32) UNIQUE,
  times_used INT DEFAULT 0,
  max_uses INT NULL,
  expires_at TIMESTAMP NULL,
  is_active BOOLEAN DEFAULT TRUE,
  UNIQUE(game_id, group_id)
);
```

### New Routes:
```php
// Public (no auth)
GET  /join/{token}              → Guest registration page
POST /join/{token}              → Create guest user
GET  /my-results/{guestToken}   → Guest results page (no auth needed!)

// Auth required (guest or user)
GET  /submissions/{id}/confirmation → Confirmation page with personal link

// Admin only
POST /games/{game}/generate-invitation  → Generate link
POST /games/{game}/invitations/{id}/deactivate → Deactivate link
```

### Security:
```php
// Guests can ONLY access:
- Their own submissions
- Their own results
- Public leaderboards
- Games they're invited to

// Guests CANNOT access:
- Admin pages
- Other users' data
- Create games/groups
- Manage questions
```

---

## Phase 2 Enhancements (Future)

### Email Integration:
```php
// After guest submits
if ($user->email) {
    Mail::to($user->email)->send(new SubmissionConfirmation([
        'name' => $user->name,
        'score' => $submission->percentage,
        'personal_link' => route('guest.results', $user->guest_token),
    ]));
}
```

### QR Code Generation:
```php
// In admin game show page
$qrCode = QrCode::size(300)->generate(route('guest.join', $invitation->token));
```

### Invitation Analytics:
```php
// Track invitation performance
- Times used
- Conversion rate
- Average score by invitation
- Most popular groups
```

---

## Success Metrics

### Guest Registration:
- [x] Guest can join with name only
- [x] Guest auto-logged in
- [x] Guest added to correct group
- [x] Email captured for Phase 2

### Link Prominence:
- [x] Personal link shown in big yellow warning box
- [x] Copy button works
- [x] Clear instructions why link is important
- [x] Multiple saving methods suggested

### Access Methods:
- [x] Guest can access via session (while logged in)
- [x] Guest can access via personal link (anytime)
- [x] Personal link works without login
- [x] Link is permanent and doesn't expire

### Security:
- [x] Guest cannot access admin features
- [x] Guest can only see own data
- [x] Invitation can be deactivated
- [x] Usage tracking prevents abuse

---

## User Experience Goals

### Guest Perspective:
- ✅ "That was easy! Just entered my name"
- ✅ "I can clearly see my results"
- ✅ "I know I need to save this link"
- ✅ "I can copy the link with one click"
- ✅ "I can come back anytime"

### Admin Perspective:
- ✅ "Easy to generate invitation links"
- ✅ "I can see who's using each link"
- ✅ "I can deactivate links if needed"
- ✅ "One link per group keeps things organized"

---

## Ready to Build! 🚀

All specifications confirmed. Implementation can proceed with:
1. Database migrations
2. Models and relationships
3. Guest controller logic
4. Submission confirmation page ⭐
5. Admin invitation management
6. Testing

Estimated time: 8-12 hours
Phase 2 additions: 4-6 hours (email + QR codes)

# QR Code Guest User Flow - Design Proposal

**Date**: November 18, 2025
**Requirement**: Allow guest users to join games via QR code without authentication

---

## Current System Analysis

### What Exists Now:
- ✅ Full authentication system (Laravel Breeze)
- ✅ User model with role field (admin/user)
- ✅ Groups with unique codes
- ✅ User-Group relationship (pivot table)
- ✅ Submissions tied to user_id
- ✅ Leaderboards tied to user_id

### What Needs to Change:
- Users currently must register/login before participating
- Groups require manual code entry
- No QR code generation
- No guest user support

---

## Proposed Solutions

### **Option 1: Guest Users with Auto-Registration** ⭐ RECOMMENDED

**How it works:**
1. Admin creates game
2. System generates unique QR code for the game (or per game-group combination)
3. QR code contains URL: `https://propoff.com/join/{unique-token}`
4. Guest scans QR code → lands on guest registration page
5. Guest enters: Name, Email (optional)
6. System creates user account with:
   - role: 'guest'
   - password: null (or random hash)
   - email_verified_at: null (skip email verification)
   - Auto-assigns to group from QR code
7. Guest can play game immediately (no login required after initial registration)
8. Session stored in browser keeps them "logged in" for duration

**Pros:**
- ✅ Preserves user tracking and statistics
- ✅ No changes to existing database structure
- ✅ Maintains data integrity (all submissions have user_id)
- ✅ Guests can return later to see results (via unique link)
- ✅ Can upgrade guest to full user later if they want

**Cons:**
- ⚠️ Guests need to enter name each time if they clear cookies
- ⚠️ No password recovery for guests (not an issue since they don't have passwords)

**Changes Required:**
- Add 'guest' role to users table
- Create QR code generation for games/groups
- Create guest registration flow (simplified)
- Skip email verification for guests
- Auto-login after guest registration
- Generate shareable link for guests to return

---

### **Option 2: Session-Based Anonymous Users**

**How it works:**
1. QR code → guest lands on game page
2. Guest enters name (stored in session only)
3. No user record created initially
4. On submission, create user with role 'anonymous'
5. Session ties them to their submission

**Pros:**
- ✅ Fastest for guests (fewer steps)
- ✅ No account creation unless they submit

**Cons:**
- ❌ Can't track user across sessions
- ❌ Can't see results later if session expires
- ❌ Harder to prevent duplicate submissions
- ❌ More complex session management

---

### **Option 3: Temporary User Tokens**

**How it works:**
1. QR code contains temp token
2. Guest enters name
3. System creates temporary user with:
   - Unique token instead of email
   - Expires after X days
4. Token allows them to return to see results

**Pros:**
- ✅ No email required
- ✅ Can return to see results via token link

**Cons:**
- ❌ Requires token management system
- ❌ Need cleanup job for expired tokens
- ❌ More complex than Option 1

---

## Recommended Approach: **Option 1 - Guest Users with Auto-Registration**

### Implementation Plan

#### 1. Database Changes

**Add to users table migration:**
```php
// Already has 'role' field, just need to ensure 'guest' is allowed
// Modify validation to accept role: 'admin', 'user', 'guest'
```

**Create new table: `game_invitations`**
```php
Schema::create('game_invitations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('game_id')->constrained()->onDelete('cascade');
    $table->foreignId('group_id')->nullable()->constrained()->onDelete('cascade');
    $table->string('token')->unique(); // For QR code URL
    $table->integer('max_uses')->nullable(); // Limit how many can use it
    $table->integer('times_used')->default(0);
    $table->timestamp('expires_at')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

#### 2. QR Code Generation

**Create Service: `app/Services/QRCodeService.php`**
- Use package: `simplesoftwareio/simple-qrcode`
- Generate QR code for game invitation
- Store QR code image or generate on-the-fly
- Include game/group info in URL

**URL Structure:**
- Single game: `/join/game/{token}`
- Game + specific group: `/join/game/{token}?group={groupId}`

#### 3. Guest Registration Flow

**New Route:**
```php
Route::get('/join/game/{token}', [GuestController::class, 'show'])->name('guest.join');
Route::post('/join/game/{token}', [GuestController::class, 'register'])->name('guest.register');
```

**New Controller: `GuestController`**
```php
public function show($token)
{
    // Validate token
    // Show game info
    // Display simple form: Name, Email (optional)
}

public function register(Request $request, $token)
{
    // Validate invitation
    // Create guest user
    // Auto-login
    // Redirect to game
}
```

**Guest Registration Form (Simple):**
```
Welcome to [Game Name]!
━━━━━━━━━━━━━━━━━━━━━━━━
Enter your name to get started:

Name: [____________]
Email (optional): [____________]

[Join Game]
```

#### 4. Middleware Changes

**Modify authentication middleware:**
- Allow guest users to access game play pages
- Guests can only access their own submissions
- Guests cannot access admin areas
- Guests cannot create games/groups

**New Middleware: `AllowGuests`**
```php
// Allow both authenticated users and guest users
if (!auth()->check() || !in_array(auth()->user()->role, ['user', 'guest', 'admin'])) {
    abort(403);
}
```

#### 5. User Model Changes

**Add to User model:**
```php
public function isGuest()
{
    return $this->role === 'guest';
}

public function isRegistered()
{
    return $this->role !== 'guest';
}

// Make password nullable for guests
protected $fillable = [
    'name',
    'email', // Make nullable
    'password', // Make nullable
    'role',
];

protected $hidden = [
    'password',
    'remember_token',
];

protected $casts = [
    'email_verified_at' => 'datetime',
    'password' => 'hashed',
];
```

#### 6. Admin UI Changes

**Add to Admin/Games/Show.vue:**
- New section: "Share this Game"
- QR Code display
- Copy link button
- Download QR code button
- Email template with QR code

**New Component: `Admin/Games/ShareGame.vue`**
```vue
<template>
  <div class="bg-white p-6 rounded-lg shadow">
    <h3>Share this Game</h3>
    
    <!-- QR Code Display -->
    <div class="qr-code">
      <img :src="qrCodeUrl" alt="QR Code" />
    </div>
    
    <!-- Copy Link -->
    <input readonly :value="joinUrl" />
    <button @click="copyLink">Copy Link</button>
    
    <!-- Download QR -->
    <button @click="downloadQR">Download QR Code</button>
    
    <!-- Email Template -->
    <button @click="showEmailTemplate">Email Template</button>
  </div>
</template>
```

#### 7. Guest User Experience

**Flow:**
1. Guest scans QR code
2. Lands on `/join/game/{token}`
3. Sees game details and simple form
4. Enters name (and optional email)
5. Clicks "Join Game"
6. System creates guest user account
7. Auto-login via session
8. Redirects to game play page
9. Guest plays and submits answers
10. Guest sees results on leaderboard
11. Guest gets unique link to return later: `/my-results/{token}`

**Return Visit:**
- Guest can bookmark `/my-results/{uniqueToken}`
- Or receive email with link (if they provided email)
- Can see their submission and leaderboard position

#### 8. Security Considerations

**Prevent abuse:**
- Rate limit QR code registrations (max 10 per IP per hour)
- Token expiration (e.g., QR code expires after event date)
- Max uses per token (optional)
- CAPTCHA for guest registration (optional, if spam is concern)

**Data integrity:**
- Guest users can only see/edit their own submissions
- Guest users cannot access other users' data
- Guest users cannot create games/groups
- Guest users auto-expire after X days of inactivity (optional cleanup job)

#### 9. Email Integration (Optional)

**If admin wants to email QR code:**
```php
// Create mailable: InviteToGame
Mail::to($emails)->send(new InviteToGame($game, $qrCodeUrl));
```

**Email Template:**
```html
You're invited to play [Game Name]!

Scan this QR code or click the link below to join:
[QR Code Image]

Or visit: {joinUrl}

Event Date: {eventDate}
```

---

## File Structure Changes

### New Files to Create:

**Backend:**
1. `database/migrations/XXXX_create_game_invitations_table.php`
2. `app/Models/GameInvitation.php`
3. `app/Http/Controllers/GuestController.php`
4. `app/Http/Middleware/AllowGuests.php`
5. `app/Services/QRCodeService.php`
6. `app/Mail/InviteToGame.php` (optional)
7. `app/Http/Requests/GuestRegistrationRequest.php`

**Frontend:**
1. `resources/js/Pages/Guest/Join.vue` (guest registration page)
2. `resources/js/Pages/Guest/MyResults.vue` (guest results page)
3. `resources/js/Pages/Admin/Games/ShareGame.vue` (admin share UI)
4. `resources/js/Components/QRCode.vue` (reusable QR display)

**Routes:**
```php
// Guest routes (no auth required)
Route::get('/join/game/{token}', [GuestController::class, 'show'])->name('guest.join');
Route::post('/join/game/{token}', [GuestController::class, 'register'])->name('guest.register');
Route::get('/my-results/{token}', [GuestController::class, 'results'])->name('guest.results');

// Admin routes (add to existing admin group)
Route::post('/games/{game}/generate-invite', [Admin\GameController::class, 'generateInvite'])->name('games.generateInvite');
Route::get('/games/{game}/qr-code', [Admin\GameController::class, 'downloadQR'])->name('games.downloadQR');
```

---

## Package Requirements

**Install QR Code Package:**
```bash
composer require simplesoftwareio/simple-qrcode
```

---

## Migration Path

### Phase A: Foundation (2-3 hours)
1. Add 'guest' role support to User model
2. Create game_invitations table
3. Create GameInvitation model
4. Update User validation to allow nullable email/password

### Phase B: QR Code Generation (2-3 hours)
1. Install QR code package
2. Create QRCodeService
3. Add generate invite method to Admin\GameController
4. Create QR code display component

### Phase C: Guest Registration (3-4 hours)
1. Create GuestController
2. Create Guest/Join.vue page
3. Implement guest registration logic
4. Auto-login after registration

### Phase D: Admin UI (2-3 hours)
1. Add "Share Game" section to Games/Show.vue
2. Create ShareGame component
3. Add copy link functionality
4. Add download QR code button

### Phase E: Testing & Polish (2-3 hours)
1. Test guest flow end-to-end
2. Test guest results view
3. Test security (guest can't access admin)
4. Test rate limiting

**Total Estimated Time: 11-16 hours**

---

## Alternative: Simpler MVP Approach

**If you want something faster (4-6 hours):**

1. **Skip QR code generation** - Just use shareable link
2. **Skip game_invitations table** - Use encrypted game ID in URL
3. **Simplified guest registration:**
   - Route: `/join/{encryptedGameId}`
   - Guest enters name only
   - Auto-create user with role 'guest'
   - Auto-join default group for game

**URL Example:**
```
https://propoff.com/join/abc123xyz
```

Where `abc123xyz` = encrypted game ID + group ID

**Benefits:**
- ✅ Faster to implement
- ✅ No QR code package needed
- ✅ No additional database table
- ✅ Still maintains user tracking

**Later you can add:**
- QR code generation
- Invitation management
- Email integration

---

## Questions for You:

1. **Do you want actual QR codes, or would shareable links be sufficient initially?**

2. **Should guests be able to:**
   - See results after submission? ✓ or ✗
   - Return later to view results? ✓ or ✗
   - Update their submission before deadline? ✓ or ✗

3. **Email integration:**
   - Do you want admins to send email invites with QR codes?
   - Or just display QR code for admin to share manually?

4. **Group assignment:**
   - Should each QR code be tied to a specific group?
   - Or should guests choose a group after scanning?
   - Or auto-assign all guests to one "default" group?

5. **Guest data retention:**
   - Keep guest users forever?
   - Auto-delete after 30 days of inactivity?
   - Convert to full users if they set a password later?

6. **Spam prevention:**
   - Add CAPTCHA to guest registration?
   - IP-based rate limiting sufficient?

---

## My Recommendation:

**Start with Simpler MVP:**
1. Shareable links (can add QR later)
2. Guest role with name-only registration
3. Auto-assign to default group per game
4. Allow guests to see results
5. Simple unique link to return to results

**Then enhance with:**
1. QR code generation
2. Per-group invitations
3. Email integration
4. Admin invitation management

This gets you functional guest access quickly, then you can polish based on real usage.

---

**What do you think? Which approach do you prefer?**

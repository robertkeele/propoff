# Captain System Implementation Plan

**Date**: November 20, 2025
**Objective**: Implement Captain role with group-level question customization and dual grading model

---

## Executive Summary

### Key Changes:
1. **Terminology**: Games → Events throughout entire application
2. **Captain Role**: Per-group captaincy via `user_groups.is_captain`
3. **Question Architecture**: 3-tier system (Templates → Event Questions → Group Questions)
4. **Dual Grading**: Captain-set answers OR Admin-set answers (captain chooses)
5. **Captain Autonomy**: Full control over their group's questions and grading

### User Decisions:
- ✅ Anyone with admin link can create group (become captain)
- ✅ Multiple captains per group have equal control
- ✅ Captains can modify questions even after event starts
- ✅ Admin answers hidden until after event ends
- ✅ Custom captain questions isolated to their group only
- ✅ No cross-group leaderboard comparison (different questions)

---

## Architecture Overview

### Current System:
```
Admin → Game → Questions → Group plays → Submissions → Scoring
                              ↓
                    Group-specific answers
```

### New System:
```
Admin → Event Template → Event Questions (variables filled)
                              ↓
        Captain Link → Create Group → Inherits Event Questions
                              ↓
                    Captain Customizes (add/remove/modify)
                              ↓
                         Group Questions
                              ↓
        Player Link → Join Group → Play Game → Submit Answers
                              ↓
        Captain Grades (OR auto-use admin answers)
                              ↓
                    Group Leaderboard (isolated)
```

---

## Database Schema Changes

### 1. Rename `games` table → `events`

```sql
-- Step 1: Rename table
RENAME TABLE games TO events;

-- Step 2: Update foreign key columns in other tables
ALTER TABLE questions CHANGE game_id event_id BIGINT UNSIGNED NOT NULL;
ALTER TABLE submissions CHANGE game_id event_id BIGINT UNSIGNED NOT NULL;
ALTER TABLE leaderboards CHANGE game_id event_id BIGINT UNSIGNED NOT NULL;
ALTER TABLE user_groups CHANGE game_id event_id BIGINT UNSIGNED NOT NULL;
ALTER TABLE game_group_invitations CHANGE game_id event_id BIGINT UNSIGNED NOT NULL;

-- Step 3: Rename the table to event_group_invitations
RENAME TABLE game_group_invitations TO event_invitations;
-- Adjust this table for captain invitations (see below)
```

### 2. Add `is_captain` to `user_groups` table

```sql
ALTER TABLE user_groups ADD COLUMN is_captain BOOLEAN DEFAULT FALSE AFTER joined_at;
ALTER TABLE user_groups ADD INDEX idx_captain (is_captain);
```

### 3. Rename `questions` → `event_questions`

```sql
RENAME TABLE questions TO event_questions;
```

### 4. Create `group_questions` table

```sql
CREATE TABLE group_questions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    group_id BIGINT UNSIGNED NOT NULL,
    event_question_id BIGINT UNSIGNED NULL, -- NULL if custom question
    question_text TEXT NOT NULL,
    question_type ENUM('multiple_choice', 'yes_no', 'numeric', 'text') NOT NULL,
    options JSON NULL,
    points INT DEFAULT 1,
    order INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_custom BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
    FOREIGN KEY (event_question_id) REFERENCES event_questions(id) ON DELETE SET NULL,

    INDEX idx_group_id (group_id),
    INDEX idx_event_question_id (event_question_id),
    INDEX idx_is_active (is_active),
    INDEX idx_order (order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 5. Update `user_answers` to reference `group_questions`

```sql
ALTER TABLE user_answers ADD COLUMN group_question_id BIGINT UNSIGNED NULL AFTER question_id;
ALTER TABLE user_answers ADD FOREIGN KEY (group_question_id) REFERENCES group_questions(id) ON DELETE CASCADE;
ALTER TABLE user_answers ADD INDEX idx_group_question_id (group_question_id);

-- Later we'll migrate data from question_id to group_question_id
-- Then we can drop question_id column
```

### 6. Add `grading_source` to `groups` table

```sql
ALTER TABLE groups ADD COLUMN grading_source ENUM('captain', 'admin') DEFAULT 'captain' AFTER join_code;
ALTER TABLE groups ADD INDEX idx_grading_source (grading_source);
```

### 7. Create `event_answers` table (admin-set answers)

```sql
CREATE TABLE event_answers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id BIGINT UNSIGNED NOT NULL,
    event_question_id BIGINT UNSIGNED NOT NULL,
    correct_answer TEXT NOT NULL,
    is_void BOOLEAN DEFAULT FALSE,
    set_at TIMESTAMP NULL,
    set_by BIGINT UNSIGNED NULL, -- admin user who set it
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (event_question_id) REFERENCES event_questions(id) ON DELETE CASCADE,
    FOREIGN KEY (set_by) REFERENCES users(id) ON DELETE SET NULL,

    UNIQUE KEY unique_event_question (event_id, event_question_id),
    INDEX idx_event_id (event_id),
    INDEX idx_event_question_id (event_question_id),
    INDEX idx_is_void (is_void)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 8. Update `group_question_answers` to reference `group_questions`

```sql
ALTER TABLE group_question_answers ADD COLUMN group_question_id BIGINT UNSIGNED NULL AFTER question_id;
ALTER TABLE group_question_answers ADD FOREIGN KEY (group_question_id) REFERENCES group_questions(id) ON DELETE CASCADE;

-- Later migrate data from question_id to group_question_id
-- Then drop question_id column
```

### 9. Create `captain_invitations` table (separate from player invitations)

```sql
CREATE TABLE captain_invitations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id BIGINT UNSIGNED NOT NULL,
    token VARCHAR(32) UNIQUE NOT NULL,
    max_uses INT NULL, -- NULL = unlimited
    times_used INT DEFAULT 0,
    expires_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_by BIGINT UNSIGNED NOT NULL, -- admin who created
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,

    INDEX idx_token (token),
    INDEX idx_event_id (event_id),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Note**: Keep existing `event_invitations` table for player invitations (renamed from game_group_invitations)

---

## Model Changes

### 1. Rename `Game.php` → `Event.php`

**File**: `app/Models/Event.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'event_date',
        'lock_date',
        'status',
    ];

    protected $casts = [
        'event_date' => 'datetime',
        'lock_date' => 'datetime',
    ];

    // Relationships
    public function eventQuestions()
    {
        return $this->hasMany(EventQuestion::class);
    }

    public function groups()
    {
        return $this->hasMany(Group::class);
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }

    public function leaderboards()
    {
        return $this->hasMany(Leaderboard::class);
    }

    public function eventAnswers()
    {
        return $this->hasMany(EventAnswer::class);
    }

    public function captainInvitations()
    {
        return $this->hasMany(CaptainInvitation::class);
    }

    public function playerInvitations()
    {
        return $this->hasMany(EventInvitation::class);
    }
}
```

### 2. Rename `Question.php` → `EventQuestion.php`

**File**: `app/Models/EventQuestion.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'question_template_id',
        'question_text',
        'question_type',
        'options',
        'points',
        'order',
    ];

    protected $casts = [
        'options' => 'array',
        'points' => 'integer',
        'order' => 'integer',
    ];

    // Relationships
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function questionTemplate()
    {
        return $this->belongsTo(QuestionTemplate::class);
    }

    public function groupQuestions()
    {
        return $this->hasMany(GroupQuestion::class);
    }

    public function eventAnswers()
    {
        return $this->hasMany(EventAnswer::class);
    }
}
```

### 3. Create `GroupQuestion.php` model

**File**: `app/Models/GroupQuestion.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'event_question_id',
        'question_text',
        'question_type',
        'options',
        'points',
        'order',
        'is_active',
        'is_custom',
    ];

    protected $casts = [
        'options' => 'array',
        'points' => 'integer',
        'order' => 'integer',
        'is_active' => 'boolean',
        'is_custom' => 'boolean',
    ];

    // Relationships
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function eventQuestion()
    {
        return $this->belongsTo(EventQuestion::class);
    }

    public function userAnswers()
    {
        return $this->hasMany(UserAnswer::class);
    }

    public function groupQuestionAnswer()
    {
        return $this->hasOne(GroupQuestionAnswer::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
```

### 4. Create `EventAnswer.php` model (admin answers)

**File**: `app/Models/EventAnswer.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'event_question_id',
        'correct_answer',
        'is_void',
        'set_at',
        'set_by',
    ];

    protected $casts = [
        'is_void' => 'boolean',
        'set_at' => 'datetime',
    ];

    // Relationships
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function eventQuestion()
    {
        return $this->belongsTo(EventQuestion::class);
    }

    public function setBy()
    {
        return $this->belongsTo(User::class, 'set_by');
    }
}
```

### 5. Create `CaptainInvitation.php` model

**File**: `app/Models/CaptainInvitation.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CaptainInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'token',
        'max_uses',
        'times_used',
        'expires_at',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'max_uses' => 'integer',
        'times_used' => 'integer',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Methods
    public static function generateToken()
    {
        return Str::random(32);
    }

    public function getUrl()
    {
        return url("/events/{$this->event_id}/create-group/{$this->token}");
    }

    public function canBeUsed()
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

    public function incrementUsage()
    {
        $this->increment('times_used');
    }
}
```

### 6. Update `Group.php` model

Add new relationships and captain-related methods:

```php
// Add to existing Group model

public function captains()
{
    return $this->belongsToMany(User::class, 'user_groups')
        ->wherePivot('is_captain', true)
        ->withPivot('joined_at')
        ->withTimestamps();
}

public function groupQuestions()
{
    return $this->hasMany(GroupQuestion::class);
}

public function activeGroupQuestions()
{
    return $this->hasMany(GroupQuestion::class)->active()->ordered();
}

public function isCaptain($userId)
{
    return $this->members()
        ->wherePivot('user_id', $userId)
        ->wherePivot('is_captain', true)
        ->exists();
}

public function promoteToCapta($userId)
{
    $this->members()->updateExistingPivot($userId, ['is_captain' => true]);
}

public function demoteFromCaptain($userId)
{
    $this->members()->updateExistingPivot($userId, ['is_captain' => false]);
}
```

### 7. Update `User.php` model

Add captain-related methods:

```php
// Add to existing User model

public function captainGroups()
{
    return $this->belongsToMany(Group::class, 'user_groups')
        ->wherePivot('is_captain', true)
        ->withPivot('joined_at')
        ->withTimestamps();
}

public function isCaptainOf($groupId)
{
    return $this->captainGroups()->where('groups.id', $groupId)->exists();
}

public function canManageGroup($groupId)
{
    // Admin or captain of the group
    return $this->role === 'admin' || $this->isCaptainOf($groupId);
}
```

---

## Migration Files

### Migration 1: Rename games to events

**File**: `database/migrations/2025_11_20_000001_rename_games_to_events.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Rename games table to events
        Schema::rename('games', 'events');

        // Update foreign key columns in other tables
        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign(['game_id']);
            $table->renameColumn('game_id', 'event_id');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
        });

        Schema::table('submissions', function (Blueprint $table) {
            $table->dropForeign(['game_id']);
            $table->renameColumn('game_id', 'event_id');
        });

        Schema::table('submissions', function (Blueprint $table) {
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
        });

        Schema::table('leaderboards', function (Blueprint $table) {
            $table->dropForeign(['game_id']);
            $table->renameColumn('game_id', 'event_id');
        });

        Schema::table('leaderboards', function (Blueprint $table) {
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
        });

        Schema::table('user_groups', function (Blueprint $table) {
            $table->dropForeign(['game_id']);
            $table->renameColumn('game_id', 'event_id');
        });

        Schema::table('user_groups', function (Blueprint $table) {
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
        });

        Schema::table('game_group_invitations', function (Blueprint $table) {
            $table->dropForeign(['game_id']);
            $table->renameColumn('game_id', 'event_id');
        });

        Schema::table('game_group_invitations', function (Blueprint $table) {
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
        });

        // Rename game_group_invitations to event_invitations
        Schema::rename('game_group_invitations', 'event_invitations');
    }

    public function down()
    {
        // Reverse all changes
        Schema::rename('event_invitations', 'game_group_invitations');

        Schema::table('game_group_invitations', function (Blueprint $table) {
            $table->dropForeign(['event_id']);
            $table->renameColumn('event_id', 'game_id');
        });

        Schema::table('game_group_invitations', function (Blueprint $table) {
            $table->foreign('game_id')->references('id')->on('games')->onDelete('cascade');
        });

        Schema::table('user_groups', function (Blueprint $table) {
            $table->dropForeign(['event_id']);
            $table->renameColumn('event_id', 'game_id');
        });

        Schema::table('user_groups', function (Blueprint $table) {
            $table->foreign('game_id')->references('id')->on('games')->onDelete('cascade');
        });

        Schema::table('leaderboards', function (Blueprint $table) {
            $table->dropForeign(['event_id']);
            $table->renameColumn('event_id', 'game_id');
        });

        Schema::table('leaderboards', function (Blueprint $table) {
            $table->foreign('game_id')->references('id')->on('games')->onDelete('cascade');
        });

        Schema::table('submissions', function (Blueprint $table) {
            $table->dropForeign(['event_id']);
            $table->renameColumn('event_id', 'game_id');
        });

        Schema::table('submissions', function (Blueprint $table) {
            $table->foreign('game_id')->references('id')->on('games')->onDelete('cascade');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign(['event_id']);
            $table->renameColumn('event_id', 'game_id');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->foreign('game_id')->references('id')->on('games')->onDelete('cascade');
        });

        Schema::rename('events', 'games');
    }
};
```

### Migration 2: Add is_captain to user_groups

**File**: `database/migrations/2025_11_20_000002_add_is_captain_to_user_groups.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('user_groups', function (Blueprint $table) {
            $table->boolean('is_captain')->default(false)->after('joined_at');
            $table->index('is_captain');
        });

        // Set existing group creators as captains
        DB::statement('
            UPDATE user_groups ug
            INNER JOIN groups g ON ug.group_id = g.id
            SET ug.is_captain = TRUE
            WHERE ug.user_id = g.created_by
        ');
    }

    public function down()
    {
        Schema::table('user_groups', function (Blueprint $table) {
            $table->dropIndex(['is_captain']);
            $table->dropColumn('is_captain');
        });
    }
};
```

### Migration 3: Rename questions to event_questions

**File**: `database/migrations/2025_11_20_000003_rename_questions_to_event_questions.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::rename('questions', 'event_questions');
    }

    public function down()
    {
        Schema::rename('event_questions', 'questions');
    }
};
```

### Migration 4: Create group_questions table

**File**: `database/migrations/2025_11_20_000004_create_group_questions_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('group_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->onDelete('cascade');
            $table->foreignId('event_question_id')->nullable()->constrained('event_questions')->onDelete('set null');
            $table->text('question_text');
            $table->enum('question_type', ['multiple_choice', 'yes_no', 'numeric', 'text']);
            $table->json('options')->nullable();
            $table->integer('points')->default(1);
            $table->integer('order');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_custom')->default(false);
            $table->timestamps();

            $table->index('group_id');
            $table->index('event_question_id');
            $table->index('is_active');
            $table->index('order');
        });

        // Migrate existing data: Create group questions from event questions for all groups
        DB::statement('
            INSERT INTO group_questions (
                group_id, event_question_id, question_text, question_type,
                options, points, `order`, is_active, is_custom, created_at, updated_at
            )
            SELECT
                g.id as group_id,
                eq.id as event_question_id,
                eq.question_text,
                eq.question_type,
                eq.options,
                eq.points,
                eq.`order`,
                TRUE as is_active,
                FALSE as is_custom,
                NOW() as created_at,
                NOW() as updated_at
            FROM groups g
            INNER JOIN event_questions eq ON g.event_id = eq.event_id
        ');
    }

    public function down()
    {
        Schema::dropIfExists('group_questions');
    }
};
```

### Migration 5: Add grading_source to groups

**File**: `database/migrations/2025_11_20_000005_add_grading_source_to_groups.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->enum('grading_source', ['captain', 'admin'])->default('captain')->after('join_code');
            $table->index('grading_source');
        });
    }

    public function down()
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropIndex(['grading_source']);
            $table->dropColumn('grading_source');
        });
    }
};
```

### Migration 6: Create event_answers table

**File**: `database/migrations/2025_11_20_000006_create_event_answers_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('event_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->foreignId('event_question_id')->constrained('event_questions')->onDelete('cascade');
            $table->text('correct_answer');
            $table->boolean('is_void')->default(false);
            $table->timestamp('set_at')->nullable();
            $table->foreignId('set_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->unique(['event_id', 'event_question_id']);
            $table->index('event_id');
            $table->index('event_question_id');
            $table->index('is_void');
        });
    }

    public function down()
    {
        Schema::dropIfExists('event_answers');
    }
};
```

### Migration 7: Create captain_invitations table

**File**: `database/migrations/2025_11_20_000007_create_captain_invitations_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('captain_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->string('token', 32)->unique();
            $table->integer('max_uses')->nullable();
            $table->integer('times_used')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index('token');
            $table->index('event_id');
            $table->index('is_active');
        });
    }

    public function down()
    {
        Schema::dropIfExists('captain_invitations');
    }
};
```

### Migration 8: Update user_answers for group_questions

**File**: `database/migrations/2025_11_20_000008_update_user_answers_for_group_questions.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('user_answers', function (Blueprint $table) {
            $table->foreignId('group_question_id')->nullable()->after('question_id')->constrained('group_questions')->onDelete('cascade');
            $table->index('group_question_id');
        });

        // Migrate existing data: Link user_answers to group_questions
        DB::statement('
            UPDATE user_answers ua
            INNER JOIN submissions s ON ua.submission_id = s.id
            INNER JOIN group_questions gq ON gq.group_id = s.group_id AND gq.event_question_id = ua.question_id
            SET ua.group_question_id = gq.id
        ');

        // After migration, we could drop question_id column, but keep it for now for safety
    }

    public function down()
    {
        Schema::table('user_answers', function (Blueprint $table) {
            $table->dropForeign(['group_question_id']);
            $table->dropIndex(['group_question_id']);
            $table->dropColumn('group_question_id');
        });
    }
};
```

### Migration 9: Update group_question_answers for group_questions

**File**: `database/migrations/2025_11_20_000009_update_group_question_answers_for_group_questions.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('group_question_answers', function (Blueprint $table) {
            $table->foreignId('group_question_id')->nullable()->after('question_id')->constrained('group_questions')->onDelete('cascade');
            $table->index('group_question_id');
        });

        // Migrate existing data
        DB::statement('
            UPDATE group_question_answers gqa
            INNER JOIN group_questions gq ON gq.group_id = gqa.group_id AND gq.event_question_id = gqa.question_id
            SET gqa.group_question_id = gq.id
        ');

        // After migration, we could drop question_id column, but keep it for now for safety
    }

    public function down()
    {
        Schema::table('group_question_answers', function (Blueprint $table) {
            $table->dropForeign(['group_question_id']);
            $table->dropIndex(['group_question_id']);
            $table->dropColumn('group_question_id');
        });
    }
};
```

---

## Controller Changes

### New Controllers to Create:

1. **`Captain\DashboardController`** - Captain dashboard showing their groups
2. **`Captain\GroupController`** - Create group from captain invitation
3. **`Captain\GroupQuestionController`** - Manage group questions (add/remove/modify)
4. **`Captain\GradingController`** - Set answers for their group
5. **`Captain\MemberController`** - Manage group members, promote captains
6. **`Admin\CaptainInvitationController`** - Generate captain invitation links
7. **`Admin\EventAnswerController`** - Set admin-level answers for events

### Controllers to Rename/Update:

1. **`GameController` → `EventController`** - Update all references
2. **`Admin\GameController` → `Admin\EventController`** - Update all references
3. **`Admin\QuestionController` → `Admin\EventQuestionController`** - Update for event_questions
4. **`Admin\GradingController`** - Update to use event_answers table
5. **`SubmissionService`** - Update to use group_questions and dual grading

---

## Implementation Phases

### Phase 1: Database Foundation (4-6 hours)
**Objective**: Rename games → events, create new tables

**Tasks**:
1. Create all 9 migration files
2. Run migrations on development database
3. Verify data integrity after migrations
4. Test rollback functionality
5. Update database seeder for new structure

**Deliverables**:
- ✅ events table (renamed from games)
- ✅ event_questions table (renamed from questions)
- ✅ group_questions table with migrated data
- ✅ event_answers table (empty, ready for use)
- ✅ captain_invitations table
- ✅ is_captain column in user_groups
- ✅ grading_source column in groups
- ✅ All foreign keys and indexes properly set

### Phase 2: Model Layer (3-4 hours)
**Objective**: Update all models for new structure

**Tasks**:
1. Rename Game.php → Event.php
2. Rename Question.php → EventQuestion.php
3. Create GroupQuestion.php
4. Create EventAnswer.php
5. Create CaptainInvitation.php
6. Update Group.php with captain methods
7. Update User.php with captain methods
8. Update all model relationships
9. Create model factories for new models
10. Test model relationships in tinker

**Deliverables**:
- ✅ All models renamed and updated
- ✅ All relationships defined correctly
- ✅ Helper methods for captain checks
- ✅ Factories for testing

### Phase 3: Rename Controllers & Routes (3-4 hours)
**Objective**: Update all existing controllers and routes for "events" terminology

**Tasks**:
1. Rename GameController → EventController
2. Rename Admin\GameController → Admin\EventController
3. Rename Admin\QuestionController → Admin\EventQuestionController
4. Update all routes (web.php) from /games to /events
5. Update all controller methods to use Event instead of Game
6. Update all Form Requests
7. Update all Policies
8. Test all existing routes still work

**Deliverables**:
- ✅ All controllers renamed
- ✅ All routes updated to /events/*
- ✅ All references to $game → $event
- ✅ Form requests updated
- ✅ Policies updated
- ✅ Existing functionality still works

### Phase 4: Captain Controllers (6-8 hours)
**Objective**: Create all captain-specific functionality

**Tasks**:
1. Create `app/Http/Controllers/Captain/` directory
2. Create Captain\DashboardController
3. Create Captain\GroupController (create from invitation)
4. Create Captain\GroupQuestionController (CRUD for group questions)
5. Create Captain\GradingController (set group answers)
6. Create Captain\MemberController (manage members, promote)
7. Create `EnsureIsCaptainOfGroup` middleware
8. Create Captain routes group in web.php
9. Create Form Requests for captain actions
10. Update GroupPolicy for captain permissions

**Deliverables**:
- ✅ Captain dashboard
- ✅ Group creation from captain invitation
- ✅ Question customization (add/remove/modify)
- ✅ Captain grading interface
- ✅ Member management & promotion
- ✅ Middleware for captain protection
- ✅ All captain routes defined

### Phase 5: Admin Captain Features (4-5 hours)
**Objective**: Admin tools for captain system

**Tasks**:
1. Create Admin\CaptainInvitationController
2. Create Admin\EventAnswerController (admin grading)
3. Update Admin\EventController with captain invitation UI
4. Update Admin\GradingController to use event_answers
5. Create Form Requests for admin actions
6. Update Admin dashboard stats

**Deliverables**:
- ✅ Admin can generate captain invitations
- ✅ Admin can set event-level answers
- ✅ Admin can view captain invitations
- ✅ Admin can deactivate captain invitations
- ✅ Admin dashboard shows captain stats

### Phase 6: Dual Grading Logic (4-5 hours)
**Objective**: Implement captain vs admin grading

**Tasks**:
1. Update SubmissionService grading logic
2. Check group.grading_source before grading
3. If 'captain': Use group_question_answers
4. If 'admin': Use event_answers (read-only for captain)
5. Handle case where admin answers not set yet
6. Update LeaderboardService for group_questions
7. Test both grading modes thoroughly
8. Handle edge cases (switching modes mid-event)

**Deliverables**:
- ✅ Dual grading system working
- ✅ Captain-graded groups score correctly
- ✅ Admin-graded groups score correctly
- ✅ Edge cases handled
- ✅ Leaderboards update correctly

### Phase 7: Frontend - Captain Vue Components (8-10 hours)
**Objective**: Build all captain-facing UI

**Tasks**:
1. Create `resources/js/Pages/Captain/` directory
2. Create Captain/Dashboard.vue
3. Create Captain/CreateGroup.vue (from invitation)
4. Create Captain/Questions/Index.vue (list)
5. Create Captain/Questions/Customize.vue (add/remove/edit)
6. Create Captain/Questions/Create.vue (custom question)
7. Create Captain/Grading/Index.vue (set answers)
8. Create Captain/Members/Index.vue (manage members)
9. Update navigation to show Captain menu
10. Add captain badge/indicator in UI

**Deliverables**:
- ✅ Complete captain dashboard
- ✅ Group creation flow
- ✅ Question management UI
- ✅ Grading interface for captains
- ✅ Member management UI
- ✅ Navigation updates

### Phase 8: Frontend - Admin Updates (4-5 hours)
**Objective**: Update admin UI for captain system

**Tasks**:
1. Update Admin/Dashboard.vue (simplify)
2. Update Admin/Events/Show.vue (add captain invitations section)
3. Create Admin/CaptainInvitations/Index.vue
4. Update Admin/Grading/Index.vue (for event-level answers)
5. Remove statistics sections as requested
6. Remove recent activity sections
7. Update Admin/Events/Index.vue terminology

**Deliverables**:
- ✅ Simplified admin dashboard
- ✅ Captain invitation management
- ✅ Event-level grading interface
- ✅ All terminology updated to "Events"
- ✅ Statistics removed

### Phase 9: Frontend - Player Updates (2-3 hours)
**Objective**: Update player-facing UI for new structure

**Tasks**:
1. Update Dashboard.vue (events terminology)
2. Update Events/Available.vue (browse events)
3. Update Events/Play.vue (play events)
4. Update Submissions/Continue.vue (use group_questions)
5. Update Leaderboard views
6. Update navigation (Games → Events)

**Deliverables**:
- ✅ All player UI updated
- ✅ Events terminology throughout
- ✅ Group questions display correctly
- ✅ Submissions work with group questions

### Phase 10: Testing & Refinement (4-6 hours)
**Objective**: Comprehensive testing of all flows

**Test Scenarios**:
1. Admin creates event → generates captain invitation
2. Captain clicks link → creates group → becomes captain
3. Captain customizes questions (add custom, remove some)
4. Captain promotes another member to captain
5. Both captains can modify questions and set answers
6. Player joins group → plays game → submits
7. Captain grades → leaderboard updates
8. Test admin grading mode (group uses admin answers)
9. Test switching grading modes
10. Test permissions (non-captains can't access captain features)
11. Test edge cases (expired invitations, etc.)

**Deliverables**:
- ✅ All user flows tested
- ✅ Permissions enforced correctly
- ✅ Data integrity maintained
- ✅ Bug fixes implemented
- ✅ Documentation updated

---

## Total Estimated Time: 42-56 hours

**Breakdown**:
- Phase 1 (Database): 4-6 hours
- Phase 2 (Models): 3-4 hours
- Phase 3 (Rename): 3-4 hours
- Phase 4 (Captain Controllers): 6-8 hours
- Phase 5 (Admin Features): 4-5 hours
- Phase 6 (Grading Logic): 4-5 hours
- Phase 7 (Captain UI): 8-10 hours
- Phase 8 (Admin UI): 4-5 hours
- Phase 9 (Player UI): 2-3 hours
- Phase 10 (Testing): 4-6 hours

---

## Risk Mitigation

### High-Risk Areas:
1. **Data Migration**: Renaming tables and migrating question data
2. **Grading Logic**: Dual grading system complexity
3. **Permissions**: Ensuring captains can't access other groups

### Mitigation Strategies:
1. **Backup Before Migration**: Full database backup before Phase 1
2. **Test Migrations**: Run migrations on copy of production data
3. **Incremental Testing**: Test each phase before moving to next
4. **Feature Flags**: Could add feature flag for captain system
5. **Rollback Plan**: Keep old question_id columns temporarily

---

## Success Criteria

### Must Have (MVP):
- ✅ Games renamed to Events throughout
- ✅ Captain role working (per-group via is_captain)
- ✅ Captain can create group from admin invitation
- ✅ Captain can customize questions (add/remove/modify)
- ✅ Captain can set answers for their group
- ✅ Captain can promote other members to captain
- ✅ Dual grading working (captain vs admin)
- ✅ Admin can generate captain invitations
- ✅ Admin can set event-level answers
- ✅ Permissions enforced correctly

### Should Have (Enhancement):
- ✅ Live leaderboard updates as captain grades
- ✅ Admin can view all groups' questions
- ✅ Captain can copy questions from another group
- ✅ Admin dashboard simplified (statistics removed)
- ✅ Invitation usage tracking

### Nice to Have (Future):
- ⏳ QR code for captain invitations
- ⏳ Email invitations
- ⏳ Captain analytics dashboard
- ⏳ Question templates at captain level
- ⏳ Bulk question import for captains

---

## Next Steps

**Immediate**:
1. Review and approve this implementation plan
2. Create backup of current database
3. Create new Git branch: `feature/captain-system`
4. Begin Phase 1: Database Foundation

**Questions Before Starting**:
1. Should we keep old `question_id` columns during transition or remove immediately?
2. What happens to existing groups - should we auto-promote creators to captain?
3. Should we add feature flag to enable/disable captain system during development?
4. Timeline expectations - implement all at once or phase-by-phase over multiple sessions?

---

## File Structure Summary

### New Files to Create (~35 files):

**Migrations (9)**:
- 2025_11_20_000001_rename_games_to_events.php
- 2025_11_20_000002_add_is_captain_to_user_groups.php
- 2025_11_20_000003_rename_questions_to_event_questions.php
- 2025_11_20_000004_create_group_questions_table.php
- 2025_11_20_000005_add_grading_source_to_groups.php
- 2025_11_20_000006_create_event_answers_table.php
- 2025_11_20_000007_create_captain_invitations_table.php
- 2025_11_20_000008_update_user_answers_for_group_questions.php
- 2025_11_20_000009_update_group_question_answers_for_group_questions.php

**Models (3 new)**:
- app/Models/GroupQuestion.php
- app/Models/EventAnswer.php
- app/Models/CaptainInvitation.php

**Controllers (7 new)**:
- app/Http/Controllers/Captain/DashboardController.php
- app/Http/Controllers/Captain/GroupController.php
- app/Http/Controllers/Captain/GroupQuestionController.php
- app/Http/Controllers/Captain/GradingController.php
- app/Http/Controllers/Captain/MemberController.php
- app/Http/Controllers/Admin/CaptainInvitationController.php
- app/Http/Controllers/Admin/EventAnswerController.php

**Middleware (1 new)**:
- app/Http/Middleware/EnsureIsCaptainOfGroup.php

**Form Requests (~5 new)**:
- app/Http/Requests/Captain/CreateGroupRequest.php
- app/Http/Requests/Captain/StoreGroupQuestionRequest.php
- app/Http/Requests/Captain/UpdateGroupQuestionRequest.php
- app/Http/Requests/Captain/SetAnswerRequest.php
- app/Http/Requests/Admin/CreateCaptainInvitationRequest.php

**Vue Components (~10 new)**:
- resources/js/Pages/Captain/Dashboard.vue
- resources/js/Pages/Captain/CreateGroup.vue
- resources/js/Pages/Captain/Questions/Index.vue
- resources/js/Pages/Captain/Questions/Customize.vue
- resources/js/Pages/Captain/Questions/Create.vue
- resources/js/Pages/Captain/Grading/Index.vue
- resources/js/Pages/Captain/Members/Index.vue
- resources/js/Pages/Admin/CaptainInvitations/Index.vue
- (Plus updates to ~10 existing components)

### Files to Rename (~10 files):
- Game.php → Event.php
- Question.php → EventQuestion.php
- GameController.php → EventController.php
- Admin/GameController.php → Admin/EventController.php
- Admin/QuestionController.php → Admin/EventQuestionController.php
- (Plus Form Requests, Policies, Factories)

---

**This implementation plan is comprehensive and ready to execute.**

**Awaiting approval to proceed with Phase 1.**

# Game Prediction Application - Design Document

## Table of Contents
1. [System Architecture](#system-architecture)
2. [Database Design](#database-design)
3. [API Design](#api-design)
4. [Frontend Architecture](#frontend-architecture)
5. [Authentication & Authorization](#authentication--authorization)
6. [UI/UX Design](#uiux-design)
7. [Data Flow](#data-flow)

---

## System Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                        Client Layer                          │
│  ┌────────────────────────────────────────────────────┐    │
│  │  Vue 3 SPA (Composition API) + Tailwind CSS        │    │
│  └────────────────────────────────────────────────────┘    │
└───────────────────────┬─────────────────────────────────────┘
                        │ Inertia.js
┌───────────────────────▼─────────────────────────────────────┐
│                    Application Layer                         │
│  ┌────────────────────────────────────────────────────┐    │
│  │           Laravel 10.x Backend                      │    │
│  │  ┌──────────────────────────────────────────┐      │    │
│  │  │  Controllers (Inertia Responses)         │      │    │
│  │  ├──────────────────────────────────────────┤      │    │
│  │  │  Services (Business Logic)               │      │    │
│  │  ├──────────────────────────────────────────┤      │    │
│  │  │  Repositories (Data Access)              │      │    │
│  │  ├──────────────────────────────────────────┤      │    │
│  │  │  Models (Eloquent ORM)                   │      │    │
│  │  └──────────────────────────────────────────┘      │    │
│  └────────────────────────────────────────────────────┘    │
└───────────────────────┬─────────────────────────────────────┘
                        │
┌───────────────────────▼─────────────────────────────────────┐
│                      Data Layer                              │
│  ┌────────────────────────────────────────────────────┐    │
│  │              MySQL Database                         │    │
│  └────────────────────────────────────────────────────┘    │
│  ┌────────────────────────────────────────────────────┐    │
│  │          Redis Cache (Sessions, Cache)              │    │
│  └────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────┘
```

### Architecture Patterns

**Backend:**
- **MVC Pattern**: Laravel follows Model-View-Controller pattern
- **Repository Pattern**: Abstract data access logic
- **Service Layer**: Encapsulate business logic
- **Event-Driven**: Use Laravel Events for notifications and async tasks

**Frontend:**
- **Component-Based Architecture**: Vue 3 Composition API
- **Composables**: Reusable logic with Vue composables
- **State Management**: Pinia for complex state (optional for MVP)

---

## Database Design

### Entity Relationship Diagram

```
users                    user_groups              groups
├─ id                   ├─ id                    ├─ id
├─ name                 ├─ user_id (FK)          ├─ name
├─ email                ├─ group_id (FK)         ├─ code (unique)
├─ password             ├─ joined_at             ├─ description
├─ role                 └─ timestamps            ├─ is_public
└─ timestamps                                     ├─ created_by (FK)
                                                  └─ timestamps

games                    game_user                question_templates
├─ id                   ├─ id                    ├─ id
├─ name                 ├─ game_id (FK)          ├─ title
├─ description          ├─ user_id (FK)          ├─ question_text
├─ event_type           ├─ group_id (FK)         ├─ question_type
├─ event_date           ├─ timestamps            ├─ category
├─ status                                         ├─ default_points
├─ lock_date                                      ├─ variables (JSON)
└─ timestamps                                     ├─ is_favorite
                                                  └─ timestamps

questions                group_question_answers   submissions
├─ id                   ├─ id                    ├─ id
├─ game_id (FK)         ├─ group_id (FK)         ├─ game_id (FK)
├─ template_id (FK)     ├─ question_id (FK)      ├─ user_id (FK)
├─ question_text        ├─ correct_answer        ├─ group_id (FK)
├─ question_type        ├─ is_void               ├─ total_score
├─ options (JSON)       └─ timestamps            ├─ is_complete
├─ points                                         ├─ submitted_at
├─ order                                          └─ timestamps
└─ timestamps

user_answers            leaderboards
├─ id                  ├─ id
├─ submission_id (FK)  ├─ game_id (FK)
├─ question_id (FK)    ├─ group_id (FK)
├─ answer_text         ├─ user_id (FK)
├─ points_earned       ├─ rank
├─ is_correct          ├─ total_score
└─ timestamps          ├─ percentage
                       └─ timestamps
```

### Database Schema

#### Users Table
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    avatar VARCHAR(255) NULL,
    email_verified_at TIMESTAMP NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### Groups Table
```sql
CREATE TABLE groups (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    description TEXT NULL,
    is_public BOOLEAN DEFAULT FALSE,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_code (code),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### User_Groups Table (Pivot)
```sql
CREATE TABLE user_groups (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    group_id BIGINT UNSIGNED NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_group (user_id, group_id),
    INDEX idx_user_id (user_id),
    INDEX idx_group_id (group_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### Games Table
```sql
CREATE TABLE games (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    event_type VARCHAR(100) NOT NULL,
    event_date DATETIME NOT NULL,
    status ENUM('draft', 'open', 'locked', 'in_progress', 'completed') DEFAULT 'draft',
    lock_date DATETIME NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_event_date (event_date),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### Question_Templates Table
```sql
CREATE TABLE question_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('multiple_choice', 'yes_no', 'numeric', 'text') NOT NULL,
    category VARCHAR(100) NULL,
    default_points INT DEFAULT 1,
    variables JSON NULL COMMENT 'Array of variable names like ["team1", "player1"]',
    default_options JSON NULL COMMENT 'Default options for multiple choice',
    is_favorite BOOLEAN DEFAULT FALSE,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_category (category),
    INDEX idx_is_favorite (is_favorite)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### Questions Table
```sql
CREATE TABLE questions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    game_id BIGINT UNSIGNED NOT NULL,
    template_id BIGINT UNSIGNED NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('multiple_choice', 'yes_no', 'numeric', 'text') NOT NULL,
    options JSON NULL COMMENT 'For multiple choice: ["Option A", "Option B", ...]',
    points INT DEFAULT 1,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES question_templates(id) ON DELETE SET NULL,
    INDEX idx_game_id (game_id),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### Group_Question_Answers Table
**Purpose**: Stores group-specific correct answers for questions, allowing each group to have different correct answers for subjective questions.

```sql
CREATE TABLE group_question_answers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    group_id BIGINT UNSIGNED NOT NULL,
    question_id BIGINT UNSIGNED NOT NULL,
    correct_answer TEXT NOT NULL,
    is_void BOOLEAN DEFAULT FALSE COMMENT 'If true, no points awarded for this question in this group',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_group_question (group_id, question_id),
    INDEX idx_group_id (group_id),
    INDEX idx_question_id (question_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### Submissions Table
```sql
CREATE TABLE submissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    game_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    group_id BIGINT UNSIGNED NOT NULL,
    total_score INT DEFAULT 0,
    possible_points INT DEFAULT 0,
    percentage DECIMAL(5,2) DEFAULT 0.00,
    is_complete BOOLEAN DEFAULT FALSE,
    submitted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
    UNIQUE KEY unique_submission (game_id, user_id, group_id),
    INDEX idx_game_id (game_id),
    INDEX idx_user_id (user_id),
    INDEX idx_group_id (group_id),
    INDEX idx_is_complete (is_complete)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### User_Answers Table
```sql
CREATE TABLE user_answers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    submission_id BIGINT UNSIGNED NOT NULL,
    question_id BIGINT UNSIGNED NOT NULL,
    answer_text TEXT NULL,
    points_earned INT DEFAULT 0,
    is_correct BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (submission_id) REFERENCES submissions(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_submission_question (submission_id, question_id),
    INDEX idx_submission_id (submission_id),
    INDEX idx_question_id (question_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### Leaderboards Table (Materialized View)
```sql
CREATE TABLE leaderboards (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    game_id BIGINT UNSIGNED NOT NULL,
    group_id BIGINT UNSIGNED NULL COMMENT 'NULL for global leaderboard',
    user_id BIGINT UNSIGNED NOT NULL,
    rank INT NOT NULL,
    total_score INT NOT NULL,
    possible_points INT NOT NULL,
    percentage DECIMAL(5,2) NOT NULL,
    answered_count INT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_leaderboard (game_id, group_id, user_id),
    INDEX idx_game_group (game_id, group_id),
    INDEX idx_rank (rank)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### Notifications Table
```sql
CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(100) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    data JSON NULL,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_read_at (read_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## API Design

### RESTful API Endpoints (Inertia.js Routes)

#### Authentication Routes
```
POST   /register                 - Register new user
POST   /login                    - User login
POST   /logout                   - User logout
POST   /forgot-password          - Request password reset
POST   /reset-password           - Reset password
GET    /verify-email/{id}/{hash} - Verify email
```

#### User Routes
```
GET    /dashboard                - User dashboard
GET    /profile                  - View profile
PUT    /profile                  - Update profile
GET    /settings                 - User settings
PUT    /settings                 - Update settings
```

#### Game Routes (User)
```
GET    /games                    - List available games
GET    /games/{game}             - View game details
POST   /games/{game}/join        - Join a game
DELETE /games/{game}/leave       - Leave a game
GET    /games/{game}/play        - Play game (answer questions)
POST   /games/{game}/submit      - Submit answers
PUT    /games/{game}/answers     - Update answers
GET    /games/{game}/results     - View results
```

#### Group Routes
```
GET    /groups                   - List user's groups
GET    /groups/discover          - Discover public groups
POST   /groups                   - Create group (admin only)
GET    /groups/{group}           - View group details
PUT    /groups/{group}           - Update group
DELETE /groups/{group}           - Delete group
POST   /groups/join              - Join group with code
DELETE /groups/{group}/leave     - Leave group
DELETE /groups/{group}/users/{user} - Remove user from group
```

#### Leaderboard Routes
```
GET    /leaderboards/global/{game}      - Global leaderboard
GET    /leaderboards/group/{game}/{group} - Group leaderboard
GET    /leaderboards/history            - Historical leaderboards
```

#### Admin Routes - Games
```
GET    /admin/games              - List all games
GET    /admin/games/create       - Create game form
POST   /admin/games              - Store new game
GET    /admin/games/{game}/edit  - Edit game form
PUT    /admin/games/{game}       - Update game
DELETE /admin/games/{game}       - Delete game
PATCH  /admin/games/{game}/status - Change game status
```

#### Admin Routes - Questions
```
GET    /admin/games/{game}/questions          - List game questions
GET    /admin/games/{game}/questions/create   - Create question form
POST   /admin/games/{game}/questions          - Store new question
GET    /admin/questions/{question}/edit       - Edit question form
PUT    /admin/questions/{question}            - Update question
DELETE /admin/questions/{question}            - Delete question
POST   /admin/questions/reorder               - Reorder questions
```

#### Admin Routes - Question Templates
```
GET    /admin/templates                - List templates
GET    /admin/templates/create         - Create template form
POST   /admin/templates                - Store template
GET    /admin/templates/{template}/edit - Edit template form
PUT    /admin/templates/{template}     - Update template
DELETE /admin/templates/{template}     - Delete template
POST   /admin/templates/{template}/favorite - Toggle favorite
```

#### Admin Routes - Answers & Scoring
```
GET    /admin/games/{game}/grading     - View all submissions
POST   /admin/games/{game}/answers     - Input correct answers
PUT    /admin/answers/{answer}/adjust  - Manually adjust score
PATCH  /admin/questions/{question}/void - Mark question as void
POST   /admin/games/{game}/calculate   - Recalculate scores
GET    /admin/games/{game}/export      - Export results CSV
```

#### Admin Routes - Statistics
```
GET    /admin/dashboard                 - Admin dashboard
GET    /admin/games/{game}/statistics   - Game statistics
GET    /admin/users                     - User management
GET    /admin/groups                    - Group management
```

### API Response Format (JSON for AJAX calls)

```json
{
  "success": true,
  "message": "Operation successful",
  "data": {
    // Response data
  },
  "errors": null
}
```

---

## Frontend Architecture

### Directory Structure

```
resources/
├── js/
│   ├── app.js                      # Main entry point
│   ├── Pages/                      # Inertia pages
│   │   ├── Auth/
│   │   │   ├── Login.vue
│   │   │   ├── Register.vue
│   │   │   └── ForgotPassword.vue
│   │   ├── Dashboard.vue
│   │   ├── Games/
│   │   │   ├── Index.vue
│   │   │   ├── Show.vue
│   │   │   └── Play.vue
│   │   ├── Groups/
│   │   │   ├── Index.vue
│   │   │   ├── Show.vue
│   │   │   └── Create.vue
│   │   ├── Leaderboards/
│   │   │   ├── Global.vue
│   │   │   └── Group.vue
│   │   ├── Admin/
│   │   │   ├── Dashboard.vue
│   │   │   ├── Games/
│   │   │   ├── Questions/
│   │   │   ├── Templates/
│   │   │   └── Grading/
│   │   └── Profile/
│   │       └── Edit.vue
│   ├── Components/                  # Reusable components
│   │   ├── Layout/
│   │   │   ├── AppLayout.vue
│   │   │   ├── AdminLayout.vue
│   │   │   ├── Navbar.vue
│   │   │   └── Sidebar.vue
│   │   ├── UI/
│   │   │   ├── Button.vue
│   │   │   ├── Input.vue
│   │   │   ├── Select.vue
│   │   │   ├── Card.vue
│   │   │   ├── Modal.vue
│   │   │   └── Alert.vue
│   │   ├── Game/
│   │   │   ├── GameCard.vue
│   │   │   ├── QuestionCard.vue
│   │   │   ├── QuestionForm.vue
│   │   │   └── ProgressBar.vue
│   │   ├── Leaderboard/
│   │   │   ├── LeaderboardTable.vue
│   │   │   └── LeaderboardRow.vue
│   │   └── Group/
│   │       ├── GroupCard.vue
│   │       └── GroupList.vue
│   ├── Composables/                 # Reusable logic
│   │   ├── useAuth.js
│   │   ├── useGame.js
│   │   ├── useLeaderboard.js
│   │   ├── useNotifications.js
│   │   └── useForm.js
│   ├── Utils/                       # Utility functions
│   │   ├── date.js
│   │   ├── validation.js
│   │   └── helpers.js
│   └── Stores/                      # Pinia stores (if needed)
│       ├── auth.js
│       └── notifications.js
└── css/
    └── app.css                      # Tailwind imports
```

### Key Vue Components

#### Question Form Component
```vue
<template>
  <div class="question-card">
    <h3>{{ question.question_text }}</h3>

    <!-- Multiple Choice -->
    <div v-if="question.question_type === 'multiple_choice'">
      <RadioGroup v-model="answer">
        <RadioOption
          v-for="option in question.options"
          :key="option"
          :value="option"
        />
      </RadioGroup>
    </div>

    <!-- Yes/No -->
    <div v-if="question.question_type === 'yes_no'">
      <ToggleSwitch v-model="answer" />
    </div>

    <!-- Numeric -->
    <div v-if="question.question_type === 'numeric'">
      <NumberInput v-model="answer" />
    </div>

    <!-- Text -->
    <div v-if="question.question_type === 'text'">
      <TextInput v-model="answer" />
    </div>
  </div>
</template>
```

#### Leaderboard Component
```vue
<template>
  <div class="leaderboard">
    <table>
      <thead>
        <tr>
          <th>Rank</th>
          <th>User</th>
          <th>Score</th>
          <th>%</th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="entry in leaderboard"
          :key="entry.id"
          :class="{ 'highlight': entry.user_id === currentUserId }"
        >
          <td>{{ entry.rank }}</td>
          <td>{{ entry.user.name }}</td>
          <td>{{ entry.total_score }} / {{ entry.possible_points }}</td>
          <td>{{ entry.percentage }}%</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
```

---

## Authentication & Authorization

### Laravel Breeze with Inertia Stack

**Installation:**
```bash
composer require laravel/breeze --dev
php artisan breeze:install vue
```

**Configuration:**
- Use Laravel Sanctum for SPA authentication
- Session-based authentication with Inertia
- CSRF protection enabled
- Middleware: `auth`, `verified`, `admin`

### Authorization Rules

**Policies:**
- `GamePolicy`: Control game management
- `QuestionPolicy`: Control question management
- `SubmissionPolicy`: Control answer submission
- `GroupPolicy`: Control group management

**Example Policy:**
```php
class GamePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Game $game): bool
    {
        return $user->isAdmin();
    }

    public function submit(User $user, Game $game): bool
    {
        return $game->status === 'open' && $game->lock_date > now();
    }
}
```

---

## UI/UX Design

### Design System

**Color Palette (Tailwind):**
```
Primary: Blue-600 (#2563eb)
Secondary: Slate-600 (#475569)
Success: Green-600 (#16a34a)
Warning: Yellow-500 (#eab308)
Danger: Red-600 (#dc2626)
Background: Gray-50 (#f9fafb)
```

**Typography:**
```
Headings: Inter font (font-bold)
Body: Inter font (font-normal)
Code: Mono font
```

### Page Layouts

#### User Dashboard
```
┌─────────────────────────────────────────────┐
│ Navbar (Logo, Nav Links, User Menu)        │
├─────────────────────────────────────────────┤
│                                             │
│  Welcome Back, [Name]!                      │
│                                             │
│  ┌──────────────┐  ┌──────────────┐        │
│  │ Active Games │  │  My Groups   │        │
│  │  Count: 3    │  │  Count: 5    │        │
│  └──────────────┘  └──────────────┘        │
│                                             │
│  Active Games                               │
│  ┌─────────────────────────────────────┐   │
│  │ Super Bowl LIX 2025                 │   │
│  │ Status: Open | Lock: 2 days         │   │
│  │ Progress: 8/15 questions answered   │   │
│  │ [Continue Playing]                  │   │
│  └─────────────────────────────────────┘   │
│                                             │
│  Recent Results                             │
│  ...                                        │
│                                             │
└─────────────────────────────────────────────┘
```

#### Game Play Interface
```
┌─────────────────────────────────────────────┐
│ ← Back to Dashboard                         │
├─────────────────────────────────────────────┤
│ Super Bowl LIX 2025                         │
│ Progress: ████████░░░░░░░ 8/15 (53%)       │
├─────────────────────────────────────────────┤
│                                             │
│ Question 1 of 15                  [1 point] │
│                                             │
│ Which team will score first?                │
│                                             │
│ ○ Kansas City Chiefs                        │
│ ○ Philadelphia Eagles                       │
│ ○ Neither (safety or tie)                   │
│                                             │
│ [Previous]  [Save Draft]  [Next Question]   │
│                                             │
│ Questions:                                  │
│ ✓ Q1  ✓ Q2  ✓ Q3  ○ Q4  ○ Q5 ...          │
│                                             │
└─────────────────────────────────────────────┘
```

#### Leaderboard
```
┌─────────────────────────────────────────────┐
│ Leaderboard - Super Bowl LIX 2025           │
├─────────────────────────────────────────────┤
│ [Global] [My Groups ▼]                      │
│                                             │
│ ┌─────┬──────────────┬───────┬──────┐      │
│ │Rank │ User         │ Score │  %   │      │
│ ├─────┼──────────────┼───────┼──────┤      │
│ │  1  │ JohnDoe      │ 14/15 │ 93%  │      │
│ │  2  │ JaneSmith    │ 13/15 │ 87%  │      │
│ │  3  │ You ⭐       │ 12/15 │ 80%  │      │
│ │  4  │ BobJones     │ 11/15 │ 73%  │      │
│ └─────┴──────────────┴───────┴──────┘      │
│                                             │
│ Your rank: #3 out of 50 participants       │
│                                             │
└─────────────────────────────────────────────┘
```

#### Admin Dashboard
```
┌─────────────────────────────────────────────┐
│ Admin Dashboard                             │
├─────────────────────────────────────────────┤
│ ┌──────────┐ ┌──────────┐ ┌──────────┐    │
│ │  Games   │ │  Users   │ │Templates │    │
│ │    12    │ │   450    │ │    35    │    │
│ └──────────┘ └──────────┘ └──────────┘    │
│                                             │
│ Quick Actions                               │
│ [+ Create Game] [+ Create Template]         │
│                                             │
│ Recent Games                                │
│ ┌─────────────────────────────────────┐    │
│ │ Super Bowl LIX | Status: Open       │    │
│ │ 234 submissions | [Manage] [Grade]  │    │
│ └─────────────────────────────────────┘    │
│                                             │
└─────────────────────────────────────────────┘
```

### Responsive Design Breakpoints

```
Mobile:  < 640px  (sm)
Tablet:  640-1024px (md/lg)
Desktop: > 1024px (xl)
```

---

## Data Flow

### User Submission Flow

```
1. User navigates to /games/{game}/play
   ↓
2. Frontend fetches game + questions via Inertia
   ↓
3. User answers questions (auto-save to localStorage)
   ↓
4. User clicks "Submit" or "Save Draft"
   ↓
5. POST /games/{game}/submit with answers JSON
   ↓
6. Backend validates:
   - Game is open
   - User is authenticated
   - Answers are valid format
   ↓
7. Create/update Submission record
   ↓
8. Create/update UserAnswer records
   ↓
9. Mark submission as complete if all questions answered
   ↓
10. Return success response
   ↓
11. Redirect to confirmation page
```

### Scoring Flow

```
1. Admin navigates to /admin/games/{game}/grading
   ↓
2. Admin inputs correct answers for each question
   ↓
3. POST /admin/games/{game}/answers
   ↓
4. Update questions table with correct_answer
   ↓
5. Trigger ScoreCalculationJob (queued)
   ↓
6. For each submission:
   - Compare user_answers with correct_answer
   - Calculate points_earned
   - Update is_correct flag
   - Sum total_score
   ↓
7. Update submissions table with total_score
   ↓
8. Recalculate leaderboards (materialized view)
   ↓
9. Trigger notification events to users
   ↓
10. Broadcast leaderboard updates (if using websockets)
```

### Leaderboard Calculation

```
1. When scores are calculated/updated
   ↓
2. Trigger UpdateLeaderboardJob
   ↓
3. For each group in the game:
   - Query submissions for game + group
   - Order by total_score DESC, submitted_at ASC
   - Calculate rank (handle ties)
   - Upsert leaderboards table
   ↓
4. For global leaderboard (group_id = NULL):
   - Query all submissions for game
   - Order by total_score DESC, submitted_at ASC
   - Calculate rank
   - Upsert leaderboards table
   ↓
5. Cache results in Redis (TTL: 5 minutes)
   ↓
6. Return leaderboard data
```

---

## Performance Optimizations

### Database Indexing
- Index all foreign keys
- Composite indexes on frequently queried columns
- Index on status, dates for filtering

### Caching Strategy
```php
// Cache leaderboards
Cache::remember("leaderboard:game:{$gameId}:group:{$groupId}", 300, function() {
    return Leaderboard::where('game_id', $gameId)
        ->where('group_id', $groupId)
        ->orderBy('rank')
        ->with('user')
        ->get();
});

// Cache game questions
Cache::remember("game:{$gameId}:questions", 3600, function() {
    return Question::where('game_id', $gameId)
        ->orderBy('display_order')
        ->get();
});
```

### Query Optimization
- Use eager loading to prevent N+1 queries
- Paginate large result sets
- Use database transactions for multi-step operations
- Implement query scopes for reusable filters

### Frontend Optimization
- Lazy load components
- Implement virtual scrolling for long lists
- Debounce auto-save functionality
- Use Inertia partial reloads
- Optimize images and assets

---

## Security Considerations

1. **Input Validation**: Validate all inputs on both frontend and backend
2. **SQL Injection Prevention**: Use Eloquent ORM and parameterized queries
3. **XSS Prevention**: Sanitize user-generated content, use Vue's automatic escaping
4. **CSRF Protection**: Laravel's built-in CSRF middleware
5. **Rate Limiting**: Implement rate limiting on submission endpoints
6. **Authorization**: Check permissions on every protected route
7. **Data Encryption**: Encrypt sensitive data at rest
8. **Secure Sessions**: Use secure, httpOnly cookies
9. **API Security**: Implement proper authentication and authorization

---

## Error Handling

### Backend Error Responses
```php
try {
    // Operation
} catch (ValidationException $e) {
    return back()->withErrors($e->errors());
} catch (AuthorizationException $e) {
    abort(403, 'Unauthorized action');
} catch (\Exception $e) {
    Log::error('Error: ' . $e->getMessage());
    return back()->with('error', 'An unexpected error occurred');
}
```

### Frontend Error Display
- Toast notifications for success/error messages
- Inline validation errors on forms
- Global error boundary component
- 404 and 500 error pages

---

## Testing Strategy

1. **Unit Tests**: Test models, services, utilities
2. **Feature Tests**: Test API endpoints and business logic
3. **Browser Tests**: Test user flows with Laravel Dusk
4. **Component Tests**: Test Vue components with Vitest
5. **API Tests**: Test all API endpoints with proper auth

---

This design document provides a comprehensive blueprint for building the game prediction application. The architecture is scalable, maintainable, and follows Laravel and Vue.js best practices.

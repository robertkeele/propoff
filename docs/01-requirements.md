# Game Prediction Application - Requirements Document

## Project Overview
A web-based prediction/guessing game platform where users can submit answers to questions about events in a game (initially NFL Super Bowl). The platform supports multiple groups, leaderboards, question templates, and administrative controls.

## Technology Stack
- **Frontend**: Vue 3 (Composition API) + Tailwind CSS
- **Backend**: PHP Laravel
- **Bridge**: Inertia.js
- **Database**: MySQL
- **Authentication**: Laravel Breeze/Sanctum with Inertia

---

## Functional Requirements

### 1. User Management

#### 1.1 User Registration & Authentication
- **FR-1.1.1**: Users must be able to register with email, username, and password
- **FR-1.1.2**: Users must be able to log in with email/username and password
- **FR-1.1.3**: Users must be able to reset forgotten passwords via email
- **FR-1.1.4**: Users must be able to update their profile information
- **FR-1.1.5**: Users must be able to log out
- **FR-1.1.6**: System must validate email uniqueness and format
- **FR-1.1.7**: System must enforce password strength requirements (min 8 chars, mix of letters/numbers)

#### 1.2 User Roles
- **FR-1.2.1**: System must support two user roles: Admin and Regular User
- **FR-1.2.2**: Admins must have access to all administrative functions
- **FR-1.2.3**: Regular users must only access user-facing features

### 2. Game Management (Admin)

#### 2.1 Game Creation
- **FR-2.1.1**: Admins must be able to create a new game with:
  - Game name (e.g., "Super Bowl LIX 2025")
  - Game date/time
  - Sport/event type
  - Description
  - Status (draft, open, locked, completed)
- **FR-2.1.2**: Admins must be able to edit game details before game is locked
- **FR-2.1.3**: Admins must be able to delete games that have no submissions
- **FR-2.1.4**: Admins must be able to archive completed games

#### 2.2 Game Status Management
- **FR-2.2.1**: Games must have statuses: Draft, Open, Locked, In Progress, Completed
- **FR-2.2.2**: Only "Open" games accept user submissions
- **FR-2.2.3**: "Locked" games prevent any new/edited submissions
- **FR-2.2.4**: Admins must be able to change game status
- **FR-2.2.5**: System must automatically lock games at a specified deadline

### 3. Question Management (Admin)

#### 3.1 Question Templates
- **FR-3.1.1**: Admins must be able to create reusable question templates with:
  - Question text with variable placeholders (e.g., "{team1} will score first")
  - Question type (multiple choice, yes/no, numeric, text)
  - Category/tag (e.g., "First Score", "Player Stats", "Team Stats")
  - Point value
  - Correct answer criteria
- **FR-3.1.2**: Templates must support variable placeholders that can be replaced (e.g., {team1}, {player1}, {team2})
- **FR-3.1.3**: Admins must be able to edit and delete question templates
- **FR-3.1.4**: Admins must be able to mark templates as "favorite" for quick access

#### 3.2 Game Questions
- **FR-3.2.1**: Admins must be able to add questions to a game by:
  - Creating a new custom question
  - Using a question template and filling in variables
- **FR-3.2.2**: Each game question must include:
  - Question text
  - Question type
  - Answer options (for multiple choice)
  - Point value
  - Display order
- **FR-3.2.3**: Admins must be able to reorder questions within a game
- **FR-3.2.4**: Admins must be able to edit questions before game is locked
- **FR-3.2.5**: Admins must be able to delete questions before submissions exist
- **FR-3.2.6**: System must prevent question modification after submissions exist

#### 3.3 Answer Types Support
- **FR-3.3.1**: Multiple Choice - predefined options (A, B, C, D)
- **FR-3.3.2**: Yes/No - binary choice
- **FR-3.3.3**: Numeric - number input with optional range validation
- **FR-3.3.4**: Short Text - free text response with character limit

### 4. User Participation

#### 4.1 Game Discovery & Joining
- **FR-4.1.1**: Users must be able to view all "Open" games
- **FR-4.1.2**: Users must be able to join a game
- **FR-4.1.3**: Users must see a list of games they've joined
- **FR-4.1.4**: Users must be able to leave a game before making submissions

#### 4.2 Group Management
- **FR-4.2.1**: Admins must be able to create groups with:
  - Group name
  - Group code (unique identifier)
  - Description
  - Privacy setting (public/private)
- **FR-4.2.2**: Users must be able to join groups using a group code
- **FR-4.2.3**: Users must be able to join multiple groups
- **FR-4.2.4**: Users must be able to leave groups
- **FR-4.2.5**: Group creators/admins must be able to remove users from groups
- **FR-4.2.6**: System must support a "General" group that all users auto-join
- **FR-4.2.7**: Group creators must be able to define correct answers for questions specific to their group
- **FR-4.2.8**: Each group operates independently with its own correct answers and scoring

#### 4.3 Answer Submission
- **FR-4.3.1**: Users must be able to submit answers to all questions in an open game
- **FR-4.3.2**: Users must be able to save answers as draft (partial completion)
- **FR-4.3.3**: Users must be able to edit submitted answers before game is locked
- **FR-4.3.4**: Users must see a visual indicator of completion status
- **FR-4.3.5**: System must validate answer format based on question type
- **FR-4.3.6**: System must timestamp all answer submissions
- **FR-4.3.7**: Users must be able to submit answers for each group they're in separately

### 5. Scoring & Results (Admin)

#### 5.1 Answer Grading
- **FR-5.1.1**: Group admin users who create questions must input correct answers for each question for their specific group
- **FR-5.1.2**: Each group must have its own correct answers for questions, as questions may be subjective and answers can vary by group
- **FR-5.1.3**: System must support multiple correct answers for the same question across different groups
- **FR-5.1.4**: System must automatically calculate scores based on the correct answers defined for each specific group
- **FR-5.1.5**: Admins must be able to manually adjust scores if needed
- **FR-5.1.6**: System must support partial credit for numeric answers (within range)
- **FR-5.1.7**: Admins must be able to mark questions as "void" (no points awarded) per group

#### 5.2 Score Calculation
- **FR-5.2.1**: System must calculate total score per user per game
- **FR-5.2.2**: System must calculate percentage correct
- **FR-5.2.3**: System must handle tie-breaking (by timestamp of final submission)
- **FR-5.2.4**: Scores must update automatically when correct answers are entered

### 6. Leaderboards

#### 6.1 Group Leaderboards
- **FR-6.1.1**: Users must be able to view leaderboard for each group they're in
- **FR-6.1.2**: Leaderboard must display:
  - Rank
  - Username
  - Total score
  - Percentage correct
  - Number of questions answered
- **FR-6.1.3**: Leaderboard must update in real-time as scores are calculated
- **FR-6.1.4**: Users must be able to see their own rank highlighted

#### 6.2 Global Leaderboard
- **FR-6.2.1**: System must provide a global leaderboard across all groups
- **FR-6.2.2**: Global leaderboard must be accessible to all users
- **FR-6.2.3**: Global leaderboard must display same information as group leaderboards

#### 6.3 Historical Leaderboards
- **FR-6.3.1**: Users must be able to view leaderboards for past games
- **FR-6.3.2**: System must maintain historical ranking data

### 7. Notifications

- **FR-7.1**: Users must receive notification when a game opens
- **FR-7.2**: Users must receive notification when a game is about to lock (24hr, 1hr warnings)
- **FR-7.3**: Users must receive notification when results are published
- **FR-7.4**: Users must be able to opt-in/out of email notifications
- **FR-7.5**: System must show in-app notifications

### 8. Additional Features

#### 8.1 User Dashboard
- **FR-8.1.1**: Users must have a dashboard showing:
  - Active games
  - Submission status
  - Recent scores
  - Groups they belong to
  - Overall statistics

#### 8.2 Admin Dashboard
- **FR-8.2.1**: Admins must have a dashboard showing:
  - All games and their status
  - Total users
  - Total submissions per game
  - Quick actions (create game, manage questions)

#### 8.3 Social Features
- **FR-8.3.1**: Users must be able to share game invitation links
- **FR-8.3.2**: Users must be able to see other users' answers after game completion (optional privacy setting)
- **FR-8.3.3**: System must support group chat or comments (future consideration)

#### 8.4 Statistics & Analytics
- **FR-8.4.1**: System must track user participation statistics
- **FR-8.4.2**: System must show answer distribution for each question (after game ends)
- **FR-8.4.3**: Admins must be able to export game results to CSV

---

## Non-Functional Requirements

### 1. Performance
- **NFR-1.1**: Page load time must be under 2 seconds
- **NFR-1.2**: Leaderboard updates must appear within 3 seconds of score calculation
- **NFR-1.3**: System must support at least 1000 concurrent users
- **NFR-1.4**: Database queries must be optimized with proper indexing

### 2. Security
- **NFR-2.1**: All passwords must be hashed using bcrypt
- **NFR-2.2**: System must protect against SQL injection
- **NFR-2.3**: System must protect against XSS attacks
- **NFR-2.4**: System must implement CSRF protection
- **NFR-2.5**: API endpoints must be authenticated and authorized
- **NFR-2.6**: Sensitive data must be encrypted in transit (HTTPS)

### 3. Usability
- **NFR-3.1**: Interface must be responsive (mobile, tablet, desktop)
- **NFR-3.2**: Interface must be intuitive with minimal learning curve
- **NFR-3.3**: Forms must have clear validation messages
- **NFR-3.4**: System must be accessible (WCAG 2.1 Level AA compliance goal)

### 4. Reliability
- **NFR-4.1**: System uptime must be 99.5% or higher
- **NFR-4.2**: System must have automated backups (daily)
- **NFR-4.3**: System must have error logging and monitoring

### 5. Maintainability
- **NFR-5.1**: Code must follow PSR-12 standards (PHP)
- **NFR-5.2**: Code must follow Vue.js style guide
- **NFR-5.3**: Code must be well-documented with comments
- **NFR-5.4**: System must have comprehensive test coverage (unit, integration)

### 6. Scalability
- **NFR-6.1**: Architecture must support horizontal scaling
- **NFR-6.2**: Database must be optimized for read-heavy operations
- **NFR-6.3**: System must implement caching strategy (Redis/Memcached)

### 7. Browser Support
- **NFR-7.1**: Must support latest versions of Chrome, Firefox, Safari, Edge
- **NFR-7.2**: Must gracefully degrade for older browsers

---

## Constraints

1. Must use specified technology stack (Vue 3, Laravel, Inertia.js, MySQL, Tailwind)
2. Initial release focused on Super Bowl, but architecture must be flexible for other sports/events
3. Development timeline should prioritize MVP features first
4. Must be deployable on standard LAMP stack hosting

---

## Assumptions

1. Users have basic familiarity with online registration and form submission
2. Admins will have technical knowledge to manage games and questions
3. Initial user base will be English-speaking (internationalization future consideration)
4. Users will primarily access via web browser (native mobile apps future consideration)

---

## Future Considerations

1. Mobile native apps (iOS/Android)
2. Live scoring during games
3. Push notifications
4. Multi-language support
5. Integration with sports APIs for automatic answer validation
6. Betting/wagering features (where legal)
7. Social media integration (share results)
8. Advanced analytics and insights
9. Tournament/season-long competitions
10. Rewards/badge system for top performers

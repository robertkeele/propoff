# PropOff Development - Project Summary

**Project**: PropOff - Game Prediction Application
**Technology Stack**: Vue 3, Laravel 10, Inertia.js, MySQL, Tailwind CSS
**Current Status**: Phase 5 (80% complete) → Major Enhancement in Progress

---

## Current Implementation Status

### ✅ Completed Phases:

**Phase 0**: Project Setup (100%)
- Laravel 10.49.1 + Breeze authentication
- Vue 3 + Inertia.js + Tailwind CSS
- MySQL database configuration

**Phase 1**: Database & Models (100%)
- 14 database tables (4 Laravel default + 10 custom)
- 9 Eloquent models with complete relationships
- **Group-specific answer system** (core feature)
- Factories and seeders for test data

**Phase 2**: Authentication & Authorization (100%)
- 3 custom middleware classes
- 4 authorization policies
- 8 form request validation classes

**Phase 3**: Backend User Features (100%)
- 5 user-facing controllers
- 3 service classes (Game, Submission, Leaderboard)
- Complete game participation flow
- Type-aware answer grading
- Advanced leaderboard calculations

**Phase 4**: Backend Admin Features (100%)
- 7 admin controllers
- 2 background jobs (score calculation, leaderboard updates)
- 60+ admin routes with middleware protection
- Group-specific grading system
- CSV export functionality

**Phase 5**: Frontend UI (80%)
- 7 admin Vue components
- 3+ user-facing components
- Responsive design with Tailwind CSS
- Complete game play interface
- Admin dashboard and management tools

---

## 🚨 MAJOR ENHANCEMENT IN PROGRESS

### Captain System Implementation

**Date Started**: November 20, 2025

**Objective**: Implement Captain role with group-level question customization and dual grading model

### Key Changes:

1. **Terminology Change**: Games → Events (throughout entire application)

2. **Captain Role**:
   - Per-group captaincy via `user_groups.is_captain` (not global role)
   - Anyone with admin invitation link can create group and become captain
   - Multiple captains per group with equal control

3. **Question Architecture** (3-tier system):
   ```
   Question Templates (Admin, reusable)
        ↓
   Event Questions (Event-specific, variables filled)
        ↓
   Group Questions (Captain customizable, add/remove/modify)
   ```

4. **Dual Grading Model**:
   - **Captain Mode**: Captain sets answers for their group (live grading)
   - **Admin Mode**: Group uses admin-set event answers (after-event grading)
   - Captain chooses mode when creating group

5. **Captain Capabilities**:
   - Create group from admin invitation link
   - Customize questions (add custom, remove unwanted, modify existing)
   - Set correct answers for their group
   - Promote other members to captain
   - Manage group members
   - Calculate scores and view leaderboard

### Design Decisions:
- ✅ Anyone with admin link can become captain
- ✅ Multiple captains have equal control
- ✅ Captains can modify questions even after event starts
- ✅ Admin answers hidden until after event ends
- ✅ Custom captain questions isolated to their group
- ✅ No cross-group leaderboard comparison (different questions)

---

## 📚 Key Documentation

### Implementation Guides:
- **`docs/15-captain-system-quick-reference.md`** ⭐ **START HERE**
  - Concise checklist for each phase
  - Code snippets and examples
  - Progress tracking checkboxes
  - Quick reference during implementation

- **`docs/14-captain-system-implementation-plan.md`**
  - Comprehensive implementation plan (42-56 hours)
  - Complete database schema changes
  - All migration files with full code
  - Model changes and relationships
  - Controller architecture
  - 10 phases with detailed tasks

### Requirements & Design:
- **`docs/MyEnhancements.txt`**
  - Original user requirements
  - Captain role specification
  - Question flow clarification
  - Grading model requirements

### Previous Work:
- `docs/01-requirements.md` - Original functional requirements
- `docs/02-design.md` - Database design and ERD
- `docs/03-task-list.md` - Original task breakdown
- `docs/01-phase1-summary.md` - Phase 1 completion summary
- `docs/02-phase2-summary.md` - Phase 2 completion summary
- `docs/03-phase3-summary.md` - Phase 3 completion summary
- `docs/04-phase4-summary.md` - Phase 4 completion summary
- `docs/05-phase5-summary.md` - Phase 5 completion summary

### Guest System (On Hold):
- `docs/08-qr-code-guest-flow-proposal.md`
- `docs/09-guest-implementation-roadmap.md`
- `docs/10-guest-flow-final-spec.md`
- `docs/11-guest-system-implementation-summary.md`
- `docs/12-guest-vue-components-code.md`
- `docs/13-guest-implementation-guide.md`

*Note: Guest system fully designed but not implemented. On hold pending captain system completion.*

---

## 🗄️ Database Structure

### Current Tables (14):
1. users (with role: admin/user/guest)
2. password_reset_tokens
3. failed_jobs
4. personal_access_tokens
5. groups
6. user_groups
7. games *(will become: events)*
8. question_templates
9. questions *(will become: event_questions)*
10. group_question_answers
11. submissions
12. user_answers
13. leaderboards
14. game_group_invitations *(will become: event_invitations)*

### New Tables (Captain System):
15. **group_questions** - Per-group customizable questions
16. **event_answers** - Admin-set answers for events
17. **captain_invitations** - Links for captain recruitment

---

## 🔄 Captain System Implementation Roadmap

### Phase 1: Database Foundation (4-6 hours)
- Rename games → events
- Add is_captain to user_groups
- Create group_questions table
- Create event_answers table
- Create captain_invitations table
- Migrate existing data

### Phase 2: Model Layer (3-4 hours)
- Rename Game → Event, Question → EventQuestion
- Create GroupQuestion, EventAnswer, CaptainInvitation models
- Update all model relationships
- Add captain helper methods

### Phase 3: Rename Controllers & Routes (3-4 hours)
- Rename all game-related controllers
- Update all routes from /games to /events
- Update all form requests and policies
- Global find/replace for terminology

### Phase 4: Captain Controllers (6-8 hours)
- Create Captain namespace controllers (5 controllers)
- Create EnsureIsCaptainOfGroup middleware
- Define captain routes
- Implement group creation from invitation
- Implement question customization
- Implement captain grading

### Phase 5: Admin Captain Features (4-5 hours)
- Create CaptainInvitationController
- Create EventAnswerController
- Update admin UI for invitations
- Implement event-level grading

### Phase 6: Dual Grading Logic (4-5 hours)
- Update SubmissionService for dual grading
- Implement captain grading mode
- Implement admin grading mode
- Handle edge cases

### Phase 7: Captain Vue Components (8-10 hours)
- Create Captain/Dashboard.vue
- Create question management UI
- Create grading interface
- Create member management UI
- Update navigation

### Phase 8: Admin UI Updates (4-5 hours)
- Simplify admin dashboard (remove statistics)
- Update event management UI
- Create captain invitation management
- Update terminology throughout

### Phase 9: Player UI Updates (2-3 hours)
- Update terminology (Games → Events)
- Update to use group_questions
- Update navigation

### Phase 10: Testing & Documentation (4-6 hours)
- Test complete captain flow
- Test dual grading modes
- Test permissions
- Update documentation

**Total Estimate: 42-56 hours**

---

## 🎯 Next Steps

### Immediate Actions:
1. **Review implementation plan** (`docs/14-captain-system-implementation-plan.md`)
2. **Backup current database** before starting Phase 1
3. **Create feature branch**: `git checkout -b feature/captain-system`
4. **Begin Phase 1**: Database migrations

### Start Implementation:
```bash
# Backup database
mysqldump -u root -p propoff > backup_before_captain_system_$(date +%Y%m%d).sql

# Create feature branch
git checkout -b feature/captain-system
git commit -m "Starting captain system implementation"

# Follow checklist in docs/15-captain-system-quick-reference.md
```

---

## 📊 Feature Comparison

### Before (Current System):
- Admin creates game with questions
- Admin sets correct answers (one set for all groups)
- Users join groups and play
- All groups have same questions
- Only admin can modify anything

### After (Captain System):
- Admin creates event with question framework
- Admin generates captain invitation links
- **Captain creates group from link**
- **Captain customizes questions for their group**
- **Captain chooses grading mode (captain or admin)**
- **Captain can set answers for live grading**
- Users join captain's group and play
- Each group can have different questions
- Multiple captains can co-manage groups

---

## 🔐 Test Accounts

**Admin Account**:
- Email: admin@propoff.com
- Password: password

**Regular User**:
- Email: user@propoff.com
- Password: password

---

## 🚀 Development Commands

```bash
# Database
php artisan migrate
php artisan migrate:fresh --seed
php artisan tinker

# Frontend
npm run dev
npm run build

# Server
php artisan serve

# Testing
php artisan test
```

---

## 📝 Important Notes

### Migration Safety:
- Always backup database before migrations
- Test rollback: `php artisan migrate:rollback --step=9`
- Keep old columns temporarily during transition

### Terminology Changes:
- Global find/replace: Game → Event
- Update all routes: /games → /events
- Update all variables: $game → $event
- Update all Vue components

### Captain Role:
- Stored in `user_groups.is_captain` (per-group)
- NOT a global user role
- User can be captain of multiple groups
- User can be captain of one group and member of another

### Grading Modes:
- **captain**: Uses `group_question_answers` table
- **admin**: Uses `event_answers` table
- Mode is set on group creation
- Can be changed by captain later (careful with mid-event changes)

---

## 🔗 Quick Links

- **Quick Reference**: `docs/15-captain-system-quick-reference.md` ⭐
- **Full Plan**: `docs/14-captain-system-implementation-plan.md`
- **Requirements**: `docs/MyEnhancements.txt`
- **GitHub**: (Add repository URL)
- **Staging**: (Add staging URL)

---

**Last Updated**: November 20, 2025
**Current Priority**: Captain System Implementation
**Status**: Ready to begin Phase 1

# Phase 5 Remaining Tasks - Frontend Components

**Date**: November 16, 2025
**Status**: Phase 5 80% Complete - 20% Remaining

---

## Overview

Phase 5 is 80% complete with core admin and user components functional. This document outlines the remaining 20% of frontend components needed to complete the phase.

---

## Completed Components ✅

### Admin Components (7):
1. ✅ Admin/Dashboard.vue
2. ✅ Admin/Games/Index.vue
3. ✅ Admin/Games/Show.vue
4. ✅ Admin/Games/Create.vue
5. ✅ Admin/Games/Edit.vue
6. ✅ Admin/Grading/Index.vue (CORE FEATURE)
7. ✅ Admin/Users/Index.vue
8. ✅ Admin/Users/Show.vue
9. ✅ Admin/Groups/Index.vue
10. ✅ Admin/Groups/Show.vue
11. ✅ Admin/QuestionTemplates/Create.vue
12. ✅ Admin/QuestionTemplates/Edit.vue
13. ✅ Admin/QuestionTemplates/Index.vue

### User Components (existing):
1. ✅ Dashboard.vue (updated)
2. ✅ Games/Available.vue
3. ✅ Games/Play.vue
4. ✅ Submissions/Continue.vue
5. ✅ Groups/Create.vue
6. ✅ Groups/Index.vue
7. ✅ Groups/Join.vue
8. ✅ Groups/Show.vue
9. ✅ Leaderboards/Game.vue
10. ✅ Leaderboards/Group.vue

---

## Remaining Components to Create (20%)

### 1. Admin Question Management (HIGH PRIORITY)

#### Admin/Questions/Index.vue
**Purpose**: List and manage all questions for a specific game

**Required Props**:
```javascript
{
    game: Object,              // Game details
    questions: Array,          // All questions for the game
}
```

**Key Features**:
- Display questions list with drag-and-drop reordering
- Question type badges (multiple_choice, yes_no, numeric, text)
- Points display per question
- Quick actions: Edit, Duplicate, Delete
- Bulk actions: Reorder, Delete selected
- Create new question button
- Create from template button
- Bulk import from another game
- Link to Grading page
- Total points calculation
- Empty state with call-to-action

**Layout**:
```
Header: Game Title + "Questions" | [Back to Game] [Add Question]
---
Game Info Card: Total Questions | Total Points | Event Date | Status
---
Quick Actions Bar:
  - Create from Template
  - Bulk Import
  - Set Answers & Grade
---
Questions List (Draggable):
  Each Question Card:
    - Order Number Badge
    - Type Badge (color-coded)
    - Points Badge
    - Question Text
    - Options (if multiple choice)
    - Actions: Edit | Duplicate | Delete
---
Empty State (if no questions):
  - Icon
  - "No questions yet"
  - Create Question button
```

**Actions**:
- `startReorder()` - Enable drag-and-drop mode
- `saveOrder()` - POST to admin.questions.reorder
- `cancelReorder()` - Reset to original order
- `deleteQuestion(id)` - DELETE with confirmation
- `duplicateQuestion(id)` - POST to admin.questions.duplicate

**Routes Used**:
- `admin.questions.index` (current)
- `admin.questions.create`
- `admin.questions.edit`
- `admin.questions.destroy`
- `admin.questions.duplicate`
- `admin.questions.reorder`
- `admin.questions.create-from-template`
- `admin.grading.index`

---

#### Admin/Questions/Create.vue
**Purpose**: Create a new question for a game

**Required Props**:
```javascript
{
    game: Object,              // Game details
    questionTemplates: Array,  // Available templates (optional)
}
```

**Key Features**:
- Question text input (textarea)
- Question type selector (multiple_choice, yes_no, numeric, text)
- Points input (number, default 10)
- Order number input
- Dynamic options management (for multiple_choice):
  - Add/Remove options
  - Minimum 2 options required
- Form validation
- Preview section
- Link back to questions list

**Form Fields**:
```javascript
form = {
    question_text: '',
    type: 'multiple_choice',
    points: 10,
    order_number: null,  // Auto-assigned if null
    options: [],         // Only for multiple_choice
}
```

**Layout**:
```
Header: "Create Question" | [Back to Questions List]
---
Game Context Card:
  - Game Title
  - Current Question Count
  - Event Date
---
Question Form:
  - Question Text (textarea, required)
  - Type Selector (radio buttons with descriptions)
  - Points (number input, min 1, default 10)
  - Order Number (number, optional)
  
  [If type = multiple_choice]
    Options Section:
      - Option 1 input + Remove
      - Option 2 input + Remove
      - ...
      - [Add Option button]
  
  - Preview Section (blue box)
    Shows how question will appear to users
---
[Cancel] [Create Question]
```

**Validation**:
- Question text required
- Points >= 1
- Multiple choice must have at least 2 options
- Options cannot be empty strings

---

#### Admin/Questions/Edit.vue
**Purpose**: Edit an existing question

**Required Props**:
```javascript
{
    game: Object,
    question: Object,
}
```

**Key Features**:
- Same form as Create
- Pre-filled with existing data
- Cannot change question type (would invalidate existing answers)
- Display existing answers count warning
- Update button instead of Create

**Warning Display**:
```
⚠️ This question has 15 submitted answers.
Changing the question text or options may affect grading accuracy.
```

**Form Fields**:
```javascript
form = {
    question_text: question.question_text,
    points: question.points,
    order_number: question.order_number,
    options: question.options || [],
}
```

---

### 2. Shared/Reusable Components (MEDIUM PRIORITY)

#### Components/Modal.vue
**Purpose**: Reusable modal dialog component

**Props**:
```javascript
{
    show: Boolean,
    maxWidth: String, // 'sm', 'md', 'lg', 'xl', '2xl'
    closeable: Boolean,
}
```

**Features**:
- Backdrop click to close (if closeable)
- ESC key to close (if closeable)
- Slot for content
- Slot for footer
- Smooth transitions
- Focus trap

**Usage Example**:
```vue
<Modal :show="showModal" @close="showModal = false">
    <template #default>
        <h2>Modal Title</h2>
        <p>Modal content here</p>
    </template>
    <template #footer>
        <button @click="showModal = false">Close</button>
    </template>
</Modal>
```

---

#### Components/ConfirmationDialog.vue
**Purpose**: Confirm destructive actions

**Props**:
```javascript
{
    show: Boolean,
    title: String,
    message: String,
    confirmText: String,
    cancelText: String,
    danger: Boolean,  // Red confirm button if true
}
```

**Emits**:
- `confirm`
- `cancel`

**Usage Example**:
```vue
<ConfirmationDialog
    :show="showConfirm"
    title="Delete Question"
    message="Are you sure? This action cannot be undone."
    confirmText="Delete"
    cancelText="Cancel"
    :danger="true"
    @confirm="handleDelete"
    @cancel="showConfirm = false"
/>
```

---

#### Components/Toast.vue
**Purpose**: Show temporary notification messages

**Features**:
- Auto-dismiss after 3-5 seconds
- Success, error, warning, info types
- Icon based on type
- Close button
- Stack multiple toasts
- Slide-in animation

**Usage via Composable**:
```javascript
import { useToast } from '@/Composables/useToast';

const toast = useToast();

toast.success('Game created successfully!');
toast.error('Failed to save question');
toast.warning('Game will lock in 10 minutes');
toast.info('New submission received');
```

---

#### Components/Pagination.vue
**Purpose**: Reusable pagination controls

**Props**:
```javascript
{
    links: Array,       // Laravel pagination links
    preserveScroll: Boolean,
    preserveState: Boolean,
}
```

**Features**:
- Previous/Next buttons
- Page numbers
- Current page highlighting
- Disabled state for first/last
- Responsive (show fewer pages on mobile)

---

#### Components/DataTable.vue
**Purpose**: Reusable data table with sorting/filtering

**Props**:
```javascript
{
    columns: Array,     // [{key: 'name', label: 'Name', sortable: true}]
    data: Array,
    sortable: Boolean,
    searchable: Boolean,
}
```

**Features**:
- Column headers
- Sortable columns (click to sort)
- Search filter input
- Empty state
- Loading state
- Slot for custom cell rendering

---

### 3. Enhanced User Features (LOW PRIORITY)

#### Leaderboards/User.vue
**Purpose**: Show user's personal leaderboard positions across all games

**Required Props**:
```javascript
{
    positions: Array,  // User's positions in different games/groups
    statistics: Object, // Overall stats
}
```

**Features**:
- List of all games user participated in
- Position in each game/group
- Score and percentage
- Rank badges (1st, 2nd, 3rd get special icons)
- Filter by game status
- Statistics overview

---

#### Profile/Edit.vue (Enhanced)
**Purpose**: Edit user profile with avatar upload

**Current**: Basic edit form exists in Breeze
**Enhancement**:
- Avatar upload with preview
- Crop functionality
- Email verification status
- Password change section
- Delete account section
- Statistics summary (read-only)

---

#### Submissions/History.vue
**Purpose**: View all user's past submissions

**Required Props**:
```javascript
{
    submissions: Object, // Paginated submissions
    filters: Object,     // Active filters
}
```

**Features**:
- List of all submissions
- Filter by game, group, status
- Sort by date, score
- View details button
- Pagination
- Export user's results

---

### 4. Admin Statistics Pages (LOW PRIORITY)

#### Admin/Games/Statistics.vue
**Purpose**: Detailed game statistics and analytics

**Required Props**:
```javascript
{
    game: Object,
    statistics: Object,
}
```

**Features**:
- Participation rate by group
- Score distribution chart
- Question difficulty analysis
- Submission timeline
- Top performers
- Completion percentage

---

#### Admin/Users/Activity.vue
**Purpose**: View detailed user activity log

**Required Props**:
```javascript
{
    user: Object,
    activities: Object, // Paginated activities
}
```

**Features**:
- Chronological activity feed
- Filter by activity type
- Date range filter
- Export activity log

---

#### Admin/Groups/Members.vue
**Purpose**: Manage group members in detail

**Required Props**:
```javascript
{
    group: Object,
    members: Object, // Paginated members
    availableUsers: Array,
}
```

**Features**:
- Members list with stats
- Add member form
- Remove member (with confirmation)
- Member role in group (if applicable)
- Bulk add/remove
- Export members list

---

## Component Priority & Complexity

### HIGH PRIORITY (Complete Phase 5 Core):
1. **Admin/Questions/Index.vue** - Complex (drag-and-drop, bulk actions)
2. **Admin/Questions/Create.vue** - Medium (dynamic options)
3. **Admin/Questions/Edit.vue** - Medium (similar to Create)

### MEDIUM PRIORITY (Quality of Life):
4. **Components/Modal.vue** - Simple (reusable)
5. **Components/ConfirmationDialog.vue** - Simple (uses Modal)
6. **Components/Toast.vue** - Medium (composable + component)
7. **Components/Pagination.vue** - Simple (standard pattern)

### LOW PRIORITY (Nice to Have):
8. **Leaderboards/User.vue** - Medium
9. **Profile/Edit.vue** (Enhanced) - Medium
10. **Submissions/History.vue** - Medium
11. **Admin/Games/Statistics.vue** - Complex (charts/graphs)
12. **Admin/Users/Activity.vue** - Simple
13. **Admin/Groups/Members.vue** - Medium
14. **Components/DataTable.vue** - Complex (sorting, filtering)

---

## Estimated Time to Complete

- **HIGH Priority (3 components)**: 4-6 hours
- **MEDIUM Priority (4 components)**: 3-4 hours
- **LOW Priority (7 components)**: 8-10 hours

**Total**: 15-20 hours to 100% completion

**Core Functionality (HIGH only)**: 4-6 hours to 90% completion

---

## Testing Checklist (After Creation)

For each component created:
- [ ] Component renders without errors
- [ ] Props are properly validated
- [ ] Form submissions work correctly
- [ ] Validation messages display
- [ ] Responsive design works on mobile
- [ ] Accessibility (keyboard navigation, ARIA labels)
- [ ] Inertia.js navigation preserves state
- [ ] Loading states display correctly
- [ ] Error states handled gracefully

---

## Next Steps

1. **Install PowerShell 7+** to enable directory/file creation
2. **Create HIGH priority components** (Questions management)
3. **Test question CRUD flow** end-to-end
4. **Create MEDIUM priority components** (reusable components)
5. **Refactor existing components** to use new shared components
6. **Create LOW priority components** (enhanced features)
7. **Comprehensive testing** of all flows
8. **Update documentation** with completion status

---

## Notes

- All components should follow existing patterns from completed components
- Use Heroicons for consistency
- Use Tailwind CSS utility classes
- Follow Vue 3 Composition API with `<script setup>`
- Use Inertia.js `Link` and `router` for navigation
- Use `useForm` for form handling
- Preserve scroll and state where appropriate
- Add loading states to buttons during async operations
- Implement proper error handling

---

## PowerShell Installation Required

To create the remaining components, PowerShell 7+ is needed. Install from:
https://aka.ms/powershell

After installation, restart the session and continue with component creation.

---

**Status**: Awaiting PowerShell installation to proceed with component creation.

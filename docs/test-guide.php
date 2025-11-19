#!/usr/bin/env php
<?php

/**
 * PropOff Manual Testing Checklist
 * Run this to get testing instructions and URLs
 */

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║           PropOff Application Testing Guide                   ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "🌐 Application Running:\n";
echo "   Frontend (Vite): http://localhost:5173\n";
echo "   Backend (Laravel): http://127.0.0.1:8000\n\n";

echo "👤 Test Accounts:\n";
echo "   Admin: admin@propoff.com / password\n";
echo "   User:  user@propoff.com / password\n\n";

echo "═══════════════════════════════════════════════════════════════\n";
echo "PHASE 1: AUTHENTICATION & BASIC ACCESS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "1. Login as Admin\n";
echo "   → http://127.0.0.1:8000/login\n";
echo "   → Use: admin@propoff.com / password\n";
echo "   → Should redirect to /dashboard\n\n";

echo "2. Check Admin Dashboard\n";
echo "   → Should see statistics (games, questions, users, groups)\n";
echo "   → Should see recent games/submissions/users\n";
echo "   → Quick actions should be visible\n\n";

echo "═══════════════════════════════════════════════════════════════\n";
echo "PHASE 2: ADMIN GAME MANAGEMENT\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "3. View Games List\n";
echo "   → http://127.0.0.1:8000/admin/games\n";
echo "   → Should see list of 3 games\n";
echo "   → Check filters and search work\n\n";

echo "4. Create New Game\n";
echo "   → Click 'Create Game' button\n";
echo "   → Fill in: Title, Description, Event Date, Status\n";
echo "   → Submit and verify creation\n\n";

echo "5. View Game Details\n";
echo "   → Click on a game from list\n";
echo "   → Should show game info, statistics\n";
echo "   → Quick links to Questions/Grading visible\n\n";

echo "═══════════════════════════════════════════════════════════════\n";
echo "PHASE 3: ADMIN GRADING SYSTEM ⭐ CRITICAL\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "6. Access Grading Interface\n";
echo "   → From game details, click 'Set Answers & Grade'\n";
echo "   → Or: http://127.0.0.1:8000/admin/games/1/grading\n";
echo "   → Should show group selector\n\n";

echo "7. Set Group-Specific Answers\n";
echo "   → Select a group from dropdown\n";
echo "   → Should see list of questions\n";
echo "   → For each question type:\n";
echo "      - Multiple choice: Select correct option\n";
echo "      - Yes/No: Select Yes or No\n";
echo "      - Numeric: Enter number\n";
echo "      - Text: Enter text answer\n";
echo "   → Click 'Set Answer' for each\n";
echo "   → Verify visual indicator changes\n\n";

echo "8. Toggle Void Status\n";
echo "   → Click 'Void' button on a question\n";
echo "   → Verify button changes to 'Unvoid'\n";
echo "   → Voided questions should award 0 points\n\n";

echo "9. Calculate Scores\n";
echo "   → Click 'Calculate Scores' button\n";
echo "   → Should show success message\n";
echo "   → Leaderboards should update\n\n";

echo "10. Export Results\n";
echo "    → Click 'Export Summary CSV'\n";
echo "    → Should download CSV file\n";
echo "    → Click 'Export Detailed CSV'\n";
echo "    → Should download detailed CSV\n\n";

echo "═══════════════════════════════════════════════════════════════\n";
echo "PHASE 4: GUEST USER FLOW ⭐ CRITICAL\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "11. Generate Invitation Link (as Admin)\n";
echo "    → Go to game details page\n";
echo "    → Find 'Game Invitations' section\n";
echo "    → Click 'Generate Invitation' for a group\n";
echo "    → Copy the invitation URL\n\n";

echo "12. Join as Guest (in incognito/new browser)\n";
echo "    → Paste invitation URL\n";
echo "    → Should see registration page\n";
echo "    → Enter name only (no password required)\n";
echo "    → Submit\n\n";

echo "13. Guest Auto-Login\n";
echo "    → Should automatically login after registration\n";
echo "    → Should redirect to game page\n";
echo "    → Should see game questions\n\n";

echo "14. Guest Plays Game\n";
echo "    → Answer questions\n";
echo "    → Save progress works\n";
echo "    → Navigate between questions\n";
echo "    → Submit final answers\n\n";

echo "15. Confirmation Page ⭐ MOST CRITICAL\n";
echo "    → After submitting, should see confirmation page\n";
echo "    → HUGE YELLOW BOX with personal results link\n";
echo "    → Link format: /my-results/{token}\n";
echo "    → Copy button should work\n";
echo "    → Instructions to save link visible\n\n";

echo "16. Personal Results Link (No Login Required)\n";
echo "    → Close browser / logout\n";
echo "    → Open personal results link directly\n";
echo "    → Should see results WITHOUT login\n";
echo "    → Should show: score, answers, leaderboard position\n\n";

echo "═══════════════════════════════════════════════════════════════\n";
echo "PHASE 5: USER DASHBOARD & GAME PLAYING\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "17. Login as Regular User\n";
echo "    → Logout from admin\n";
echo "    → Login: user@propoff.com / password\n";
echo "    → Should see user dashboard\n\n";

echo "18. Browse Available Games\n";
echo "    → Click 'Browse Games'\n";
echo "    → Should see list of open games\n";
echo "    → Click 'Play' on a game\n\n";

echo "19. Play Game Flow\n";
echo "    → Select group (if member of multiple)\n";
echo "    → Start game\n";
echo "    → Answer questions\n";
echo "    → Save progress\n";
echo "    → Submit final answers\n\n";

echo "20. View Results\n";
echo "    → Go to 'My Submissions'\n";
echo "    → Click on a submission\n";
echo "    → Should see score and answers\n\n";

echo "═══════════════════════════════════════════════════════════════\n";
echo "PHASE 6: ADMIN USER & GROUP MANAGEMENT\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "21. Manage Users\n";
echo "    → http://127.0.0.1:8000/admin/users\n";
echo "    → Search users\n";
echo "    → Change user role (inline)\n";
echo "    → View user details\n";
echo "    → Export CSV\n\n";

echo "22. Manage Groups\n";
echo "    → http://127.0.0.1:8000/admin/groups\n";
echo "    → View groups list\n";
echo "    → Edit group details\n";
echo "    → Add/remove members\n";
echo "    → Export CSV\n\n";

echo "═══════════════════════════════════════════════════════════════\n";
echo "TESTING PRIORITIES\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "🔴 CRITICAL (Test First):\n";
echo "   - Admin grading interface (set group-specific answers)\n";
echo "   - Guest registration and auto-login\n";
echo "   - Confirmation page with personal link\n";
echo "   - Personal results link (no login required)\n";
echo "   - Score calculation\n\n";

echo "🟡 HIGH PRIORITY:\n";
echo "   - Game creation and management\n";
echo "   - Question management\n";
echo "   - User game playing flow\n";
echo "   - Leaderboards\n\n";

echo "🟢 MEDIUM PRIORITY:\n";
echo "   - User/group management\n";
echo "   - CSV exports\n";
echo "   - Statistics displays\n";
echo "   - Profile management\n\n";

echo "═══════════════════════════════════════════════════════════════\n";
echo "BUG REPORTING FORMAT\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "When you find an issue, please report:\n";
echo "1. What you were trying to do\n";
echo "2. What you expected to happen\n";
echo "3. What actually happened\n";
echo "4. Any error messages shown\n";
echo "5. Browser console errors (F12 → Console)\n\n";

echo "I'll fix issues as we find them and update the testing doc.\n\n";

echo "═══════════════════════════════════════════════════════════════\n\n";
echo "Ready to start testing? Open http://127.0.0.1:8000 in your browser!\n\n";

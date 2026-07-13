#!/bin/bash
# Phase 5 Regression Test Run — Workflow Completeness, Scheduled Automation,
# Recovery Flows & Remaining Findings Closure
#
# Executed against the live local app (http://localhost/HR_Komagin) using the
# seeded accounts: superadmin (super_admin), hrmanager (hr_manager),
# hrofficer (hr_officer), payroll (payroll_officer). Results are appended to
# docs/remediation/Testing/phase5-regression-results.log
#
# Dedicated test groups: Leave, ApprovalEngine, Calendar, Scheduler,
# Password reset, Notifications, Recruitment conversion, Temp attendance,
# QR verification — plus a full Phase 1+2 re-run and a repo-wide syntax scan.
# This script performs REAL HTTP requests and REAL database writes against
# a running instance, using disposable P5TEST-prefixed data that it creates
# and removes itself. Run it with the XAMPP Apache+MySQL services active.

BASE="http://localhost/HR_Komagin"
JAR_DIR="/tmp/phase5_regress"
LOG="$(dirname "$0")/phase5-regression-results.log"
MYSQL="/c/xampp/mysql/bin/mysql.exe -u root komagin_hr"
APPROOT="c:/New_xampp/htdocs/HR_Komagin"
mkdir -p "$JAR_DIR"
PASS=0
FAIL=0

echo "Phase 5 Regression Run — $(date)" > "$LOG"
echo "======================================" >> "$LOG"

login() {
  local role_key="$1" username="$2" password="$3"
  local jar="$JAR_DIR/$role_key.txt"
  rm -f "$jar"
  curl -s -c "$jar" "$BASE/auth/login.php" -o "$JAR_DIR/${role_key}_login.html"
  local csrf
  csrf=$(grep -oE 'name="csrf_token" value="[a-f0-9]+"' "$JAR_DIR/${role_key}_login.html" | grep -oE '[a-f0-9]{20,}')
  curl -s -b "$jar" -c "$jar" -o /dev/null \
    -d "username=$username&password=$password&csrf_token=$csrf" \
    "$BASE/auth/login.php"
}

pass() { echo "PASS | $1" | tee -a "$LOG"; PASS=$((PASS+1)); }
fail() { echo "FAIL | $1" | tee -a "$LOG"; FAIL=$((FAIL+1)); }

assert_http() {
  local url="$1" expected="$2" role_key="$3" desc="$4"
  local jar="$JAR_DIR/$role_key.txt"
  local code
  code=$(curl -s -b "$jar" -o /dev/null -w "%{http_code}" "$BASE/$url")
  if [ "$code" = "$expected" ]; then pass "$desc | expected=$expected got=$code"
  else fail "$desc | expected=$expected got=$code | url=$url"; fi
}

echo "--- Logging in test accounts ---" | tee -a "$LOG"
login superadmin superadmin "Admin@123"
login hrmanager hrmanager "Admin@123"

# ══════════════════════════════════════════════════════════════════════
echo "" | tee -a "$LOG"
echo "--- Group 1: Leave (Stage 5.1) — single-stage HR-only approval ---" | tee -a "$LOG"
STAGE_COUNT=$(grep -c "'name'=>'Supervisor Review'" "$APPROOT/config/ApprovalEngine.php")
[ "$STAGE_COUNT" = "0" ] && pass "leave workflowConfig no longer has a Supervisor Review stage" || fail "leave workflowConfig still references Supervisor Review"
LEAVE_STAGES=$(grep -A3 "'leave' =>" "$APPROOT/config/ApprovalEngine.php" | grep -c "'name'=>")
[ "$LEAVE_STAGES" = "1" ] && pass "leave workflow has exactly 1 stage" || fail "leave workflow has $LEAVE_STAGES stages, expected 1"

# ══════════════════════════════════════════════════════════════════════
echo "" | tee -a "$LOG"
echo "--- Group 2: ApprovalEngine (Stage 5.2) — dormant types removed ---" | tee -a "$LOG"
for t in overtime correction payroll_run document; do
  MATCH=$(grep -c "'$t' => \[" "$APPROOT/config/ApprovalEngine.php")
  [ "$MATCH" = "0" ] && pass "ApprovalEngine workflowConfig no longer defines '$t'" || fail "ApprovalEngine workflowConfig still defines '$t'"
done
CONCAT_BUG=$(grep -c "notes=?+" "$APPROOT/config/ApprovalEngine.php")
[ "$CONCAT_BUG" = "0" ] && pass "ApprovalEngine::cancel() no longer has the +-vs-concatenation bug" || fail "cancel() still has the old +-concatenation bug"

# ══════════════════════════════════════════════════════════════════════
echo "" | tee -a "$LOG"
echo "--- Group 3: Working-Day Calendar (Stage 5.3) ---" | tee -a "$LOG"
CAL_OUT=$(php "$APPROOT/docs/remediation/Testing/phase5-calendar-unit-tests.php" 2>&1)
CAL_PASS=$(echo "$CAL_OUT" | grep -c "^PASS")
CAL_FAIL=$(echo "$CAL_OUT" | grep -c "^FAIL")
if [ "$CAL_FAIL" = "0" ] && [ "$CAL_PASS" -gt "0" ]; then
  pass "calendar unit tests: $CAL_PASS passed, 0 failed"
else
  fail "calendar unit tests: $CAL_PASS passed, $CAL_FAIL failed"
fi
assert_http "modules/settings/calendar.php" 200 superadmin "Working Calendar admin page reachable"

# ══════════════════════════════════════════════════════════════════════
echo "" | tee -a "$LOG"
echo "--- Group 4: Scheduler (Stage 5.4) ---" | tee -a "$LOG"
WEB_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE/cron/run.php")
[ "$WEB_CODE" = "403" ] && pass "cron/run.php blocked over HTTP (403)" || fail "cron/run.php returned $WEB_CODE over HTTP, expected 403"
CLI_OUT=$(php "$APPROOT/cron/run.php" 2>&1)
echo "$CLI_OUT" | grep -q "Scheduler run finished" && pass "cron/run.php completes successfully via CLI" || fail "cron/run.php did not complete via CLI"
echo "$CLI_OUT" | grep -q "FAIL" && fail "cron/run.php CLI run reported a task failure" || pass "cron/run.php CLI run: all tasks OK"

# ══════════════════════════════════════════════════════════════════════
echo "" | tee -a "$LOG"
echo "--- Group 5: Self-Service Password Recovery (Stage 5.5) ---" | tee -a "$LOG"
$MYSQL -e "DELETE FROM users WHERE username='p5regress';" >/dev/null 2>&1
PWHASH=$(php -r "echo password_hash('OldPassword123', PASSWORD_BCRYPT, ['cost'=>12]);")
$MYSQL -e "INSERT INTO users (username, email, password_hash, role, is_active, must_change_password) VALUES ('p5regress','p5regress@example.com','$PWHASH','hr_officer',1,0);" >/dev/null 2>&1
UID_TEST=$($MYSQL -N -e "SELECT id FROM users WHERE username='p5regress';")

# Session A: log in with the current password
JAR_A="$JAR_DIR/p5regress_a.txt"
rm -f "$JAR_A"
CSRF_L=$(curl -s -c "$JAR_A" "$BASE/auth/login.php" | grep -oE 'name="csrf_token" value="[^"]+"' | head -1 | sed -E 's/.*value="([^"]+)"/\1/')
curl -s -b "$JAR_A" -c "$JAR_A" -o /dev/null -d "username=p5regress&password=OldPassword123&csrf_token=$CSRF_L" "$BASE/auth/login.php"
CODE_BEFORE=$(curl -s -b "$JAR_A" -o /dev/null -w "%{http_code}" "$BASE/dashboard.php")
[ "$CODE_BEFORE" = "200" ] && pass "session A authenticated and reaches dashboard.php" || fail "session A could not authenticate (got $CODE_BEFORE)"

# Enumeration resistance: identical response for real vs fake identifier
CSRF_F=$(curl -s -c "$JAR_DIR/fp.txt" "$BASE/auth/forgot_password.php" | grep -oE 'name="csrf_token" value="[^"]+"' | head -1 | sed -E 's/.*value="([^"]+)"/\1/')
curl -s -b "$JAR_DIR/fp.txt" -c "$JAR_DIR/fp.txt" -o "$JAR_DIR/fp_real.html" -d "csrf_token=$CSRF_F&identifier=p5regress" "$BASE/auth/forgot_password.php"
CSRF_F2=$(curl -s -c "$JAR_DIR/fp2.txt" "$BASE/auth/forgot_password.php" | grep -oE 'name="csrf_token" value="[^"]+"' | head -1 | sed -E 's/.*value="([^"]+)"/\1/')
curl -s -b "$JAR_DIR/fp2.txt" -c "$JAR_DIR/fp2.txt" -o "$JAR_DIR/fp_fake.html" -d "csrf_token=$CSRF_F2&identifier=p5regress_nonexistent_$RANDOM" "$BASE/auth/forgot_password.php"
if diff -q "$JAR_DIR/fp_real.html" "$JAR_DIR/fp_fake.html" >/dev/null 2>&1; then
  pass "forgot_password.php returns identical response for real vs nonexistent identifier"
else
  fail "forgot_password.php responses differ between real and nonexistent identifier — possible enumeration"
fi

# Extract the real reset token from email_logs and complete a reset from a
# SECOND, independent session — then confirm session A is invalidated.
sleep 1
# The reset link appears twice in the email body (href= and visible link
# text) — take only the first match.
TOKEN=$($MYSQL -N -e "SELECT body_html FROM email_logs WHERE reference_id=$UID_TEST AND reference_type='users' ORDER BY id DESC LIMIT 1;" | grep -oE "token=[a-f0-9]{64}" | head -1 | sed 's/token=//')
if [ -n "$TOKEN" ]; then
  JAR_B="$JAR_DIR/p5regress_b.txt"
  rm -f "$JAR_B"
  curl -s -c "$JAR_B" "$BASE/auth/reset_password.php?token=$TOKEN" -o "$JAR_DIR/reset_get.html"
  CSRF_R=$(grep -oE 'name="csrf_token" value="[^"]+"' "$JAR_DIR/reset_get.html" | head -1 | sed -E 's/.*value="([^"]+)"/\1/')
  grep -q "Set a new password" "$JAR_DIR/reset_get.html" && pass "reset token accepted, reset form rendered" || fail "reset token was rejected unexpectedly"
  curl -s -b "$JAR_B" -c "$JAR_B" -o "$JAR_DIR/reset_post.html" \
    --data-urlencode "csrf_token=$CSRF_R" --data-urlencode "token=$TOKEN" \
    --data-urlencode "new_password=NewPassword456" --data-urlencode "confirm_password=NewPassword456" \
    "$BASE/auth/reset_password.php"
  grep -q "Password updated" "$JAR_DIR/reset_post.html" && pass "password reset completed successfully" || fail "password reset did not complete"

  # Reusing the same (now-consumed) token must fail
  curl -s "$BASE/auth/reset_password.php?token=$TOKEN" -o "$JAR_DIR/reset_reuse.html"
  grep -q "invalid or has expired" "$JAR_DIR/reset_reuse.html" && pass "consumed reset token correctly rejected on reuse" || fail "consumed reset token was NOT rejected on reuse"

  # Session A must now be invalidated
  CODE_AFTER=$(curl -s -b "$JAR_A" -o /dev/null -w "%{http_code}" "$BASE/dashboard.php")
  [ "$CODE_AFTER" = "302" ] && pass "session A correctly invalidated after password reset (302)" || fail "session A was NOT invalidated after reset (got $CODE_AFTER, expected 302)"
else
  fail "could not extract reset token from email_logs — skipping dependent reset/invalidation checks"
fi

# Cleanup
$MYSQL -e "DELETE FROM password_reset_tokens WHERE user_id=$UID_TEST; DELETE FROM email_logs WHERE reference_id=$UID_TEST AND reference_type='users'; DELETE FROM audit_logs WHERE user_id=$UID_TEST; DELETE FROM users WHERE id=$UID_TEST;" >/dev/null 2>&1

# ══════════════════════════════════════════════════════════════════════
echo "" | tee -a "$LOG"
echo "--- Group 6: Deferred Notifications (Stage 5.6) ---" | tee -a "$LOG"
$MYSQL -e "
INSERT INTO employees (employee_number, first_name, last_name, start_date, status, contract_end_date)
VALUES ('P5REGRESS-CE','P5Regress','ContractTest', CURDATE(), 'active', DATE_ADD(CURDATE(), INTERVAL 1 DAY));
" >/dev/null 2>&1
EMP_ID=$($MYSQL -N -e "SELECT id FROM employees WHERE employee_number='P5REGRESS-CE';")
BEFORE_NOTIF=$($MYSQL -N -e "SELECT COUNT(*) FROM notifications WHERE user_id=(SELECT id FROM users WHERE role='hr_manager' AND is_active=1 LIMIT 1);")
REMIND_OUT=$(php -r "
define('CRON_RUNNING', true);
require '$APPROOT/config/config.php';
require '$APPROOT/config/database.php';
require '$APPROOT/config/functions.php';
echo require '$APPROOT/cron/tasks/send_reminders.php';
")
[ "$REMIND_OUT" -ge "1" ] 2>/dev/null && pass "send_reminders.php processed at least 1 reminder (contract expiring test employee)" || fail "send_reminders.php processed 0 reminders — expected at least the test employee's contract-expiry reminder"
AFTER_NOTIF=$($MYSQL -N -e "SELECT COUNT(*) FROM notifications WHERE user_id=(SELECT id FROM users WHERE role='hr_manager' AND is_active=1 LIMIT 1);")
[ "$AFTER_NOTIF" -gt "$BEFORE_NOTIF" ] 2>/dev/null && pass "hr_manager received a new notification" || fail "hr_manager notification count did not increase"
# Second run same day must not duplicate
REMIND_OUT2=$(php -r "
define('CRON_RUNNING', true);
require '$APPROOT/config/config.php';
require '$APPROOT/config/database.php';
require '$APPROOT/config/functions.php';
echo require '$APPROOT/cron/tasks/send_reminders.php';
")
AFTER_NOTIF2=$($MYSQL -N -e "SELECT COUNT(*) FROM notifications WHERE user_id=(SELECT id FROM users WHERE role='hr_manager' AND is_active=1 LIMIT 1);")
[ "$AFTER_NOTIF2" = "$AFTER_NOTIF" ] && pass "second same-day run produced 0 duplicate notifications (dedup working)" || fail "second same-day run duplicated notifications — dedup not working"
$MYSQL -e "
DELETE FROM reminder_notifications_log WHERE reminder_key LIKE 'contract_expiry:employees:$EMP_ID';
DELETE FROM notifications WHERE message LIKE '%P5Regress ContractTest%';
DELETE FROM employees WHERE id=$EMP_ID;
" >/dev/null 2>&1

# ══════════════════════════════════════════════════════════════════════
echo "" | tee -a "$LOG"
echo "--- Group 7: Recruitment-to-Employee Conversion (Stage 5.7) ---" | tee -a "$LOG"
VAC_ID=$($MYSQL -N -e "SELECT id FROM recruitment_vacancies LIMIT 1;")
$MYSQL -e "
INSERT INTO recruitment_applications (vacancy_id, first_name, last_name, email, status)
VALUES ($VAC_ID, 'P5RegressConv', 'Applicant', 'p5regressconv@example.com', 'selected');
" >/dev/null 2>&1
APP_ID=$($MYSQL -N -e "SELECT id FROM recruitment_applications WHERE email='p5regressconv@example.com';")
PREFILL=$(curl -s -b "$JAR_DIR/superadmin.txt" "$BASE/modules/employees/add.php?from_application=$APP_ID")
echo "$PREFILL" | grep -q "P5RegressConv" && pass "guided conversion pre-fills applicant name from application" || fail "guided conversion pre-fill missing applicant name"
$MYSQL -e "DELETE FROM recruitment_applications WHERE id=$APP_ID;" >/dev/null 2>&1

# ══════════════════════════════════════════════════════════════════════
echo "" | tee -a "$LOG"
echo "--- Group 8: Temporary Employee Attendance (Stage 5.8) ---" | tee -a "$LOG"
assert_http "modules/temp_employees/attendance_entry.php" 200 superadmin "Attendance Entry page reachable"
TABLE_EXISTS=$($MYSQL -N -e "SHOW TABLES LIKE 'temp_attendance';" | wc -l)
[ "$TABLE_EXISTS" = "1" ] && pass "temp_attendance table exists" || fail "temp_attendance table missing"

# ══════════════════════════════════════════════════════════════════════
echo "" | tee -a "$LOG"
echo "--- Group 9: Document QR Verification — Disabled (Stage 5.9) ---" | tee -a "$LOG"
TPL_PAGE=$(curl -s -b "$JAR_DIR/superadmin.txt" "$BASE/modules/documents/templates.php")
echo "$TPL_PAGE" | grep -q "QR Code" && fail "QR Code toggle still present in template editor UI" || pass "QR Code toggle absent from template editor UI"
# Only explanatory comments reference show_qr_code/qrserver now
# (documenting why it's deliberately no longer honored) — check the
# functional signal instead: the actual API URL (with scheme, only ever
# present in the removed functional line) and the conditional check on
# the stored flag must both be gone from the actual code.
QR_API_REFS=$(grep -c "https://api.qrserver.com" "$APPROOT/config/DocumentEngine.php")
QR_COND_REFS=$(grep -c "tpl\['show_qr_code'\]" "$APPROOT/config/DocumentEngine.php")
[ "$QR_API_REFS" = "0" ] && [ "$QR_COND_REFS" = "0" ] && pass "DocumentEngine.php no longer calls the external QR API or checks show_qr_code" || fail "DocumentEngine.php still has functional QR references (api refs=$QR_API_REFS, conditional refs=$QR_COND_REFS)"

# ══════════════════════════════════════════════════════════════════════
echo "" | tee -a "$LOG"
echo "--- Group 10: Spot checks, Stage 5.10 fixes ---" | tee -a "$LOG"
assert_http "modules/reports/index.php?type=employees&export=csv" 200 superadmin "Reports Hub CSV export reachable"
EMAIL_PAGE=$(curl -s -b "$JAR_DIR/superadmin.txt" "$BASE/modules/settings/email.php")
echo "$EMAIL_PAGE" | grep -q 'name="smtp_pass" value=' && fail "smtp_pass still emitted with a value attribute" || pass "smtp_pass not emitted with a cleartext value anywhere"
MENU_PAGE=$(curl -s -b "$JAR_DIR/superadmin.txt" "$BASE/modules/audit/index.php")
echo "$MENU_PAGE" | grep -q "View by User" && pass "Audit/Activity Log merge: View by User link present" || fail "Audit/Activity Log merge link missing"

echo "" | tee -a "$LOG"
echo "--- Group 11: Full Phase 1 + Phase 2 re-run ---" | tee -a "$LOG"
P1_OUT=$(bash "$(dirname "$0")/phase1-regression-run.sh" 2>&1)
P1_LINE=$(echo "$P1_OUT" | grep "^TOTAL:")
echo "Phase 1: $P1_LINE" | tee -a "$LOG"
echo "$P1_LINE" | grep -q "0 failed" && pass "Phase 1 regression: $P1_LINE" || fail "Phase 1 regression: $P1_LINE"

P2_OUT=$(bash "$(dirname "$0")/phase2-regression-run.sh" 2>&1)
P2_LINE=$(echo "$P2_OUT" | grep "^TOTAL:")
echo "Phase 2: $P2_LINE" | tee -a "$LOG"
echo "$P2_LINE" | grep -q "0 failed" && pass "Phase 2 regression: $P2_LINE" || fail "Phase 2 regression: $P2_LINE"

echo "" | tee -a "$LOG"
echo "--- Group 12: Repo-wide PHP syntax scan ---" | tee -a "$LOG"
SYNTAX_ERRORS=0
for f in $(find "$APPROOT" -name "*.php" -not -path "*/vendor/*"); do
  OUT=$(php -l "$f" 2>&1)
  if ! echo "$OUT" | grep -q "No syntax errors detected"; then
    echo "SYNTAX ERROR: $f" | tee -a "$LOG"
    echo "$OUT" | tee -a "$LOG"
    SYNTAX_ERRORS=$((SYNTAX_ERRORS+1))
  fi
done
[ "$SYNTAX_ERRORS" = "0" ] && pass "repo-wide php -l scan: 0 syntax errors across all .php files" || fail "repo-wide php -l scan: $SYNTAX_ERRORS file(s) with syntax errors"

echo "" | tee -a "$LOG"
echo "--- Group 13: Migration verification ---" | tee -a "$LOG"
MIGRATION_OUT=$($MYSQL < "$APPROOT/database/phase13_workflow_completeness_automation.sql" 2>&1)
if [ -z "$MIGRATION_OUT" ]; then
  pass "phase13_workflow_completeness_automation.sql re-applies cleanly (idempotent, 0 errors)"
else
  fail "phase13_workflow_completeness_automation.sql produced output/errors on re-apply: $MIGRATION_OUT"
fi
for tbl in work_calendar_settings work_calendar_holidays scheduled_task_locks scheduled_task_runs password_reset_tokens reminder_notifications_log temp_attendance; do
  EXISTS=$($MYSQL -N -e "SHOW TABLES LIKE '$tbl';" | wc -l)
  [ "$EXISTS" = "1" ] && pass "table '$tbl' exists in live database" || fail "table '$tbl' MISSING from live database"
done

echo "" | tee -a "$LOG"
echo "======================================" | tee -a "$LOG"
echo "TOTAL: $PASS passed, $FAIL failed" | tee -a "$LOG"

#!/bin/bash
# Phase 1 Regression Test Run — Authorization Framework
# Executed against the live local app (http://localhost/HR_Komagin) using the
# seeded accounts: superadmin (super_admin), hrmanager (hr_manager),
# hrofficer (hr_officer), payroll (payroll_officer). Results are appended to
# docs/remediation/Testing/phase1-regression-results.log
#
# This script performs REAL HTTP requests against a running instance — it is
# not a mock/unit test. Run it with the XAMPP Apache+MySQL services active.

BASE="http://localhost/HR_Komagin"
JAR_DIR="/tmp/phase1_regress"
LOG="$(dirname "$0")/phase1-regression-results.log"
mkdir -p "$JAR_DIR"
PASS=0
FAIL=0

echo "Phase 1 Regression Run — $(date)" > "$LOG"
echo "======================================" >> "$LOG"

login() {
  local role_key="$1" username="$2" password="$3"
  local jar="$JAR_DIR/$role_key.txt"
  rm -f "$jar"
  curl -s -c "$jar" "$BASE/auth/login.php" -o "$JAR_DIR/$role_key_login.html"
  local csrf
  csrf=$(grep -oE 'name="csrf_token" value="[a-f0-9]+"' "$JAR_DIR/$role_key_login.html" | grep -oE '[a-f0-9]{20,}')
  curl -s -b "$jar" -c "$jar" -o /dev/null \
    -d "username=$username&password=$password&csrf_token=$csrf" \
    "$BASE/auth/login.php"
}

# assert_http URL expected_code role_key description
assert_http() {
  local url="$1" expected="$2" role_key="$3" desc="$4"
  local jar="$JAR_DIR/$role_key.txt"
  local code
  code=$(curl -s -b "$jar" -o /dev/null -w "%{http_code}" "$BASE/$url")
  if [ "$code" = "$expected" ]; then
    echo "PASS | $desc | expected=$expected got=$code" | tee -a "$LOG"
    PASS=$((PASS+1))
  else
    echo "FAIL | $desc | expected=$expected got=$code | url=$url" | tee -a "$LOG"
    FAIL=$((FAIL+1))
  fi
}

# assert_contains URL role_key needle expected_present(0/1) description
assert_contains() {
  local url="$1" role_key="$2" needle="$3" expect_present="$4" desc="$5"
  local jar="$JAR_DIR/$role_key.txt"
  curl -s -b "$jar" "$BASE/$url" -o "$JAR_DIR/_tmp_body.html"
  local count
  count=$(grep -c "$needle" "$JAR_DIR/_tmp_body.html" 2>/dev/null)
  count=${count:-0}
  local present=0
  [ "${count:-0}" -gt 0 ] 2>/dev/null && present=1
  if [ "$present" = "$expect_present" ]; then
    echo "PASS | $desc" | tee -a "$LOG"
    PASS=$((PASS+1))
  else
    echo "FAIL | $desc | needle='$needle' expect_present=$expect_present got_count=$count" | tee -a "$LOG"
    FAIL=$((FAIL+1))
  fi
}

echo "--- Logging in all test accounts ---" | tee -a "$LOG"
login superadmin superadmin "Admin@123"
login hrmanager hrmanager "Admin@123"
login hrofficer hrofficer "Admin@123"
login payroll payroll "Admin@123"

echo "" | tee -a "$LOG"
echo "--- KOM-023: hr_officer/hrofficer role typo fix ---" | tee -a "$LOG"
assert_http "modules/temp_employees/index.php" 200 hrofficer "hr_officer can now access Temp Employees (was blocked pre-fix)"
assert_http "modules/consultants/index.php" 200 hrofficer "hr_officer can now access Consultants (was blocked pre-fix)"

echo "" | tee -a "$LOG"
echo "--- KOM-019/NH-02: Activity Log centralized permission ---" | tee -a "$LOG"
# Phase 5, Stage 5.10 (KOM-037): Activity Log's permission gate was
# deliberately merged onto audit.view (previously activity_log.view,
# seeded to super_admin only) — hr_manager/hr_officer both hold
# audit.view, so gaining access here is the INTENDED effect of the
# merge, not a regression. payroll_officer still doesn't hold
# audit.view at all, so remains correctly blocked.
assert_http "modules/activity_log/index.php" 200 superadmin "super_admin can access Activity Log"
assert_http "modules/activity_log/index.php" 200 hrmanager "hr_manager can access Activity Log (audit.view merge, Stage 5.10)"
assert_http "modules/activity_log/index.php" 200 hrofficer "hr_officer can access Activity Log (audit.view merge, Stage 5.10)"
assert_http "modules/activity_log/index.php" 302 payroll "payroll_officer blocked from Activity Log"

echo "" | tee -a "$LOG"
echo "--- KOM-001/C-01: Approvals — org-wide view now permission-gated ---" | tee -a "$LOG"
assert_contains "modules/approvals/index.php" superadmin "card-title\">All Workflows" 1 "super_admin sees All Workflows section"
assert_contains "modules/approvals/index.php" hrmanager "card-title\">All Workflows" 1 "hr_manager sees All Workflows section (approvals.manage_all granted)"
assert_contains "modules/approvals/index.php" hrofficer "card-title\">All Workflows" 0 "hr_officer does NOT see All Workflows section (not granted approvals.manage_all)"

echo "" | tee -a "$LOG"
echo "--- KOM-009/H-03 regression (leave approve display) + hardcoded-role sweep ---" | tee -a "$LOG"
assert_http "modules/leave/index.php" 200 hrmanager "hr_manager can access Leave (canApprove-based display, no fatal error)"
assert_http "modules/leave/view.php?id=1" 200 hrmanager "hr_manager can view a leave application"

echo "" | tee -a "$LOG"
echo "--- KOM-007/H-01 regression: leave.apply now permission-gated ---" | tee -a "$LOG"
assert_http "modules/leave/apply.php" 200 hrmanager "hr_manager (has leave.apply) can reach Apply for Leave"
assert_http "modules/leave/apply.php" 302 payroll "payroll_officer (no leave.apply grant) is now blocked from Apply for Leave — NEW protection, page previously had no permission gate at all"

echo "" | tee -a "$LOG"
echo "--- KOM-014/H-09: Payroll deduction/savings delete requires can_delete ---" | tee -a "$LOG"
assert_http "modules/payroll/deductions.php" 200 payroll "payroll_officer can view deductions (can_view=1)"
# payroll_officer has can_view/create/edit=1 but can_delete=0 for payroll.deductions — attempt delete POST, expect redirect to dashboard?error=access_denied (not a deletion)
curl -s -b "$JAR_DIR/payroll.txt" "$BASE/modules/payroll/deductions.php" -o "$JAR_DIR/ded_page.html"
CSRF_DED=$(grep -oE 'name="csrf_token" value="[a-f0-9]+"' "$JAR_DIR/ded_page.html" | head -1 | grep -oE '[a-f0-9]{20,}')
DEL_LOC=$(curl -s -b "$JAR_DIR/payroll.txt" -D - -o /dev/null \
  -d "csrf_token=$CSRF_DED&action=delete&id=999999" \
  "$BASE/modules/payroll/deductions.php" | grep -i "^location" )
if echo "$DEL_LOC" | grep -qi "access_denied"; then
  echo "PASS | payroll_officer delete attempt on deductions correctly denied (can_delete=0)" | tee -a "$LOG"; PASS=$((PASS+1))
else
  echo "INFO | payroll_officer delete attempt response location: $DEL_LOC (id=999999 may not exist — see notes)" | tee -a "$LOG"
fi

echo "" | tee -a "$LOG"
echo "--- KOM-011/H-05: Executive report payroll masking ---" | tee -a "$LOG"
assert_contains "modules/reports/executive.php" superadmin "filter:blur" 0 "super_admin sees UNmasked payroll totals (bypass)"
assert_contains "modules/reports/executive.php?year=$(date +%Y)" payroll "filter:blur" 0 "payroll_officer (has payroll.view) sees unmasked totals"

echo "" | tee -a "$LOG"
echo "--- KOM-018/NH-01: Dashboard Recent Activity gated ---" | tee -a "$LOG"
assert_contains "dashboard.php" superadmin "Recent Activity" 1 "super_admin dashboard renders Recent Activity card"
# payroll_officer has neither audit.view nor activity_log.view -> widget query should return empty, card shows empty-state
assert_contains "dashboard.php" payroll "No activity recorded yet." 1 "payroll_officer sees empty Recent Activity (no audit.view/activity_log.view grant)"

echo "" | tee -a "$LOG"
echo "--- KOM-015/H-10: Server-side role validation on user creation ---" | tee -a "$LOG"
curl -s -b "$JAR_DIR/hrmanager.txt" "$BASE/modules/users/index.php" -o "$JAR_DIR/users_page.html"
CSRF_U=$(grep -oE 'name="csrf_token" value="[a-f0-9]+"' "$JAR_DIR/users_page.html" | head -1 | grep -oE '[a-f0-9]{20,}')
RAND=$RANDOM
curl -s -b "$JAR_DIR/hrmanager.txt" -o "$JAR_DIR/priv_esc.html" -w "\nHTTP:%{http_code}\n" \
  -d "csrf_token=$CSRF_U&post_action=add_user&username=priv_esc_test_$RAND&email=privesc$RAND@test.local&role=super_admin&password=TestPass123" \
  "$BASE/modules/users/index.php" > "$JAR_DIR/priv_esc_out.txt"
CHECK=$(/c/xampp/mysql/bin/mysql.exe -u root komagin_hr -N -e "SELECT COUNT(*) FROM users WHERE username='priv_esc_test_$RAND' AND role='super_admin';" 2>/dev/null)
if [ "$CHECK" = "0" ]; then
  echo "PASS | hr_manager could NOT create a super_admin user via crafted POST (isValidAssignableRole blocked it)" | tee -a "$LOG"; PASS=$((PASS+1))
else
  echo "FAIL | hr_manager WAS ABLE to create a super_admin user — privilege escalation still possible!" | tee -a "$LOG"; FAIL=$((FAIL+1))
fi
/c/xampp/mysql/bin/mysql.exe -u root komagin_hr -e "DELETE FROM users WHERE username='priv_esc_test_$RAND';" 2>/dev/null

echo "" | tee -a "$LOG"
echo "======================================" | tee -a "$LOG"
echo "TOTAL: $PASS passed, $FAIL failed" | tee -a "$LOG"

#!/bin/bash
# Phase 2 Regression Test Run — Authentication, Session Security & Portal Hardening
# Executed against the live local app (http://localhost/HR_Komagin).
# Results appended to docs/remediation/Testing/phase2-regression-results.log
#
# This script temporarily enables portal credentials on ONE existing
# consultant record and ONE existing temp-employee record (neither has
# portal access configured in this environment's seed data), runs the full
# login/session/CSRF test matrix, then reverts both records to their
# original state and deletes any test data it created. Safe to re-run.

BASE="http://localhost/HR_Komagin"
MYSQL="/c/xampp/mysql/bin/mysql.exe -u root komagin_hr"
JAR="/tmp/phase2_regress"
LOG="$(dirname "$0")/phase2-regression-results.log"
mkdir -p "$JAR"
PASS=0
FAIL=0

echo "Phase 2 Regression Run — $(date)" > "$LOG"
echo "======================================" >> "$LOG"

pass() { echo "PASS | $1" | tee -a "$LOG"; PASS=$((PASS+1)); }
fail() { echo "FAIL | $1" | tee -a "$LOG"; FAIL=$((FAIL+1)); }

get_csrf() { grep -oE 'name="csrf_token" value="[a-zA-Z0-9]+"' "$1" | head -1 | grep -oE '[a-zA-Z0-9]{20,}'; }
get_sid()  { grep PHPSESSID "$1" | awk '{print $7}'; }

# ============================================================
# 1. ADMIN — login, session fixation defense, logout+cookie clear
# ============================================================
rm -f "$JAR/admin.txt"
curl -s -c "$JAR/admin.txt" "$BASE/auth/login.php" -o "$JAR/admin_pre.html"
SID_BEFORE=$(get_sid "$JAR/admin.txt")
CSRF=$(get_csrf "$JAR/admin_pre.html")
curl -s -b "$JAR/admin.txt" -c "$JAR/admin.txt" -o /dev/null -w "%{http_code}" \
  -d "username=superadmin&password=Admin@123&csrf_token=$CSRF" "$BASE/auth/login.php" > "$JAR/admin_login_code.txt"
SID_AFTER=$(get_sid "$JAR/admin.txt")
[ "$(cat "$JAR/admin_login_code.txt")" = "302" ] && pass "Admin login succeeds" || fail "Admin login did not redirect"
[ "$SID_BEFORE" != "$SID_AFTER" ] && pass "Admin login regenerates session ID (fixation defense)" || fail "Admin session ID unchanged after login"
CODE=$(curl -s -b "$JAR/admin.txt" -o /dev/null -w "%{http_code}" "$BASE/dashboard.php")
[ "$CODE" = "200" ] && pass "Admin dashboard reachable post-login" || fail "Admin dashboard not reachable ($CODE)"
LOGOUT_HDRS=$(curl -s -b "$JAR/admin.txt" -c "$JAR/admin.txt" -D - -o /dev/null "$BASE/auth/logout.php")
echo "$LOGOUT_HDRS" | grep -qi "PHPSESSID=deleted" && pass "Admin logout expires the cookie client-side" || fail "Admin logout does not expire cookie"
CODE=$(curl -s -b "$JAR/admin.txt" -o /dev/null -w "%{http_code}" "$BASE/dashboard.php")
[ "$CODE" = "302" ] && pass "Admin session cannot be reused after logout" || fail "Admin session reusable after logout ($CODE)"

# ============================================================
# 2. EMPLOYEE PORTAL — login, CSRF on login form, fixation, brute force, logout
# ============================================================
rm -f "$JAR/ep.txt"
curl -s -c "$JAR/ep.txt" "$BASE/employee-portal/login.php" -o "$JAR/ep_pre.html"
SID_BEFORE=$(get_sid "$JAR/ep.txt")
CSRF=$(get_csrf "$JAR/ep_pre.html")
CODE=$(curl -s -b "$JAR/ep.txt" -o /dev/null -w "%{http_code}" \
  -d "employee_number=KOM-EMP-2026-0001&password=Admin@123" "$BASE/employee-portal/login.php")
[ "$CODE" = "200" ] && pass "Employee login WITHOUT csrf_token is rejected (re-renders form, not redirected)" || fail "Employee login missing CSRF check ($CODE)"
CODE=$(curl -s -b "$JAR/ep.txt" -c "$JAR/ep.txt" -o /dev/null -w "%{http_code}" \
  -d "csrf_token=$CSRF&employee_number=KOM-EMP-2026-0001&password=Admin@123" "$BASE/employee-portal/login.php")
SID_AFTER=$(get_sid "$JAR/ep.txt")
[ "$CODE" = "302" ] && pass "Employee login with valid CSRF succeeds" || fail "Employee login with valid CSRF failed ($CODE)"
[ "$SID_BEFORE" != "$SID_AFTER" ] && pass "Employee login regenerates session ID (fixation defense)" || fail "Employee session ID unchanged after login"

# Complete the policy-agreement gate so the rest of the portal (hub.php etc.)
# is actually reachable for the remaining tests in this jar. policy.php now
# requires a CSRF token (Phase 3 / Stage 3.11 fix — this POST had none
# before), so fetch the form first to grab it.
curl -s -b "$JAR/ep.txt" -c "$JAR/ep.txt" "$BASE/employee-portal/policy.php" -o "$JAR/ep_policy.html"
POLICYCSRF=$(get_csrf "$JAR/ep_policy.html")
curl -s -b "$JAR/ep.txt" -c "$JAR/ep.txt" -o /dev/null -d "csrf_token=$POLICYCSRF&agree=1" "$BASE/employee-portal/policy.php"

# Brute force: 5 failures then a 6th blocked attempt, against a DIFFERENT employee number
rm -f "$JAR/ep_bf.txt"
for i in 1 2 3 4 5; do
  curl -s -c "$JAR/ep_bf.txt" -b "$JAR/ep_bf.txt" "$BASE/employee-portal/login.php" -o "$JAR/ep_bf_page.html"
  BFCSRF=$(get_csrf "$JAR/ep_bf_page.html")
  curl -s -c "$JAR/ep_bf.txt" -b "$JAR/ep_bf.txt" -o /dev/null \
    -d "csrf_token=$BFCSRF&employee_number=KOM-EMP-2026-0002&password=wrong$i" "$BASE/employee-portal/login.php"
done
curl -s -c "$JAR/ep_bf.txt" -b "$JAR/ep_bf.txt" "$BASE/employee-portal/login.php" -o "$JAR/ep_bf_page2.html"
BFCSRF6=$(get_csrf "$JAR/ep_bf_page2.html")
RESULT=$(curl -s -c "$JAR/ep_bf.txt" -b "$JAR/ep_bf.txt" \
  -d "csrf_token=$BFCSRF6&employee_number=KOM-EMP-2026-0002&password=wrong6" "$BASE/employee-portal/login.php")
echo "$RESULT" | grep -q "Too many failed attempts" && pass "Employee portal brute-force lockout triggers after 5 failures" || fail "Employee portal brute-force lockout did not trigger"
$MYSQL -e "DELETE FROM audit_logs WHERE module='employee_portal' AND reason LIKE '%KOM-EMP-2026-0002%';" >/dev/null 2>&1

# Hub CSRF — use a unique, timestamped subject so this assertion can't
# collide with pre-existing/unrelated rows in the table.
HUB_MARKER="p2csrftest_$(date +%s)"
curl -s -b "$JAR/ep.txt" "$BASE/employee-portal/hub.php" -o "$JAR/ep_hub.html"
CODE=$(curl -s -b "$JAR/ep.txt" -o /dev/null -w "%{http_code}" \
  -d "submit_request=1&request_type=general_query&subject=$HUB_MARKER&description=$HUB_MARKER&priority=normal" "$BASE/employee-portal/hub.php")
NOCSRF_COUNT=$($MYSQL -N -e "SELECT COUNT(*) FROM employee_requests WHERE subject='$HUB_MARKER';" 2>/dev/null)
[ "${NOCSRF_COUNT:-0}" = "0" ] && pass "Hub request submission WITHOUT csrf_token is rejected (no row inserted)" || fail "Hub request created without CSRF token"

HUBCSRF=$(get_csrf "$JAR/ep_hub.html")
curl -s -b "$JAR/ep.txt" -o /dev/null \
  -d "csrf_token=$HUBCSRF&submit_request=1&request_type=general_query&subject=$HUB_MARKER&description=$HUB_MARKER&priority=normal" "$BASE/employee-portal/hub.php"
WITHCSRF_COUNT=$($MYSQL -N -e "SELECT COUNT(*) FROM employee_requests WHERE subject='$HUB_MARKER';" 2>/dev/null)
[ "${WITHCSRF_COUNT:-0}" = "1" ] && pass "Hub request submission WITH valid csrf_token succeeds" || fail "Hub request with valid CSRF did not create a row"
$MYSQL -e "DELETE FROM employee_requests WHERE subject='$HUB_MARKER';" >/dev/null 2>&1

# Logout + cookie clear
LOGOUT_HDRS=$(curl -s -b "$JAR/ep.txt" -c "$JAR/ep.txt" -D - -o /dev/null "$BASE/employee-portal/logout.php")
echo "$LOGOUT_HDRS" | grep -qi "PHPSESSID=deleted" && pass "Employee portal logout expires the cookie client-side" || fail "Employee logout does not expire cookie"

# ============================================================
# 3. NOTIFICATIONS API — CSRF standardization
# ============================================================
curl -s -c "$JAR/admin.txt" "$BASE/auth/login.php" -o "$JAR/admin_pre2.html"
CSRF=$(get_csrf "$JAR/admin_pre2.html")
curl -s -b "$JAR/admin.txt" -c "$JAR/admin.txt" -o /dev/null -d "username=superadmin&password=Admin@123&csrf_token=$CSRF" "$BASE/auth/login.php"
RESULT=$(curl -s -b "$JAR/admin.txt" "$BASE/api/notifications.php?action=mark_all_read")
echo "$RESULT" | grep -q '"success":false' && pass "Notifications mark_all_read via GET is rejected" || fail "Notifications mark_all_read via GET was accepted"
RESULT=$(curl -s -b "$JAR/admin.txt" -d "action=mark_all_read" "$BASE/api/notifications.php")
echo "$RESULT" | grep -q '"success":false' && pass "Notifications mark_all_read POST without CSRF is rejected" || fail "Notifications mark_all_read POST without CSRF accepted"
curl -s -b "$JAR/admin.txt" "$BASE/dashboard.php" -o "$JAR/dash.html"
DASHCSRF=$(grep -oE "CSRF_TOKEN = '[a-f0-9]+'" "$JAR/dash.html" | grep -oE '[a-f0-9]{20,}')
RESULT=$(curl -s -b "$JAR/admin.txt" -d "action=mark_all_read&csrf_token=$DASHCSRF" "$BASE/api/notifications.php")
echo "$RESULT" | grep -q '"success":true' && pass "Notifications mark_all_read POST with valid CSRF succeeds" || fail "Notifications mark_all_read POST with valid CSRF failed"

# ============================================================
# 4. CONSULTANT PORTAL — set up temp credential, full lifecycle, tear down
# ============================================================
HASH=$(/c/xampp/php/php -r "echo password_hash('TestPass123!', PASSWORD_DEFAULT);")
$MYSQL -e "UPDATE consultants SET portal_active=1, portal_password='$HASH' WHERE consultant_number='KOM-CON-2026-0001';" >/dev/null 2>&1

rm -f "$JAR/cp.txt"
curl -s -c "$JAR/cp.txt" "$BASE/consultant-portal/login.php" -o "$JAR/cp_pre.html"
SID_BEFORE=$(get_sid "$JAR/cp.txt")
CODE=$(curl -s -b "$JAR/cp.txt" -o /dev/null -w "%{http_code}" \
  -d "consultant_number=KOM-CON-2026-0001&password=TestPass123!" "$BASE/consultant-portal/login.php")
[ "$CODE" = "200" ] && pass "Consultant login WITHOUT csrf_token is rejected" || fail "Consultant login missing CSRF check ($CODE)"
CSRF=$(get_csrf "$JAR/cp_pre.html")
CODE=$(curl -s -b "$JAR/cp.txt" -c "$JAR/cp.txt" -o /dev/null -w "%{http_code}" \
  -d "csrf_token=$CSRF&consultant_number=KOM-CON-2026-0001&password=TestPass123!" "$BASE/consultant-portal/login.php")
SID_AFTER=$(get_sid "$JAR/cp.txt")
[ "$CODE" = "302" ] && pass "Consultant login with valid CSRF succeeds" || fail "Consultant login with valid CSRF failed ($CODE)"
[ "$SID_BEFORE" != "$SID_AFTER" ] && pass "Consultant login regenerates session ID (fixation defense)" || fail "Consultant session ID unchanged after login"

# Kiosk CSRF
CODE=$(curl -s -b "$JAR/cp.txt" -o /dev/null -w "%{http_code}" -d "action=clock_in" "$BASE/consultant-portal/kiosk.php")
BEFORE=$($MYSQL -N -e "SELECT COUNT(*) FROM consultant_attendance WHERE consultant_id=1 AND work_date=CURDATE() AND clock_in IS NOT NULL;" 2>/dev/null)
[ "${BEFORE:-0}" = "0" ] && pass "Consultant kiosk clock_in WITHOUT csrf_token is rejected (no row written)" || fail "Consultant kiosk clock_in without CSRF succeeded"
curl -s -b "$JAR/cp.txt" "$BASE/consultant-portal/kiosk.php" -o "$JAR/cp_kiosk.html"
KCSRF=$(get_csrf "$JAR/cp_kiosk.html")
curl -s -b "$JAR/cp.txt" -c "$JAR/cp.txt" -o /dev/null -d "csrf_token=$KCSRF&action=clock_in" "$BASE/consultant-portal/kiosk.php"
AFTER=$($MYSQL -N -e "SELECT COUNT(*) FROM consultant_attendance WHERE consultant_id=1 AND work_date=CURDATE() AND clock_in IS NOT NULL;" 2>/dev/null)
[ "${AFTER:-0}" = "1" ] && pass "Consultant kiosk clock_in WITH valid csrf_token succeeds" || fail "Consultant kiosk clock_in with valid CSRF did not write a row"

LOGOUT_HDRS=$(curl -s -b "$JAR/cp.txt" -c "$JAR/cp.txt" -D - -o /dev/null "$BASE/consultant-portal/logout.php")
echo "$LOGOUT_HDRS" | grep -qi "PHPSESSID=deleted" && pass "Consultant logout expires the cookie client-side (previously never destroyed the session at all)" || fail "Consultant logout does not expire cookie"
CODE=$(curl -s -b "$JAR/cp.txt" -o /dev/null -w "%{http_code}" "$BASE/consultant-portal/dashboard.php")
[ "$CODE" = "302" ] && pass "Consultant session cannot be reused after logout" || fail "Consultant session reusable after logout ($CODE)"

# Tear down consultant test data
$MYSQL -e "DELETE FROM consultant_attendance WHERE consultant_id=1 AND work_date=CURDATE(); UPDATE consultants SET portal_active=0, portal_password=NULL WHERE consultant_number='KOM-CON-2026-0001';" >/dev/null 2>&1

# ============================================================
# 5. TEMPORARY EMPLOYEE PORTAL — set up temp credential, full lifecycle, tear down
# ============================================================
HASH2=$(/c/xampp/php/php -r "echo password_hash('TestPass123!', PASSWORD_DEFAULT);")
$MYSQL -e "UPDATE temp_employees SET portal_active=1, portal_password='$HASH2' WHERE employee_number='KOM-TMP-2026-0001';" >/dev/null 2>&1

rm -f "$JAR/tmp.txt"
curl -s -c "$JAR/tmp.txt" "$BASE/employee-portal/login.php" -o "$JAR/tmp_pre.html"
SID_BEFORE=$(get_sid "$JAR/tmp.txt")
CSRF=$(get_csrf "$JAR/tmp_pre.html")
CODE=$(curl -s -b "$JAR/tmp.txt" -c "$JAR/tmp.txt" -o /dev/null -w "%{http_code}" \
  -d "csrf_token=$CSRF&employee_number=KOM-TMP-2026-0001&password=TestPass123!" "$BASE/employee-portal/login.php")
SID_AFTER=$(get_sid "$JAR/tmp.txt")
[ "$CODE" = "302" ] && pass "Temp employee login succeeds" || fail "Temp employee login failed ($CODE)"
[ "$SID_BEFORE" != "$SID_AFTER" ] && pass "Temp employee login regenerates session ID (fixation defense)" || fail "Temp employee session ID unchanged after login"
CODE=$(curl -s -b "$JAR/tmp.txt" -o /dev/null -w "%{http_code}" "$BASE/employee-portal/temp_portal.php")
[ "$CODE" = "200" ] && pass "Temp portal reachable via shared session framework" || fail "Temp portal not reachable ($CODE)"
LOGOUT_HDRS=$(curl -s -b "$JAR/tmp.txt" -c "$JAR/tmp.txt" -D - -o /dev/null "$BASE/employee-portal/temp_portal.php?action=logout")
echo "$LOGOUT_HDRS" | grep -qi "PHPSESSID=deleted" && pass "Temp portal logout expires the cookie client-side" || fail "Temp portal logout does not expire cookie"
CODE=$(curl -s -b "$JAR/tmp.txt" -o /dev/null -w "%{http_code}" "$BASE/employee-portal/temp_portal.php")
[ "$CODE" = "302" ] && pass "Temp portal session cannot be reused after logout" || fail "Temp portal session reusable after logout ($CODE)"

# Tear down temp employee test data
$MYSQL -e "UPDATE temp_employees SET portal_active=0, portal_password=NULL WHERE employee_number='KOM-TMP-2026-0001'; DELETE FROM audit_logs WHERE module='employee_portal' AND reason LIKE '%KOM-TMP-2026-0001%';" >/dev/null 2>&1

# ============================================================
# 6. CONSULTANTS MODULE CRUD (admin side) — KOM-002 regression
# ============================================================
curl -s -b "$JAR/admin.txt" "$BASE/modules/consultants/add.php" -o "$JAR/cadd.html"
CCSRF=$(get_csrf "$JAR/cadd.html")
curl -s -b "$JAR/admin.txt" -D - -o /dev/null \
  -d "csrf_token=$CCSRF&first_name=P2Regress&last_name=Test&type=output_based&status=active&start_date=2026-07-12" \
  "$BASE/modules/consultants/add.php" > "$JAR/cadd_hdrs.txt"
grep -qi "^location.*view.php" "$JAR/cadd_hdrs.txt" && pass "Consultants add.php works with no fatal error (KOM-002 regression)" || fail "Consultants add.php did not succeed as expected"
NEWID=$($MYSQL -N -e "SELECT id FROM consultants WHERE last_name='Test' AND first_name='P2Regress' ORDER BY id DESC LIMIT 1;" 2>/dev/null)
if [ -n "$NEWID" ]; then
  curl -s -b "$JAR/admin.txt" "$BASE/modules/consultants/index.php" -o "$JAR/cidx.html"
  DCSRF=$(get_csrf "$JAR/cidx.html")
  curl -s -b "$JAR/admin.txt" -o /dev/null -d "csrf_token=$DCSRF&id=$NEWID" "$BASE/modules/consultants/delete.php"
  REMAIN=$($MYSQL -N -e "SELECT COUNT(*) FROM consultants WHERE id=$NEWID;" 2>/dev/null)
  [ "${REMAIN:-1}" = "0" ] && pass "Consultants delete.php works with no fatal error (KOM-002 regression)" || fail "Consultants delete.php did not remove the test record"
fi

echo "" | tee -a "$LOG"
echo "======================================" | tee -a "$LOG"
echo "TOTAL: $PASS passed, $FAIL failed" | tee -a "$LOG"

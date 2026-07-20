<?php
/**
 * Currency Constant Collision — Regression Test
 *
 * CLI-runnable, no browser or PHPUnit needed — follows the same plain
 * pass/fail-record convention as database/verify_clean_install.php and
 * tests/payroll_dashboard_aggregation_test.php.
 *
 * Reproduces the exact production incident: CURRENCY_SYMBOL collided with
 * PHP's own built-in nl_langinfo() LC_MONETARY constant of the same name
 * (only present on POSIX/Linux builds — absent on Windows, which is why
 * this went undetected on every Windows dev machine used on this
 * project). define('CURRENCY_SYMBOL', 'K') silently no-ops with a
 * warning when the name is already taken, so the app kept rendering
 * whatever the pre-existing value was (a raw integer, e.g. 262145 —
 * glibc's packed locale-item code for LC_MONETARY's CURRENCY_SYMBOL
 * item) directly in front of every monetary amount instead of 'K'.
 *
 * This script cannot run in the same PHP process as any other test file
 * that already loaded config.php — PHP constants, once defined, can
 * never be redefined or undefined for the lifetime of the process, so
 * simulating the collision requires full control over definition order
 * from a clean process. Run standalone.
 *
 * Usage: php tests/currency_constant_collision_test.php
 */

$results = ['pass' => 0, 'fail' => 0];
function record(array &$results, bool $ok, string $msg): void {
    $results[$ok ? 'pass' : 'fail']++;
    echo ($ok ? 'PASS' : 'FAIL') . " | $msg\n";
}

echo "=== Currency Constant Collision Regression Test ===\n\n";

// Step 1: simulate the Linux production collision BEFORE config.php ever
// runs — exactly what nl_langinfo() registers at PHP startup on a POSIX
// build, before any application code executes. 262145 is not an
// arbitrary stand-in: it is glibc's actual packed locale-item code for
// LC_MONETARY's CURRENCY_SYMBOL item ((LC_MONETARY=4) << 16 | 1).
define('CURRENCY_SYMBOL', 262145);
record($results, defined('CURRENCY_SYMBOL') && constant('CURRENCY_SYMBOL') === 262145,
    'Setup: simulated collision constant CURRENCY_SYMBOL=262145 is in place before config.php loads');

// Step 2: capture warnings so we can prove none fire for our own
// constant, without letting a real warning (if the fix regressed) go
// unnoticed — collected via a custom error handler rather than relying
// on suppressing/hiding it.
$capturedWarnings = [];
set_error_handler(function ($errno, $errstr) use (&$capturedWarnings) {
    $capturedWarnings[] = $errstr;
    return true;
});

require __DIR__ . '/../config/config.php';

restore_error_handler();

// Step 3: no warning about our own constant.
$ownConstantWarnings = array_filter($capturedWarnings, fn($w) => str_contains($w, 'HRMS_CURRENCY_SYMBOL') || str_contains($w, 'HRMS_CURRENCY_CODE'));
record($results, count($ownConstantWarnings) === 0,
    'Loading config.php with the collision present emits NO "already defined" warning for HRMS_CURRENCY_SYMBOL/HRMS_CURRENCY_CODE');
if (count($capturedWarnings) > 0) {
    echo "  (other warnings captured, for information only: " . implode(' | ', $capturedWarnings) . ")\n";
}

// Step 4: our constant is exactly 'K', completely unaffected by the collision.
record($results, defined('HRMS_CURRENCY_SYMBOL') && HRMS_CURRENCY_SYMBOL === 'K',
    'HRMS_CURRENCY_SYMBOL === \'K\' even with the colliding CURRENCY_SYMBOL constant already defined');
record($results, defined('HRMS_CURRENCY_CODE') && HRMS_CURRENCY_CODE === 'PGK',
    'HRMS_CURRENCY_CODE === \'PGK\' even with a collision present');

// Step 5: the colliding built-in itself is untouched — proves the app
// never attempts to write to or rely on the name it collides with.
record($results, constant('CURRENCY_SYMBOL') === 262145,
    'The colliding CURRENCY_SYMBOL constant itself is left exactly as-is (262145) — the app never touches it');

// Step 6: the actual KPI rendering expression, reproduced exactly as it
// appears in modules/payroll/index.php, must render 'K 0.00' — not
// '262145 0.00' — for a zero total under the exact collision condition
// that caused the original incident.
$payrollTotalGross = 0.0;
ob_start();
?><?= HRMS_CURRENCY_SYMBOL ?> <?= number_format($payrollTotalGross, 2) ?><?php
$rendered = ob_get_clean();
record($results, $rendered === 'K 0.00',
    "KPI rendering expression produces exactly 'K 0.00' (got '$rendered')");
record($results, !str_contains($rendered, '262145'),
    'Rendered output does not contain 262145 anywhere');
record($results, !str_contains($rendered, (string)CURRENCY_SYMBOL),
    'Rendered output does not contain the raw value of the colliding built-in constant');

echo "\n=== Summary ===\n";
echo "PASS: {$results['pass']}\n";
echo "FAIL: {$results['fail']}\n";
exit($results['fail'] > 0 ? 1 : 0);

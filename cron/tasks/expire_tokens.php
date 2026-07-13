<?php
/**
 * Marks employee_update_links and password_reset_tokens past their
 * expiry as unusable.
 *
 * self-service/update.php and auth/reset_password.php both already
 * filter `expires_at > NOW()` (and, for reset tokens, `used_at IS
 * NULL`) at the point of use, so an expired/used token can never
 * actually be used even without this task — but is_active/used_at
 * would otherwise stay in a state that misleads any future admin-facing
 * "active links/tokens" listing that queries those columns directly
 * rather than re-deriving expiry itself. Idempotent: running this
 * twice in a row processes 0 rows the second time.
 */
$itemsProcessed = 0;

$stmt = db()->prepare("UPDATE employee_update_links SET is_active = 0 WHERE is_active = 1 AND expires_at < NOW()");
$stmt->execute();
$itemsProcessed += $stmt->rowCount();

$stmt2 = db()->prepare("UPDATE password_reset_tokens SET used_at = NOW() WHERE used_at IS NULL AND expires_at < NOW()");
$stmt2->execute();
$itemsProcessed += $stmt2->rowCount();

return $itemsProcessed;

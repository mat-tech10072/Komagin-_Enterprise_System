<?php
/**
 * Marks employee_update_links past their expiry as inactive.
 *
 * self-service/update.php already filters `expires_at > NOW()` at the
 * point of use, so an expired link can never actually be used even
 * without this task — but is_active still reads 1 on those rows until
 * something sets it, which would mislead any future admin-facing "active
 * links" listing that queries is_active directly rather than re-deriving
 * expiry itself. Idempotent: running this twice in a row processes 0
 * rows the second time.
 */
$stmt = db()->prepare("UPDATE employee_update_links SET is_active = 0 WHERE is_active = 1 AND expires_at < NOW()");
$stmt->execute();
return $stmt->rowCount();

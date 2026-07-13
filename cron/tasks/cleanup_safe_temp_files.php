<?php
/**
 * Removes long-expired, no-longer-usable self-service link rows —
 * single-use magic links are conceptually disposable/temporary
 * artifacts once they can never be used again (inactive, revoked, or
 * expired) AND have sat that way for a long retention window (180
 * days — well past any legitimate follow-up need). This is the only
 * genuinely disposable, non-evidentiary data this application
 * generates; deliberately does not touch audit_logs or any other
 * record with compliance/evidentiary value — those are never
 * auto-deleted by this or any other scheduled task.
 */
$stmt = db()->prepare("DELETE FROM employee_update_links
    WHERE (is_active = 0 OR is_revoked = 1 OR expires_at < NOW())
    AND created_at < DATE_SUB(NOW(), INTERVAL 180 DAY)");
$stmt->execute();
$itemsProcessed = $stmt->rowCount();

// Phase 5, Stage 5.6: reminder_notifications_log is a pure per-day dedup
// marker (send_reminders.php) with no evidentiary/audit value of its own
// — safe to prune well past any reminder's relevance window.
$stmt2 = db()->prepare("DELETE FROM reminder_notifications_log WHERE reminder_date < DATE_SUB(CURDATE(), INTERVAL 90 DAY)");
$stmt2->execute();
$itemsProcessed += $stmt2->rowCount();

return $itemsProcessed;

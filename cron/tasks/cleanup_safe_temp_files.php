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
return $stmt->rowCount();

<?php
/**
 * Komagin HR — Approval Workflow Engine
 * Creates, advances, and resolves approval workflows
 * across leave, payroll, documents, and HR actions.
 *
 * act() is the authorization boundary for every approval/rejection in the
 * system. It does not trust its caller: it independently re-derives whether
 * the acting user is allowed to act on the current stage before writing
 * anything, and it audits every attempt — allowed or blocked.
 */

/** Thrown by ApprovalEngine::act() when the caller is not authorized to
 *  act on a workflow's current stage. Callers must catch this explicitly —
 *  there is no boolean "did it work?" return left to silently ignore. */
class ApprovalAuthorizationException extends \RuntimeException {}

class ApprovalEngine
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // ── Workflow type configurations ───────────────────────────────────────
    public static function workflowConfig(): array
    {
        return [
            'leave' => [
                'label'  => 'Leave Application',
                'stages' => [
                    ['name'=>'Supervisor Review','role'=>'supervisor'],
                    ['name'=>'HR Approval',      'role'=>'hr_manager'],
                ],
            ],
            'overtime' => [
                'label'  => 'Overtime Approval',
                'stages' => [
                    ['name'=>'HR Review', 'role'=>'hr_officer'],
                ],
            ],
            'correction' => [
                'label'  => 'Timesheet Correction',
                'stages' => [
                    ['name'=>'HR Approval', 'role'=>'hr_officer'],
                ],
            ],
            'payroll_run' => [
                'label'  => 'Payroll Run Authorization',
                'stages' => [
                    ['name'=>'Payroll Officer Review', 'role'=>'payroll_officer'],
                    ['name'=>'Payroll Manager Approval','role'=>'payroll_manager'],
                ],
            ],
            'promotion' => [
                'label'  => 'Promotion Request',
                'stages' => [
                    ['name'=>'HR Manager Review', 'role'=>'hr_manager'],
                ],
            ],
            'transfer' => [
                'label'  => 'Transfer Request',
                'stages' => [
                    ['name'=>'HR Manager Review', 'role'=>'hr_manager'],
                ],
            ],
            'termination' => [
                'label'  => 'Termination Request',
                'stages' => [
                    ['name'=>'HR Manager Review',  'role'=>'hr_manager'],
                ],
            ],
            'document' => [
                'label'  => 'Document Approval',
                'stages' => [
                    ['name'=>'HR Review', 'role'=>'hr_officer'],
                ],
            ],
        ];
    }

    // ── Create a new workflow ──────────────────────────────────────────────
    public function create(
        string $type,
        int    $referenceId,
        string $referenceTable,
        string $title,
        int    $initiatedBy,
        ?int   $employeeId = null,
        string $priority   = 'normal',
        ?string $dueDate   = null,
        ?string $notes     = null
    ): int {
        $config = self::workflowConfig()[$type] ?? null;
        if (!$config) throw new \InvalidArgumentException("Unknown workflow type: $type");

        $totalStages = count($config['stages']);

        $this->db->prepare("INSERT INTO approval_workflows
            (workflow_type, reference_id, reference_table, title, initiated_by, employee_id,
             status, current_stage, total_stages, priority, due_date, notes)
            VALUES (?,?,?,?,?,?,'pending',1,?,?,?,?)")
            ->execute([$type,$referenceId,$referenceTable,$title,$initiatedBy,$employeeId,
                       $totalStages,$priority,$dueDate,$notes]);

        $workflowId = (int)$this->db->lastInsertId();

        // Create stage records
        foreach ($config['stages'] as $i => $stage) {
            $stageNum = $i + 1;
            $this->db->prepare("INSERT INTO approval_stages (workflow_id, stage_number, stage_name, approver_role, status)
                VALUES (?,?,?,?,?)")
                ->execute([$workflowId, $stageNum, $stage['name'], $stage['role'],
                           $stageNum === 1 ? 'pending' : 'pending']);
        }

        return $workflowId;
    }

    // ── Advance a stage (approve or reject) ───────────────────────────────
    //
    // Every precondition below is enforced here, inside the engine, not left
    // to the calling page. A caller that got any of this wrong (wrong role,
    // stale stage, workflow already closed, approving its own request) is
    // rejected with a typed exception and the attempt is audited either way.
    public function act(
        int    $workflowId,
        int    $actingUserId,
        string $actingUserRole,
        string $action,     // 'approve' or 'reject'
        string $comments    = ''
    ): bool {
        if (!in_array($action, ['approve', 'reject'], true)) {
            throw new \InvalidArgumentException("Invalid approval action: $action");
        }

        $workflow = $this->getWorkflow($workflowId);
        if (!$workflow) {
            $this->auditAttempt($workflowId, $actingUserId, $action, false, 'workflow_not_found');
            throw new ApprovalAuthorizationException('Workflow not found.');
        }
        if (!in_array($workflow['status'], ['pending', 'in_review'], true)) {
            $this->auditAttempt($workflowId, $actingUserId, $action, false, "workflow_not_actionable:{$workflow['status']}");
            throw new ApprovalAuthorizationException('This workflow is no longer awaiting action (current status: ' . $workflow['status'] . ').');
        }

        $stage = $this->getCurrentStage($workflowId, $workflow['current_stage']);
        if (!$stage) {
            $this->auditAttempt($workflowId, $actingUserId, $action, false, 'current_stage_not_found');
            throw new ApprovalAuthorizationException('Current approval stage could not be located.');
        }
        // Duplicate-approval prevention: this exact stage may only be acted on once.
        if ($stage['status'] !== 'pending') {
            $this->auditAttempt($workflowId, $actingUserId, $action, false, "stage_already_actioned:{$stage['status']}");
            throw new ApprovalAuthorizationException('This approval stage has already been actioned.');
        }
        // Separation of duties: the initiator of a workflow may never be the one who resolves it,
        // regardless of what role they hold — this is a control on the person, not a permission.
        if ((int)$workflow['initiated_by'] === $actingUserId) {
            $this->auditAttempt($workflowId, $actingUserId, $action, false, 'self_approval_blocked');
            throw new ApprovalAuthorizationException('You cannot approve or reject a workflow you initiated yourself.');
        }
        // Correct approver: either the stage's designated role, or a specific assigned
        // approver_user_id. super_admin is deliberately NOT exempted from this check —
        // approval authority is a separation-of-duties control, not a feature permission,
        // so bypassing it defeats the point of having stages at all.
        $roleMatches = ($stage['approver_role'] !== null && $stage['approver_role'] === $actingUserRole);
        $userMatches = ($stage['approver_user_id'] !== null && (int)$stage['approver_user_id'] === $actingUserId);
        if (!$roleMatches && !$userMatches) {
            $this->auditAttempt($workflowId, $actingUserId, $action, false,
                "wrong_approver_role:have={$actingUserRole};need={$stage['approver_role']}");
            throw new ApprovalAuthorizationException('You are not the assigned approver for this stage.');
        }

        // All preconditions satisfied — mark the stage.
        $this->db->prepare("UPDATE approval_stages SET status=?, action=?, approver_user_id=?, comments=?, acted_at=NOW() WHERE id=?")
            ->execute([$action === 'approve' ? 'approved' : 'rejected', $action, $actingUserId, $comments, $stage['id']]);

        if ($action === 'reject') {
            $this->db->prepare("UPDATE approval_workflows SET status='rejected', updated_at=NOW() WHERE id=?")
                ->execute([$workflowId]);
            $this->updateReference($workflow, 'rejected');
            $this->notifyInitiator($workflow, 'rejected', $comments);
            $this->auditAttempt($workflowId, $actingUserId, $action, true, 'rejected');
            return true;
        }

        // Approved — advance to next stage or complete
        $nextStage = $workflow['current_stage'] + 1;
        if ($nextStage > $workflow['total_stages']) {
            $this->db->prepare("UPDATE approval_workflows SET status='approved', updated_at=NOW() WHERE id=?")
                ->execute([$workflowId]);
            $this->updateReference($workflow, 'approved', $actingUserId);
            $this->notifyInitiator($workflow, 'approved', $comments);
        } else {
            $this->db->prepare("UPDATE approval_workflows SET status='in_review', current_stage=?, updated_at=NOW() WHERE id=?")
                ->execute([$nextStage, $workflowId]);
        }

        $this->auditAttempt($workflowId, $actingUserId, $action, true, "advanced_to_stage:$nextStage");
        return true;
    }

    // KOM-095: act() previously resolved the workflow (and, via
    // updateReference(), applied the real employee change) with no
    // notification to anyone at all — the HR staff member who submitted a
    // termination/transfer/promotion request had no way to learn the
    // outcome except by manually revisiting the Approvals page. leave's
    // own approve.php already notifies its applicant on decision; this
    // generalizes the same idea to every workflow type that flows through
    // this engine, notifying whoever initiated the request (not the
    // employee the workflow is about — for a sensitive action like
    // termination, that notification is a human conversation, not an
    // automated in-app popup).
    private function notifyInitiator(array $workflow, string $finalStatus, string $comments): void
    {
        if (empty($workflow['initiated_by'])) return;
        createNotification(
            (int)$workflow['initiated_by'],
            $finalStatus === 'approved' ? 'success' : 'danger',
            $workflow['title'] . ' — ' . ucfirst($finalStatus),
            'Your ' . str_replace('_', ' ', $workflow['workflow_type']) . ' request has been ' . $finalStatus . '.'
                . ($comments !== '' ? " Comments: {$comments}" : ''),
            APP_URL . '/modules/approvals/index.php'
        );
    }

    // ── Audit every act() attempt, allowed or blocked ──────────────────────
    private function auditAttempt(int $workflowId, int $actingUserId, string $action, bool $allowed, string $detail): void
    {
        $userName = $_SESSION['user_name'] ?? 'unknown';
        try {
            $this->db->prepare("INSERT INTO audit_logs (user_id, user_name, module, action, record_id, reason, ip_address, created_at)
                VALUES (?,?,?,?,?,?,?,NOW())")
                ->execute([
                    $actingUserId, $userName, 'approvals',
                    $allowed ? "{$action}_workflow" : 'approval_blocked',
                    $workflowId,
                    $allowed ? "action={$action};detail={$detail}" : "action={$action};blocked_reason={$detail}",
                    $_SERVER['REMOTE_ADDR'] ?? null,
                ]);
        } catch (\Exception $e) { /* non-fatal — never let audit logging break the approval flow */ }
    }

    // ── Cancel a workflow ──────────────────────────────────────────────────
    public function cancel(int $workflowId, string $reason = ''): void
    {
        $this->db->prepare("UPDATE approval_workflows SET status='cancelled', notes=CONCAT(IFNULL(notes,''),' | Cancelled: '+ ?) WHERE id=?")
            ->execute([$reason, $workflowId]);
    }

    // ── Get pending approvals for a user/role ─────────────────────────────
    public function getPendingForUser(int $userId, string $role): array
    {
        $stmt = $this->db->prepare("SELECT aw.*, a_s.stage_name, a_s.approver_role,
            CONCAT(e.first_name,' ',e.last_name) as employee_name, e.employee_number,
            u.username as initiated_by_name
            FROM approval_workflows aw
            JOIN approval_stages a_s ON a_s.workflow_id=aw.id AND a_s.stage_number=aw.current_stage
            LEFT JOIN employees e ON aw.employee_id=e.id
            LEFT JOIN users u ON aw.initiated_by=u.id
            WHERE aw.status IN ('pending','in_review')
              AND (a_s.approver_role=? OR a_s.approver_user_id=?)
            ORDER BY CASE aw.priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'normal' THEN 3 ELSE 4 END,
                     aw.created_at ASC");
        $stmt->execute([$role, $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── Get all workflows (for admin view) ────────────────────────────────
    public function getAll(array $filters = []): array
    {
        $where = ['1=1']; $params = [];
        if (!empty($filters['type']))   { $where[] = 'aw.workflow_type=?'; $params[] = $filters['type']; }
        if (!empty($filters['status'])) { $where[] = 'aw.status=?';       $params[] = $filters['status']; }
        $whereSQL = implode(' AND ', $where);

        $stmt = $this->db->prepare("SELECT aw.*,
            CONCAT(e.first_name,' ',e.last_name) as employee_name, e.employee_number,
            u.username as initiated_by_name
            FROM approval_workflows aw
            LEFT JOIN employees e ON aw.employee_id=e.id
            LEFT JOIN users u ON aw.initiated_by=u.id
            WHERE $whereSQL ORDER BY aw.created_at DESC LIMIT 200");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── Private helpers ───────────────────────────────────────────────────
    private function getWorkflow(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM approval_workflows WHERE id=?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function getCurrentStage(int $workflowId, int $stageNumber): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM approval_stages WHERE workflow_id=? AND stage_number=?");
        $stmt->execute([$workflowId, $stageNumber]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function updateReference(array $workflow, string $finalStatus, ?int $actingUserId = null): void
    {
        $table = $workflow['reference_table'];
        $id    = $workflow['reference_id'];
        $type  = $workflow['workflow_type'];

        // Map workflow type to the status column in the source table
        $columnMap = [
            'leave'      => 'hr_status',
            'overtime'   => 'status',
            'correction' => 'status',
            'payroll_run'=> 'status',
            'document'   => 'status',
        ];

        if (isset($columnMap[$type])) {
            $col = $columnMap[$type];
            try {
                $this->db->prepare("UPDATE `$table` SET `$col`=? WHERE id=?")
                    ->execute([$finalStatus, $id]);
            } catch (\Exception $e) {
                error_log("ApprovalEngine::updateReference failed: " . $e->getMessage());
            }
            return;
        }

        // 'termination'/'transfer'/'promotion' have no pre-existing "pending
        // request" row to flip a status column on (unlike leave, which
        // creates a leave_applications row up front) — the proposed change
        // is carried in workflow.notes as JSON and only actually applied to
        // `employees` here, on approval. Rejection intentionally leaves the
        // employee untouched.
        if ($finalStatus !== 'approved') return;
        if ($type === 'termination') { $this->applyApprovedTermination($workflow, $actingUserId); }
        elseif ($type === 'transfer')  { $this->applyApprovedTransferOrPromotion($workflow, $actingUserId, ['department_id','supervisor_id']); }
        elseif ($type === 'promotion') { $this->applyApprovedTransferOrPromotion($workflow, $actingUserId, ['position_id','basic_salary']); }
    }

    private function applyApprovedTransferOrPromotion(array $workflow, ?int $actingUserId, array $fields): void
    {
        $id      = (int)$workflow['reference_id'];
        $payload = json_decode($workflow['notes'] ?? '', true) ?: [];

        $setClauses = [];
        $params     = [];
        foreach ($fields as $f) {
            if (array_key_exists($f, $payload)) {
                $setClauses[] = "`$f`=?";
                $params[]     = $payload[$f];
            }
        }
        if (!$setClauses) return;
        $setClauses[] = 'updated_by=?';
        $params[]     = $actingUserId;
        $params[]     = $id;

        try {
            $this->db->prepare("UPDATE employees SET " . implode(', ', $setClauses) . ", updated_at=NOW() WHERE id=?")
                ->execute($params);
        } catch (\Exception $e) {
            error_log("ApprovalEngine::applyApprovedTransferOrPromotion failed: " . $e->getMessage());
        }
    }

    private function applyApprovedTermination(array $workflow, ?int $actingUserId): void
    {
        $id      = (int)$workflow['reference_id'];
        $payload = json_decode($workflow['notes'] ?? '', true) ?: [];
        $newStatus = $payload['new_status'] ?? 'terminated';
        $reason    = $payload['reason'] ?? 'Termination approved';
        $exitDate  = $payload['exit_date'] ?? null;

        try {
            $stmt = $this->db->prepare("SELECT status FROM employees WHERE id=?");
            $stmt->execute([$id]);
            $oldStatus = $stmt->fetchColumn();
            if ($oldStatus === false) return; // employee no longer exists

            $this->db->prepare("UPDATE employees SET status=?, status_reason=?, exit_date=?, updated_by=? WHERE id=?")
                ->execute([$newStatus, $reason, $exitDate, $actingUserId, $id]);

            $this->db->prepare("INSERT INTO employee_status_history (employee_id, old_status, new_status, reason, changed_by) VALUES (?,?,?,?,?)")
                ->execute([$id, $oldStatus, $newStatus, $reason, $actingUserId]);

            $this->db->prepare("UPDATE users SET is_active=0 WHERE employee_id=?")->execute([$id]);
        } catch (\Exception $e) {
            error_log("ApprovalEngine::applyApprovedTermination failed: " . $e->getMessage());
        }
    }
}

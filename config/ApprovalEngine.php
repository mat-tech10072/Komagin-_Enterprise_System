<?php
/**
 * Komagin HR — Approval Workflow Engine
 * Creates, advances, and resolves approval workflows
 * across leave, payroll, documents, and HR actions.
 */

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
    public function act(
        int    $workflowId,
        int    $actingUserId,
        string $action,     // 'approve' or 'reject'
        string $comments    = ''
    ): bool {
        $workflow = $this->getWorkflow($workflowId);
        if (!$workflow || !in_array($workflow['status'], ['pending','in_review'])) return false;

        $stage = $this->getCurrentStage($workflowId, $workflow['current_stage']);
        if (!$stage) return false;

        // Mark stage
        $this->db->prepare("UPDATE approval_stages SET status=?, action=?, approver_user_id=?, comments=?, acted_at=NOW() WHERE id=?")
            ->execute([$action === 'approve' ? 'approved' : 'rejected', $action, $actingUserId, $comments, $stage['id']]);

        if ($action === 'reject') {
            $this->db->prepare("UPDATE approval_workflows SET status='rejected', updated_at=NOW() WHERE id=?")
                ->execute([$workflowId]);
            $this->updateReference($workflow, 'rejected');
            return true;
        }

        // Approved — advance to next stage or complete
        $nextStage = $workflow['current_stage'] + 1;
        if ($nextStage > $workflow['total_stages']) {
            $this->db->prepare("UPDATE approval_workflows SET status='approved', updated_at=NOW() WHERE id=?")
                ->execute([$workflowId]);
            $this->updateReference($workflow, 'approved');
        } else {
            $this->db->prepare("UPDATE approval_workflows SET status='in_review', current_stage=?, updated_at=NOW() WHERE id=?")
                ->execute([$nextStage, $workflowId]);
        }

        return true;
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

    private function updateReference(array $workflow, string $finalStatus): void
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
        }
    }
}

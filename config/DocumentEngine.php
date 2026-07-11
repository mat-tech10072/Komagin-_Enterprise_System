<?php
/**
 * Komagin HR — Document Variable Engine
 * Resolves {{variable}} placeholders in document templates
 * using live data from the database.
 */

class DocumentEngine
{
    private PDO $db;
    private array $emp;
    private array $settings;

    public function __construct(PDO $db, array $employee, array $companySettings = [])
    {
        $this->db       = $db;
        $this->emp      = $employee;
        $this->settings = $companySettings;
    }

    // ── Resolve all variables in a template body ──────────────────────────
    public function render(string $html, array $extra = []): string
    {
        $vars = array_merge($this->buildVariables(), $extra);
        foreach ($vars as $key => $value) {
            // HTML variables (signature, stamp, letterhead, QR) must NOT be escaped
            $htmlVars = ['signature','stamp','letterhead','watermark','qr_code'];
            $isHtml = in_array($key, $htmlVars) || str_starts_with($key, 'signature.') || str_starts_with($key, 'stamp.');
            $rendered = $isHtml ? (string)$value : htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
            $html = str_replace('{{' . $key . '}}', $rendered, $html);
        }
        // Mark unresolved placeholders
        $html = preg_replace('/\{\{[a-zA-Z0-9_.]+\}\}/', '<span style="color:red;background:#ffe;">[missing variable]</span>', $html);
        return $html;
    }

    // ── Wrap rendered body with letterhead/watermark/doc layout ───────────
    public function wrapDocument(string $bodyHtml, array $tpl): string
    {
        $appUrl   = defined('APP_URL') ? APP_URL : '';
        $output   = '';
        $docNum   = '';
        $pageNum  = '';

        // Generate document number if requested
        if (!empty($tpl['show_doc_number'])) {
            try {
                $prefix  = $this->settings['doc_number_prefix'] ?? 'KHR';
                $counter = (int)($this->settings['doc_number_counter'] ?? 1);
                $docNum  = $prefix . '-' . date('Y') . '-' . str_pad($counter, 5, '0', STR_PAD_LEFT);
                // Increment counter
                $this->db->prepare("UPDATE company_settings SET doc_number_counter=doc_number_counter+1 WHERE id=1")->execute();
            } catch (Exception $e) {}
        }

        // QR Code (simple URL-based, no library required)
        $qrHtml = '';
        if (!empty($tpl['show_qr_code']) && $docNum) {
            $verifyUrl = $appUrl . '/verify-doc.php?ref=' . urlencode($docNum);
            $qrApiUrl  = 'https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=' . urlencode($verifyUrl);
            $qrHtml    = '<div style="text-align:right;margin-top:8px;"><img src="' . $qrApiUrl . '" width="70" height="70" alt="QR"><div style="font-size:8px;color:#666;margin-top:2px;">' . htmlspecialchars($docNum) . '</div></div>';
        }

        // Letterhead image
        $letterheadHtml = '';
        if (!empty($tpl['show_letterhead']) && !empty($tpl['letterhead_id'])) {
            try {
                $lh = $this->db->prepare("SELECT * FROM company_letterheads WHERE id=? AND is_active=1");
                $lh->execute([$tpl['letterhead_id']]);
                $lhRow = $lh->fetch(PDO::FETCH_ASSOC);
                if ($lhRow && !empty($lhRow['image_path'])) {
                    $letterheadHtml = '<div style="position:fixed;top:0;left:0;width:100%;z-index:-1;pointer-events:none;">'
                        . '<img src="' . $appUrl . '/' . htmlspecialchars($lhRow['image_path']) . '" style="width:100%;display:block;" alt="">'
                        . '</div>';
                }
            } catch (Exception $e) {}
        }

        // Watermark
        $watermarkHtml = '';
        if (!empty($tpl['show_watermark']) && !empty($tpl['watermark_id'])) {
            try {
                $wm = $this->db->prepare("SELECT * FROM company_watermarks WHERE id=? AND is_active=1");
                $wm->execute([$tpl['watermark_id']]);
                $wmRow = $wm->fetch(PDO::FETCH_ASSOC);
                if ($wmRow) {
                    if ($wmRow['type'] === 'text' && $wmRow['text']) {
                        $watermarkHtml = '<div style="position:fixed;top:50%;left:50%;transform:translate(-50%,-50%) rotate(' . (int)$wmRow['rotation'] . 'deg);z-index:0;pointer-events:none;opacity:' . (float)$wmRow['opacity'] . ';color:' . htmlspecialchars($wmRow['color']) . ';font-size:' . (int)$wmRow['font_size'] . 'px;font-weight:900;font-family:Arial,sans-serif;white-space:nowrap;">'
                            . htmlspecialchars($wmRow['text']) . '</div>';
                    } elseif (!empty($wmRow['image_path'])) {
                        $watermarkHtml = '<div style="position:fixed;top:50%;left:50%;transform:translate(-50%,-50%) rotate(' . (int)$wmRow['rotation'] . 'deg);z-index:0;pointer-events:none;opacity:' . (float)$wmRow['opacity'] . ';">'
                            . '<img src="' . $appUrl . '/' . htmlspecialchars($wmRow['image_path']) . '" style="max-width:400px;max-height:400px;">'
                            . '</div>';
                    }
                }
            } catch (Exception $e) {}
        }

        // Signatures
        $signaturesHtml = '';
        if (!empty($tpl['show_signature']) && !empty($tpl['signature_ids'])) {
            $sigIds = is_array($tpl['signature_ids']) ? $tpl['signature_ids'] : json_decode($tpl['signature_ids'], true);
            if (!empty($sigIds)) {
                try {
                    $in  = implode(',', array_map('intval', $sigIds));
                    $sigs = $this->db->query("SELECT * FROM company_signatures WHERE id IN ($in) AND is_active=1 ORDER BY approval_level")->fetchAll(PDO::FETCH_ASSOC);
                    if ($sigs) {
                        $signaturesHtml = '<div style="display:flex;gap:32px;flex-wrap:wrap;margin-top:40px;padding-top:16px;">';
                        foreach ($sigs as $sig) {
                            $signaturesHtml .= '<div style="text-align:center;min-width:140px;">';
                            $signaturesHtml .= '<img src="' . $appUrl . '/' . htmlspecialchars($sig['image_path']) . '" style="max-height:52px;max-width:140px;object-fit:contain;" alt="Signature">';
                            $signaturesHtml .= '<div style="border-top:1px solid #333;margin-top:4px;padding-top:4px;font-size:11px;font-weight:600;">' . htmlspecialchars($sig['signatory_name']) . '</div>';
                            if ($sig['designation']) $signaturesHtml .= '<div style="font-size:10px;color:#555;">' . htmlspecialchars($sig['designation']) . '</div>';
                            $signaturesHtml .= '</div>';
                        }
                        $signaturesHtml .= '</div>';
                    }
                } catch (Exception $e) {}
            }
        }

        // Stamp
        $stampHtml = '';
        if (!empty($tpl['show_stamp']) && !empty($tpl['stamp_id'])) {
            try {
                $st = $this->db->prepare("SELECT * FROM company_stamps WHERE id=? AND is_active=1");
                $st->execute([$tpl['stamp_id']]);
                $stRow = $st->fetch(PDO::FETCH_ASSOC);
                if ($stRow) {
                    $stampHtml = '<div style="position:absolute;bottom:80px;right:40px;opacity:0.85;">'
                        . '<img src="' . $appUrl . '/' . htmlspecialchars($stRow['image_path']) . '" style="max-width:90px;max-height:90px;object-fit:contain;" alt="Stamp">'
                        . '</div>';
                }
            } catch (Exception $e) {}
        }

        // Header with doc number + QR
        $headerExtras = '';
        if ($docNum || $qrHtml) {
            $headerExtras = '<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px;border-bottom:1px solid #e2e8f0;padding-bottom:8px;">'
                . ($docNum ? '<div style="font-size:11px;color:#666;font-family:monospace;">Ref: ' . htmlspecialchars($docNum) . '</div>' : '<div></div>')
                . $qrHtml
                . '</div>';
        }

        // Footer
        $footerHtml = '';
        if (!empty($tpl['show_footer'])) {
            $footerHtml = '<div style="border-top:1px solid #e2e8f0;margin-top:32px;padding-top:8px;font-size:10px;color:#888;text-align:center;display:flex;justify-content:space-between;">'
                . '<span>' . htmlspecialchars($this->settings['company_name'] ?? '') . '</span>'
                . '<span>Generated ' . date('d M Y H:i') . '</span>'
                . ($docNum ? '<span>' . htmlspecialchars($docNum) . '</span>' : '<span></span>')
                . '</div>';
        }

        return $letterheadHtml . $watermarkHtml
            . '<div style="position:relative;">'
            . $headerExtras
            . $bodyHtml
            . $signaturesHtml
            . $stampHtml
            . $footerHtml
            . '</div>';
    }

    // ── Build the full variable map ────────────────────────────────────────
    public function buildVariables(): array
    {
        $e = $this->emp;
        $s = $this->settings;

        $today       = date('d F Y');
        $todayShort  = date('d/m/Y');
        $month       = date('F Y');
        $year        = date('Y');

        // Supervisor name
        $supName = '';
        if (!empty($e['supervisor_id'])) {
            $st = $this->db->prepare("SELECT first_name, last_name FROM employees WHERE id=?");
            $st->execute([$e['supervisor_id']]);
            $sup = $st->fetch();
            if ($sup) $supName = $sup['first_name'] . ' ' . $sup['last_name'];
        }

        // Leave balance
        $leaveBalance = '';
        $lb = $this->db->prepare("SELECT SUM(remaining_days) as rem FROM leave_balances WHERE employee_id=? AND year=?");
        $lb->execute([$e['id'], $year]);
        $lbRow = $lb->fetch();
        if ($lbRow) $leaveBalance = number_format((float)$lbRow['rem'], 1) . ' days';

        // Latest payslip
        $grossSalary = '';
        $netSalary   = '';
        $ps = $this->db->prepare("SELECT gross_salary, net_salary, period_month, period_year FROM payslips WHERE employee_id=? ORDER BY period_year DESC, period_month DESC LIMIT 1");
        $ps->execute([$e['id']]);
        $payRow = $ps->fetch();
        if ($payRow) {
            $grossSalary = CURRENCY_SYMBOL . ' ' . number_format((float)$payRow['gross_salary'], 2);
            $netSalary   = CURRENCY_SYMBOL . ' ' . number_format((float)$payRow['net_salary'],   2);
        }

        return [
            // Company
            'company.name'         => $s['company_name']  ?? 'Komagin Limited',
            'company.address'      => $s['address']        ?? '',
            'company.phone'        => $s['phone']          ?? '',
            'company.email'        => $s['email']          ?? '',
            'company.website'      => $s['website']        ?? '',

            // Employee — Personal
            'employee.id'              => $e['id'],
            'employee.number'          => $e['employee_number'],
            'employee.first_name'      => $e['first_name'],
            'employee.last_name'       => $e['last_name'],
            'employee.full_name'       => $e['first_name'] . ' ' . $e['last_name'],
            'employee.preferred_name'  => $e['preferred_name'] ?: $e['first_name'],
            'employee.title'           => $e['gender'] === 'female' ? 'Ms.' : 'Mr.',
            'employee.national_id'     => $e['national_id'] ?? '',
            'employee.date_of_birth'   => !empty($e['date_of_birth']) ? date('d F Y', strtotime($e['date_of_birth'])) : '',
            'employee.gender'          => ucfirst($e['gender'] ?? ''),
            'employee.nationality'     => $e['nationality'] ?? '',
            'employee.email'           => $e['email'] ?? '',
            'employee.phone'           => $e['phone'] ?? '',
            'employee.address'         => $e['residential_address'] ?? '',

            // Employee — Employment
            'employee.department'      => $e['department_name'] ?? '',
            'employee.position'        => $e['position_title']  ?? '',
            'employee.type'            => ucfirst(str_replace('_', ' ', $e['employment_type'] ?? '')),
            'employee.status'          => ucfirst($e['status'] ?? ''),
            'employee.start_date'      => !empty($e['start_date']) ? date('d F Y', strtotime($e['start_date'])) : '',
            'employee.start_date_short'=> !empty($e['start_date']) ? date('d/m/Y', strtotime($e['start_date'])) : '',
            'employee.work_location'   => $e['work_location'] ?? '',
            'employee.supervisor'      => $supName,
            'employee.salary_gross'    => $grossSalary,
            'employee.salary_net'      => $netSalary,
            'employee.basic_salary'    => !empty($e['basic_salary']) ? CURRENCY_SYMBOL . ' ' . number_format((float)$e['basic_salary'], 2) : '',
            'employee.leave_balance'   => $leaveBalance,

            // Emergency
            'employee.emergency_name'  => $e['emergency_contact_name']     ?? '',
            'employee.emergency_phone' => $e['emergency_contact_phone']    ?? '',
            'employee.emergency_rel'   => $e['emergency_contact_relation'] ?? '',

            // Dates
            'date.today'               => $today,
            'date.today_short'         => $todayShort,
            'date.month'               => $month,
            'date.year'                => $year,

            // Dynamic branding assets (resolved as HTML — not escaped)
            'signature'     => $this->getSignatureHtml(),
            'stamp'         => $this->getStampHtml(),
            'letterhead'    => '', // handled via wrapDocument()
            'watermark'     => '', // handled via wrapDocument()
            'doc.number'    => '', // auto-generated in wrapDocument()
            'page.number'   => '1', // static — multi-page handled by browser print

            // Currency
            'currency.symbol' => defined('CURRENCY_SYMBOL') ? CURRENCY_SYMBOL : 'K',
            'currency.code'   => defined('CURRENCY_CODE')   ? CURRENCY_CODE   : 'PGK',
        ];
    }

    private function getSignatureHtml(): string {
        try {
            $row = $this->db->query("SELECT * FROM company_signatures WHERE is_active=1 ORDER BY approval_level LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            if (!$row) return '';
            $appUrl = defined('APP_URL') ? APP_URL : '';
            return '<div style="display:inline-block;text-align:center;min-width:140px;">'
                . '<img src="' . $appUrl . '/' . htmlspecialchars($row['image_path']) . '" style="max-height:48px;max-width:130px;object-fit:contain;" alt="Signature">'
                . '<div style="border-top:1px solid #333;padding-top:3px;font-size:11px;font-weight:600;">' . htmlspecialchars($row['signatory_name']) . '</div>'
                . ($row['designation'] ? '<div style="font-size:10px;color:#555;">' . htmlspecialchars($row['designation']) . '</div>' : '')
                . '</div>';
        } catch (Exception $e) { return ''; }
    }

    private function getStampHtml(): string {
        try {
            $row = $this->db->query("SELECT * FROM company_stamps WHERE is_active=1 ORDER BY id LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            if (!$row) return '';
            $appUrl = defined('APP_URL') ? APP_URL : '';
            return '<img src="' . $appUrl . '/' . htmlspecialchars($row['image_path']) . '" style="max-width:80px;max-height:80px;object-fit:contain;opacity:0.85;" alt="Stamp">';
        } catch (Exception $e) { return ''; }
    }

    // ── Extract variable names used in a template body ────────────────────
    public static function extractVariables(string $html): array
    {
        preg_match_all('/\{\{([a-zA-Z0-9_.]+)\}\}/', $html, $matches);
        return array_unique($matches[1] ?? []);
    }

    // ── Full variable catalogue (for the template builder UI) ─────────────
    public static function catalogue(): array
    {
        return [
            'Company' => [
                'company.name'     => 'Company Name',
                'company.address'  => 'Company Address',
                'company.phone'    => 'Company Phone',
                'company.email'    => 'Company Email',
                'company.website'  => 'Company Website',
            ],
            'Employee — Personal' => [
                'employee.number'        => 'Employee Number',
                'employee.full_name'     => 'Full Name',
                'employee.first_name'    => 'First Name',
                'employee.last_name'     => 'Last Name',
                'employee.preferred_name'=> 'Preferred Name',
                'employee.title'         => 'Title (Mr./Ms.)',
                'employee.national_id'   => 'National ID',
                'employee.date_of_birth' => 'Date of Birth',
                'employee.gender'        => 'Gender',
                'employee.nationality'   => 'Nationality',
                'employee.email'         => 'Work Email',
                'employee.phone'         => 'Phone',
                'employee.address'       => 'Residential Address',
            ],
            'Employee — Employment' => [
                'employee.department'       => 'Department',
                'employee.position'         => 'Position/Title',
                'employee.type'             => 'Employment Type',
                'employee.status'           => 'Status',
                'employee.start_date'       => 'Start Date (long)',
                'employee.start_date_short' => 'Start Date (short)',
                'employee.work_location'    => 'Work Location',
                'employee.supervisor'       => 'Supervisor Name',
                'employee.salary_gross'     => 'Gross Salary (formatted)',
                'employee.salary_net'       => 'Net Salary (formatted)',
                'employee.basic_salary'     => 'Basic Salary (formatted)',
                'employee.leave_balance'    => 'Leave Balance',
            ],
            'Emergency Contact' => [
                'employee.emergency_name'  => 'Emergency Contact Name',
                'employee.emergency_phone' => 'Emergency Contact Phone',
                'employee.emergency_rel'   => 'Emergency Contact Relationship',
            ],
            'Dates' => [
                'date.today'       => 'Today\'s Date (long)',
                'date.today_short' => 'Today\'s Date (short)',
                'date.month'       => 'Current Month and Year',
                'date.year'        => 'Current Year',
            ],
            'Branding Assets (HTML)' => [
                'signature'       => 'Primary Signature (first active signatory)',
                'stamp'           => 'Company Stamp (first active stamp)',
                'doc.number'      => 'Auto Document Reference Number',
                'page.number'     => 'Page Number',
                'currency.symbol' => 'Currency Symbol (e.g. K)',
                'currency.code'   => 'Currency Code (e.g. PGK)',
            ],
        ];
    }
}

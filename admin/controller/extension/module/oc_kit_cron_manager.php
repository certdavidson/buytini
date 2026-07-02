<?php
/**
 * Cron Manager — OpenCart 3.x Module
 *
 * @package   OcKit\CronManager
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

class ControllerExtensionModuleOcKitCronManager extends Controller
{
    private ?OcKit\CronManager\CronManager $lib = null;

    private function getLib(): \OcKit\CronManager\CronManager
    {
        if ($this->lib === null) {
            require_once DIR_SYSTEM . 'library/ockit/cron_manager/CronManager.php';
            $this->lib = new \OcKit\CronManager\CronManager($this->registry);
            $this->lib->install();
        }
        return $this->lib;
    }

    // ─── Main page ────────────────────────────────────────────────────────────

    public function index(): void
    {
        $this->load->language('extension/module/oc_kit_cron_manager');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->document->addStyle('view/javascript/ockit/assets/css/styles.css');
        $this->document->addStyle('view/javascript/ockit/cron-manager/assets/css/styles.css');
        $this->document->addScript('view/javascript/ockit/assets/js/ok-common.js');
        $this->document->addScript('view/javascript/ockit/assets/js/lucide.min.js');
        $this->document->addScript('view/javascript/ockit/cron-manager/assets/js/admin.js');

        $token = $this->session->data['user_token'];

        $data['breadcrumbs'] = [
            [
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'user_token=' . $token, true),
            ],
            [
                'text' => $this->language->get('text_extension'),
                'href' => $this->url->link('marketplace/extension', 'user_token=' . $token . '&type=module', true),
            ],
            [
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('extension/module/oc_kit_cron_manager', 'user_token=' . $token, true),
            ],
        ];

        $data['jobs'] = $this->getLib()->getJobs();

        // Pass cron path for the "setup" hint
        $data['cron_bootstrap_path'] = realpath(DIR_SYSTEM . '../crons/cron_manager.php');
        $data['php_binary']          = PHP_BINARY;

        // Language strings for Twig
        foreach ($this->buildLangKeys() as $key) {
            $data[$key] = $this->language->get($key);
        }

        // AJAX endpoints
        $u = function (string $route, string $extra = '') use ($token): string {
            return html_entity_decode(
                $this->url->link($route, 'user_token=' . $token . $extra, true),
                ENT_QUOTES,
                'UTF-8'
            );
        };

        // All URLs as JSON — safe against any special chars in token or route
        $data['cm_urls'] = json_encode([
            'save'            => $u('extension/module/oc_kit_cron_manager/save'),
            'delete'          => $u('extension/module/oc_kit_cron_manager/delete'),
            'toggle'          => $u('extension/module/oc_kit_cron_manager/toggle'),
            'run'             => $u('extension/module/oc_kit_cron_manager/run'),
            'logs'            => $u('extension/module/oc_kit_cron_manager/getLogs'),
            'getJob'          => $u('extension/module/oc_kit_cron_manager/getJob'),
            'previewSchedule' => $u('extension/module/oc_kit_cron_manager/previewSchedule'),
            'scan'            => $u('extension/module/oc_kit_cron_manager/scan'),
            'clearLogs'       => $u('extension/module/oc_kit_cron_manager/clearLogs'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $data['i18n'] = json_encode($this->buildI18n(), JSON_UNESCAPED_UNICODE);

        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput(
            $this->load->view('extension/module/ockit/cron_manager/index', $data)
        );
    }

    // ─── AJAX: save job ───────────────────────────────────────────────────────

    public function save(): void
    {
        $this->load->language('extension/module/oc_kit_cron_manager');
        $json = [];

        if ($this->request->server['REQUEST_METHOD'] !== 'POST' || !$this->validate()) {
            $json['error'] = $this->language->get('error_permission');
            $this->jsonOut($json);
            return;
        }

        $post = $this->request->post;

        if (empty(trim((string)($post['name'] ?? '')))) {
            $json['error'] = $this->language->get('error_name_required');
            $this->jsonOut($json);
            return;
        }

        if (empty(trim((string)($post['command'] ?? '')))) {
            $json['error'] = $this->language->get('error_command_required');
            $this->jsonOut($json);
            return;
        }

        $schedule = trim((string)($post['schedule'] ?? '* * * * *'));
        require_once DIR_SYSTEM . 'library/ockit/cron_manager/CronManager.php';
        $preview = $this->getLib()->previewSchedule($schedule);
        if (!$preview['valid']) {
            $json['error'] = $this->language->get('error_schedule_invalid');
            $this->jsonOut($json);
            return;
        }

        $jobId = $this->getLib()->saveJob([
            'job_id'      => (int)($post['job_id'] ?? 0),
            'name'        => trim((string)$post['name']),
            'description' => trim((string)($post['description'] ?? '')),
            'type'        => $post['type'] ?? 'php',
            'command'     => trim((string)$post['command']),
            'schedule'    => $schedule,
            'timeout'     => (int)($post['timeout'] ?? 60),
            'status'      => !empty($post['status']),
            'sort_order'  => (int)($post['sort_order'] ?? 0),
        ]);

        $json['success'] = $this->language->get('text_success');
        $json['job']     = $this->getLib()->getJob($jobId);
        $this->jsonOut($json);
    }

    // ─── AJAX: get single job (for edit modal) ────────────────────────────────

    public function getJob(): void
    {
        $json = [];

        if (!$this->validate()) {
            $json['error'] = 'Permission denied';
            $this->jsonOut($json);
            return;
        }

        $jobId = (int)($this->request->get['job_id'] ?? 0);
        $job   = $this->getLib()->getJob($jobId);

        if (!$job) {
            $json['error'] = 'Job not found';
            $this->jsonOut($json);
            return;
        }

        $json['success'] = true;
        $json['job']     = $job;
        $this->jsonOut($json);
    }

    // ─── AJAX: delete job ─────────────────────────────────────────────────────

    public function delete(): void
    {
        $this->load->language('extension/module/oc_kit_cron_manager');
        $json = [];

        if ($this->request->server['REQUEST_METHOD'] !== 'POST' || !$this->validate()) {
            $json['error'] = $this->language->get('error_permission');
            $this->jsonOut($json);
            return;
        }

        $jobId = (int)($this->request->post['job_id'] ?? 0);
        if ($jobId > 0) {
            $this->getLib()->deleteJob($jobId);
        }

        $json['success'] = $this->language->get('text_success_delete');
        $this->jsonOut($json);
    }

    // ─── AJAX: toggle enabled ─────────────────────────────────────────────────

    public function toggle(): void
    {
        $json = [];

        if ($this->request->server['REQUEST_METHOD'] !== 'POST' || !$this->validate()) {
            $json['error'] = 'Permission denied';
            $this->jsonOut($json);
            return;
        }

        $jobId  = (int)($this->request->post['job_id'] ?? 0);
        $status = !empty($this->request->post['status']);

        if ($jobId > 0) {
            $this->getLib()->toggleJob($jobId, $status);
        }

        $json['success'] = true;
        $this->jsonOut($json);
    }

    // ─── AJAX: manual run ─────────────────────────────────────────────────────

    public function run(): void
    {
        $this->load->language('extension/module/oc_kit_cron_manager');
        $json = [];

        if ($this->request->server['REQUEST_METHOD'] !== 'POST' || !$this->validate()) {
            $json['error'] = $this->language->get('error_permission');
            $this->jsonOut($json);
            return;
        }

        $jobId  = (int)($this->request->post['job_id'] ?? 0);
        $result = $this->getLib()->runJob($jobId);

        $json['success']  = $result['success'];
        $json['output']   = $result['output'];
        $json['duration'] = $result['duration'];
        $json['job']      = $this->getLib()->getJob($jobId);
        $this->jsonOut($json);
    }

    // ─── AJAX: get logs ───────────────────────────────────────────────────────

    public function getLogs(): void
    {
        $json = [];

        if (!$this->validate()) {
            $json['error'] = 'Permission denied';
            $this->jsonOut($json);
            return;
        }

        $jobId = (int)($this->request->get['job_id'] ?? 0);
        $json['success'] = true;
        $json['logs']    = $this->getLib()->getLogs($jobId, 50);
        $this->jsonOut($json);
    }

    // ─── AJAX: clear job logs ─────────────────────────────────────────────────

    public function clearLogs(): void
    {
        $json = [];

        if ($this->request->server['REQUEST_METHOD'] !== 'POST' || !$this->validate()) {
            $json['error'] = 'Permission denied';
            $this->jsonOut($json);
            return;
        }

        $jobId = (int)($this->request->post['job_id'] ?? 0);
        if ($jobId > 0) {
            $this->getLib()->clearLogs($jobId);
        }

        $json['success'] = true;
        $this->jsonOut($json);
    }

    // ─── AJAX: preview schedule ───────────────────────────────────────────────

    public function previewSchedule(): void
    {
        $schedule = trim((string)($this->request->get['schedule'] ?? ''));
        $this->jsonOut($this->getLib()->previewSchedule($schedule));
    }

    // ─── AJAX: scan crons dir ─────────────────────────────────────────────────

    public function scan(): void
    {
        $json = [];

        if (!$this->validate()) {
            $json['error'] = 'Permission denied';
            $this->jsonOut($json);
            return;
        }

        $cronsDir        = realpath(DIR_SYSTEM . '../crons') ?: '';
        $json['success'] = true;
        $json['files']   = $this->getLib()->scanCrons($cronsDir);
        $this->jsonOut($json);
    }

    // ─── Install / Uninstall ──────────────────────────────────────────────────

    public function install(): void
    {
        $this->getLib()->install();
    }

    public function uninstall(): void
    {
        $this->getLib()->uninstall();
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function validate(): bool
    {
        return $this->user->hasPermission('modify', 'extension/module/oc_kit_cron_manager');
    }

    private function jsonOut(array $data): void
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    private function buildLangKeys(): array
    {
        return [
            'heading_title', 'heading_title_simple', 'text_home', 'text_extension', 'text_module_name',
            'text_no_jobs', 'text_cron_setup',
            'column_name', 'column_type', 'column_command', 'column_schedule',
            'column_last_run', 'column_next_run', 'column_status', 'column_enabled', 'column_actions',
            'button_add', 'button_scan', 'button_save', 'button_cancel',
            'button_run', 'button_logs', 'button_edit', 'button_delete',
            'text_type_php', 'text_type_shell', 'text_type_url',
            'text_status_never', 'text_status_success', 'text_status_error', 'text_status_running',
            'entry_name', 'entry_description', 'entry_type', 'entry_command',
            'entry_schedule', 'entry_timeout', 'entry_status',
            'help_schedule', 'help_command_php', 'help_command_shell', 'help_command_url',
        ];
    }

    private function buildI18n(): array
    {
        $keys = [
            'text_success', 'text_success_delete', 'text_confirm_delete', 'text_confirm_run',
            'text_running', 'text_run_output', 'text_no_logs', 'text_loading',
            'text_scan_none', 'text_scan_title',
            'button_add', 'button_edit', 'button_save', 'button_cancel',
            'button_delete', 'button_run', 'button_logs', 'button_clear_logs',
            'text_type_php', 'text_type_shell', 'text_type_url',
            'text_status_never', 'text_status_success', 'text_status_error', 'text_status_running',
            'column_date', 'column_duration', 'column_status', 'column_output', 'column_triggered_by',
            'text_triggered_scheduler', 'text_triggered_manual',
            'error_name_required', 'error_command_required', 'error_schedule_invalid',
            'help_command_php', 'help_command_shell', 'help_command_url',
            'text_schedule_next', 'text_schedule_invalid',
            'text_ms', 'text_sec',
        ];

        $i18n = [];
        foreach ($keys as $k) {
            $i18n[$k] = $this->language->get($k);
        }
        return $i18n;
    }
}

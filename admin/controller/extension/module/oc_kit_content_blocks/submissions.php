<?php
/**
 * Content Blocks Pro — AJAX: form submissions listing/view/delete + private attachment download.
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

class ControllerExtensionModuleOcKitContentBlocksSubmissions extends Controller
{
    public function index(): void
    {
        $this->load->language('extension/module/oc_kit_content_blocks');

        if (!$this->user->hasPermission('access', 'extension/module/oc_kit_content_blocks')) {
            $this->respond(['error' => $this->language->get('error_permission')]);
            return;
        }

        $action = (string)($this->request->post['action'] ?? $this->request->get['action'] ?? 'list');

        try {
            require_once DIR_SYSTEM . 'library/ockit/content_blocks/ContentBlocks.php';
            $cb = new \OcKit\ContentBlocks\ContentBlocks($this->registry);
            $repo = $cb->getFormRepo();

            switch ($action) {
                case 'view':
                    $id  = (int)($this->request->get['submission_id'] ?? 0);
                    $rows = $repo->getSubmissions(0, 0, 1000);
                    $row = null;
                    foreach ($rows as $r) if ((int)$r['submission_id'] === $id) { $row = $r; break; }
                    if (!$row) { $this->respond(['error' => 'Not found']); return; }
                    foreach ($row['fields'] as &$f) {
                        if (!empty($f['file_path'])) {
                            $f['download_url'] = $this->url->link(
                                'extension/module/oc_kit_content_blocks/submissions/download',
                                'user_token=' . $this->session->data['user_token'] . '&submission_id=' . $id . '&field=' . urlencode($f['field_name']),
                                true
                            );
                        }
                    }
                    unset($f);
                    $this->respond(['submission' => $row]);
                    return;

                case 'delete':
                    if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_content_blocks')) {
                        $this->respond(['error' => $this->language->get('error_permission')]); return;
                    }
                    $id = (int)($this->request->post['submission_id'] ?? 0);
                    // Also remove physical attachments
                    $rows = $repo->getSubmissions(0, 0, 1000);
                    foreach ($rows as $r) {
                        if ((int)$r['submission_id'] === $id) {
                            foreach ($r['fields'] as $f) {
                                if (!empty($f['file_path'])) {
                                    $abs = DIR_IMAGE . $f['file_path'];
                                    if (is_file($abs)) @unlink($abs);
                                }
                            }
                            break;
                        }
                    }
                    $repo->deleteSubmission($id);
                    $this->respond(['success' => 1]);
                    return;

                case 'list':
                default:
                    $blockId = (int)($this->request->post['block_id'] ?? 0);
                    $page    = max(1, (int)($this->request->post['page'] ?? 1));
                    $per     = 50;
                    $list    = $repo->getSubmissions($blockId, ($page - 1) * $per, $per);
                    $total   = $repo->countSubmissions($blockId);
                    $this->respond([
                        'submissions' => $list,
                        'total'       => $total,
                        'page'        => $page,
                        'pages'       => (int)ceil($total / $per),
                    ]);
                    return;
            }
        } catch (\Throwable $e) {
            $this->respond(['error' => $e->getMessage()]);
        }
    }

    /** Streams a private form-attachment file to the admin. */
    public function download(): void
    {
        $this->load->language('extension/module/oc_kit_content_blocks');
        if (!$this->user->hasPermission('access', 'extension/module/oc_kit_content_blocks')) {
            http_response_code(403);
            return;
        }

        $id    = (int)($this->request->get['submission_id'] ?? 0);
        $field = (string)($this->request->get['field']        ?? '');
        if (!$id || $field === '') { http_response_code(404); return; }

        require_once DIR_SYSTEM . 'library/ockit/content_blocks/ContentBlocks.php';
        $repo = (new \OcKit\ContentBlocks\ContentBlocks($this->registry))->getFormRepo();
        $rows = $repo->getSubmissions(0, 0, 1000);
        foreach ($rows as $r) {
            if ((int)$r['submission_id'] !== $id) continue;
            foreach ($r['fields'] as $f) {
                if ($f['field_name'] === $field && !empty($f['file_path'])) {
                    $abs = DIR_IMAGE . $f['file_path'];
                    if (!is_file($abs)) { http_response_code(404); return; }
                    // Strip the random `file_{token}_` prefix when surfacing the original name.
                    $display = preg_replace('/^file_[a-f0-9]{32}_/i', '', basename($abs));
                    header('Content-Type: application/octet-stream');
                    header('Content-Length: ' . filesize($abs));
                    header('Content-Disposition: attachment; filename="' . $display . '"');
                    readfile($abs);
                    return;
                }
            }
            break;
        }
        http_response_code(404);
    }

    private function respond(array $json): void
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}

<?php
/**
 * Content Blocks Pro — Form submission endpoint.
 * Accepts multipart/form-data POST keyed by element_id, validates the form
 * element's stored params/fields, stores attachments privately, saves a
 * submission row, and emails the configured recipient.
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

class ControllerExtensionModuleOcKitContentBlocksFormSubmit extends Controller
{
    private const PRIVATE_DIR_REL = 'catalog/cb-forms/';

    public function index(): void
    {
        $this->load->language('extension/module/oc_kit_content_blocks');

        $resp = ['success' => 0, 'errors' => [], 'message' => ''];

        if (empty($this->config->get('module_oc_kit_content_blocks_status'))) {
            $resp['message'] = $this->language->get('form_error_default');
            $this->respond($resp);
            return;
        }

        $elementId = (int)($this->request->post['element_id'] ?? 0);
        if ($elementId <= 0) {
            $resp['message'] = $this->language->get('form_error_default');
            $this->respond($resp);
            return;
        }

        try {
            require_once DIR_SYSTEM . 'library/ockit/content_blocks/ContentBlocks.php';
            $cb      = new \OcKit\ContentBlocks\ContentBlocks($this->registry);
            $element = $cb->getElementRepo()->getElement($elementId);

            if (!$element || $element->element_type !== 'form') {
                $resp['message'] = $this->language->get('form_error_default');
                $this->respond($resp);
                return;
            }

            $params = is_array($element->params) ? $element->params : [];
            $fields = is_array($params['fields'] ?? null) ? $params['fields'] : [];
            $langId = (int)$this->config->get('config_language_id');
            $blockId = (int)$element->block_id;

            // Server-side validation. Per-type accept and max-size come from
            // module settings now — there's no per-form/per-field override.
            $maxBytes    = max(1, (int)($this->config->get('module_oc_kit_content_blocks_form_max_size') ?: 5120)) * 1024;
            $acceptFile  = (string)$this->config->get('module_oc_kit_content_blocks_form_accept_file');
            $acceptImage = (string)$this->config->get('module_oc_kit_content_blocks_form_accept_image');
            if ($acceptImage === '') $acceptImage = 'image/*';
            $errors   = [];
            $captured = [];

            foreach ($fields as $f) {
                $fname = (string)($f['name'] ?? '');
                if ($fname === '') continue;
                $ftype  = (string)($f['type']   ?? 'text');
                $req    = !empty($f['required']);
                $accept = $ftype === 'image' ? $acceptImage : ($ftype === 'file' ? $acceptFile : '');
                $fLang  = is_array($f['lang'] ?? null) ? $f['lang'] : [];
                $ld     = $fLang[$langId] ?? ($fLang[array_key_first($fLang) ?? 0] ?? []);
                $label  = (string)($ld['label'] ?? $fname);

                if ($ftype === 'file' || $ftype === 'image') {
                    $up = $_FILES[$fname] ?? null;
                    if (!$up || ($up['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                        if ($req) $errors[$fname] = $this->language->get('form_field_required');
                        continue;
                    }
                    if (($up['error'] ?? 0) !== UPLOAD_ERR_OK) {
                        $errors[$fname] = $this->language->get('form_error_default');
                        continue;
                    }
                    if (($up['size'] ?? 0) > $maxBytes) {
                        $errors[$fname] = $this->language->get('form_field_file_too_large');
                        continue;
                    }
                    if (!$this->isMimeAllowed($up, $accept, $ftype)) {
                        $errors[$fname] = $this->language->get('form_field_file_invalid_type');
                        continue;
                    }
                    $stored = $this->storeUploadedFile($up);
                    if (!$stored) {
                        $errors[$fname] = $this->language->get('form_error_default');
                        continue;
                    }
                    $captured[$fname] = ['label' => $label, 'value' => basename($up['name']), 'file' => $stored];
                    continue;
                }

                $raw = trim((string)($this->request->post[$fname] ?? ''));
                if ($req && $raw === '') {
                    $errors[$fname] = $this->language->get('form_field_required');
                    continue;
                }
                if ($ftype === 'email' && $raw !== '' && !filter_var($raw, FILTER_VALIDATE_EMAIL)) {
                    $errors[$fname] = $this->language->get('form_field_invalid_email');
                    continue;
                }
                $captured[$fname] = ['label' => $label, 'value' => $raw, 'file' => ''];
            }

            if ($errors) {
                $resp['errors']  = $errors;
                $resp['message'] = $this->language->get('form_error_default');
                $this->respond($resp);
                return;
            }

            // Resolve per-lang form-level strings (subject / success_message)
            $perLang  = is_array($params['lang'] ?? null) ? $params['lang'] : [];
            $formLd   = $perLang[$langId] ?? ($perLang[array_key_first($perLang) ?? 0] ?? []);
            $subject  = trim((string)($formLd['subject']         ?? ''));
            $successM = trim((string)($formLd['success_message'] ?? ''));

            // Resolve block context (page_route/page_id) for the DB row + email metadata
            $blockArr = [];
            try {
                $blockDto = $cb->getBlock($blockId);
                $blockArr = json_decode(json_encode($blockDto), true) ?: [];
            } catch (\Throwable $e) { /* block may have been deleted — keep going */ }

            // ─── Persist + email ───────────────────────────────────────────
            $submissionId = $cb->getFormRepo()->insert([
                'block_id'   => $blockId,
                'element_id' => $elementId,
                'page_route' => (string)($blockArr['page_route'] ?? ''),
                'page_id'    => (int)($blockArr['page_id']   ?? 0),
                'ip'         => (string)($this->request->server['REMOTE_ADDR']     ?? ''),
                'user_agent' => substr((string)($this->request->server['HTTP_USER_AGENT'] ?? ''), 0, 255),
            ], $captured);

            $this->sendEmail($blockArr, $params, $captured, $submissionId, $subject);

            $resp['success'] = 1;
            $resp['message'] = $successM !== '' ? $successM : $this->language->get('form_success_default');
            if (!empty($params['redirect_url'])) $resp['redirect'] = (string)$params['redirect_url'];
        } catch (\Throwable $e) {
            $this->log->write('CB form_submit error: ' . $e->getMessage());
            $resp['message'] = $this->language->get('form_error_default');
        }

        $this->respond($resp);
    }

    private function respond(array $resp): void
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($resp));
    }

    private function isMimeAllowed(array $up, string $accept, string $fieldType): bool
    {
        $mime = function_exists('finfo_open')
            ? (finfo_file(finfo_open(FILEINFO_MIME_TYPE), $up['tmp_name']) ?: '')
            : '';
        $name = strtolower((string)$up['name']);
        $ext  = pathinfo($name, PATHINFO_EXTENSION);

        $deny = ['php', 'phtml', 'phar', 'exe', 'bat', 'sh', 'js', 'htaccess'];
        if (in_array($ext, $deny, true)) return false;

        if ($fieldType === 'image' && strpos($mime, 'image/') !== 0) return false;

        if ($accept === '') return true;
        $parts = array_filter(array_map('trim', explode(',', $accept)));
        foreach ($parts as $part) {
            $part = strtolower($part);
            if ($part === '') continue;
            if (strpos($part, '/') !== false) {
                if ($mime === '') continue;
                $part = rtrim($part, '*');
                if (strpos($mime, $part) === 0) return true;
            } else {
                $part = ltrim($part, '.');
                if ($ext === $part) return true;
            }
        }
        return false;
    }

    private function storeUploadedFile(array $up): string
    {
        $dirRel = self::PRIVATE_DIR_REL . date('Y/m') . '/';
        $dirAbs = DIR_IMAGE . $dirRel;
        if (!is_dir($dirAbs)) {
            @mkdir($dirAbs, 0755, true);
        }
        // Drop a tiny .htaccess once so direct HTTP access is blocked even if
        // the image directory becomes web-accessible (Apache configs vary).
        $htaccess = DIR_IMAGE . self::PRIVATE_DIR_REL . '.htaccess';
        if (!is_file($htaccess)) {
            @file_put_contents($htaccess, "Require all denied\n");
        }
        $ext     = strtolower(pathinfo((string)$up['name'], PATHINFO_EXTENSION));
        $token   = bin2hex(random_bytes(16));
        $clean   = preg_replace('/[^a-z0-9._-]+/i', '_', pathinfo((string)$up['name'], PATHINFO_FILENAME));
        $clean   = substr($clean, 0, 60) ?: 'file';
        $name    = 'file_' . $token . '_' . $clean . ($ext ? '.' . $ext : '');
        $absPath = $dirAbs . $name;
        if (!@move_uploaded_file($up['tmp_name'], $absPath)) return '';
        return $dirRel . $name;
    }

    private function sendEmail(array $block, array $params, array $captured, int $submissionId, string $subject): void
    {
        $recipient = trim((string)($params['recipient_email'] ?? ''));
        if ($recipient === '') $recipient = (string)$this->config->get('config_email');
        if ($recipient === '') return;

        if ($subject === '') {
            $subject = sprintf($this->language->get('form_subject_default'), $this->config->get('config_name'));
        }

        $data = [
            'submission_id' => $submissionId,
            'site_name'     => (string)$this->config->get('config_name'),
            'site_url'      => (string)(HTTPS_SERVER ?: HTTP_SERVER),
            'block_id'      => (int)($block['block_id'] ?? 0),
            'page_route'    => (string)($block['page_route'] ?? ''),
            'page_id'       => (int)($block['page_id'] ?? 0),
            'ip'            => (string)($this->request->server['REMOTE_ADDR'] ?? ''),
            'date'          => date('Y-m-d H:i:s'),
            'fields'        => $captured,
            't'             => [
                'form_field_label'  => $this->language->get('form_field_label'),
                'form_meta_block'   => $this->language->get('form_meta_block'),
                'form_meta_page'    => $this->language->get('form_meta_page'),
                'form_meta_ip'      => $this->language->get('form_meta_ip'),
                'form_meta_date'    => $this->language->get('form_meta_date'),
                'form_attachments'  => $this->language->get('form_attachments'),
            ],
        ];

        $html = $this->load->view('extension/module/ockit/content_blocks/emails/form_submission', $data);

        $mail = new Mail($this->config->get('config_mail_engine'));
        $mail->parameter   = $this->config->get('config_mail_parameter');
        $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
        $mail->smtp_username = $this->config->get('config_mail_smtp_username');
        $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
        $mail->smtp_port     = $this->config->get('config_mail_smtp_port');
        $mail->smtp_timeout  = $this->config->get('config_mail_smtp_timeout');

        $mail->setTo($recipient);
        $mail->setFrom($this->config->get('config_email'));
        $mail->setSender($this->config->get('config_name'));
        $mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
        $mail->setHtml($html);

        foreach ($captured as $name => $row) {
            if (!empty($row['file'])) {
                $abs = DIR_IMAGE . $row['file'];
                if (is_file($abs)) $mail->addAttachment($abs);
            }
        }
        try { $mail->send(); } catch (\Throwable $e) {
            $this->log->write('CB form_submit mail error: ' . $e->getMessage());
        }
    }
}

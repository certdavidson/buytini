<?php
/**
 * Content Blocks Pro — AJAX: upload image file with filename transliteration.
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

class ControllerExtensionModuleOcKitContentBlocksUpload extends Controller
{
    private const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
    private const MAX_SIZE      = 10 * 1024 * 1024; // 10 MB

    public function index(): void
    {
        require_once DIR_SYSTEM . 'library/ockit/content_blocks/ContentBlocks.php';

        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/module/oc_kit_content_blocks')) {
            $json['error'] = 'Permission denied';
            $this->respond($json);
            return;
        }

        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $json['error'] = 'No file uploaded';
            $this->respond($json);
            return;
        }

        $file = $_FILES['file'];

        if (!in_array($file['type'], self::ALLOWED_TYPES, true)) {
            $json['error'] = 'Invalid file type. Allowed: jpg, png, gif, webp, svg';
            $this->respond($json);
            return;
        }

        if ($file['size'] > self::MAX_SIZE) {
            $json['error'] = 'File too large (max 10 MB)';
            $this->respond($json);
            return;
        }

        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // Verify file content actually matches a real image (magic bytes).
        // SVG is XML (no magic bytes) — treat separately and only allow when
        // exactly the SVG MIME was declared.
        if ($ext === 'svg' || $file['type'] === 'image/svg+xml') {
            $head = (string)@file_get_contents($file['tmp_name'], false, null, 0, 1024);
            if (stripos($head, '<svg') === false) {
                $json['error'] = 'Invalid SVG file';
                $this->respond($json);
                return;
            }
        } else {
            $info = @getimagesize($file['tmp_name']);
            if (!$info || empty($info['mime']) || !in_array($info['mime'], self::ALLOWED_TYPES, true)) {
                $json['error'] = 'Uploaded file is not a valid image';
                $this->respond($json);
                return;
            }
        }
        $name = pathinfo($file['name'], PATHINFO_FILENAME);
        $name = $this->transliterate($name);
        $name = preg_replace('/[^a-z0-9]+/', '-', $name);
        $name = trim($name, '-') ?: 'image';
        $name = $name . '-' . time() . '.' . $ext;

        $uploadDirSetting = trim($this->config->get('module_oc_kit_content_blocks_upload_dir') ?: 'image/catalog/content-blocks', '/');
        // DIR_IMAGE already contains 'image/', strip it from setting if present
        $relPath = (substr($uploadDirSetting, 0, 6) === 'image/')
            ? substr($uploadDirSetting, 6)
            : $uploadDirSetting;
        $relPath = trim($relPath, '/');

        // Whitelist: only safe path segments — no '..', no leading slash, no NUL.
        if ($relPath === '' || !preg_match('#^[A-Za-z0-9_\-/]+$#', $relPath) || strpos($relPath, '..') !== false) {
            $json['error'] = 'Invalid upload directory configured';
            $this->respond($json);
            return;
        }

        $dir = DIR_IMAGE . $relPath . '/';
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            $json['error'] = 'Cannot create upload directory';
            $this->respond($json);
            return;
        }

        // Containment check: resolved dir must stay within DIR_IMAGE.
        $realBase = realpath(DIR_IMAGE);
        $realDir  = realpath($dir);
        if ($realBase === false || $realDir === false || strpos($realDir, $realBase) !== 0) {
            $json['error'] = 'Upload directory is outside the image root';
            $this->respond($json);
            return;
        }

        $dest = $dir . $name;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            $json['error'] = 'Failed to save uploaded file';
            $this->respond($json);
            return;
        }

        $path = $relPath . '/' . $name;

        $this->load->model('tool/image');
        $json['path'] = $path;
        $json['url']  = $this->model_tool_image->resize($path, 200, 200);

        $this->respond($json);
    }

    private function respond(array $json): void
    {
        \OcKit\ContentBlocks\ContentBlocks::json($this->response, $json);
    }

    private function transliterate(string $text): string
    {
        $map = [
            'а'=>'a',  'б'=>'b',  'в'=>'v',  'г'=>'g',  'д'=>'d',  'е'=>'e',
            'ё'=>'yo', 'ж'=>'zh', 'з'=>'z',  'и'=>'i',  'й'=>'y',  'к'=>'k',
            'л'=>'l',  'м'=>'m',  'н'=>'n',  'о'=>'o',  'п'=>'p',  'р'=>'r',
            'с'=>'s',  'т'=>'t',  'у'=>'u',  'ф'=>'f',  'х'=>'kh', 'ц'=>'ts',
            'ч'=>'ch', 'ш'=>'sh', 'щ'=>'shch','ъ'=>'',  'ы'=>'y',  'ь'=>'',
            'э'=>'e',  'ю'=>'yu', 'я'=>'ya',
            // Ukrainian
            'є'=>'ye', 'і'=>'i',  'ї'=>'yi', 'ґ'=>'g',
            ' '=>'-',  '_'=>'-',
        ];

        $text = mb_strtolower($text, 'UTF-8');
        $out  = '';
        $len  = mb_strlen($text, 'UTF-8');
        for ($i = 0; $i < $len; $i++) {
            $ch   = mb_substr($text, $i, 1, 'UTF-8');
            $out .= $map[$ch] ?? $ch;
        }
        return $out;
    }
}

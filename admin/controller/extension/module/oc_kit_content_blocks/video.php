<?php
/**
 * Content Blocks Pro — AJAX: get YouTube video thumbnail.
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 * @link      https://oc-kit.com
 */

class ControllerExtensionModuleOcKitContentBlocksVideo extends Controller
{
    public function index(): void
    {
        $json = [];

        if (!$this->user->hasPermission('access', 'extension/module/oc_kit_content_blocks')) {
            $json['error'] = 'Permission denied';
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        $videoLink = (string)($this->request->post['video_link'] ?? '');

        if (empty($videoLink)) {
            $json['error'] = 'No video link provided';
        } else {
            $videoId = $this->extractYouTubeId($videoLink);

            if ($videoId) {
                $json['thumb']    = 'https://img.youtube.com/vi/' . $videoId . '/mqdefault.jpg';
                $json['video_id'] = $videoId;
            } else {
                $json['error'] = 'Cannot parse YouTube video ID from: ' . htmlspecialchars($videoLink);
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    private function extractYouTubeId(string $url): ?string
    {
        $patterns = [
            '/[?&]v=([a-zA-Z0-9_-]{11})/',
            '/youtu\.be\/([a-zA-Z0-9_-]{11})/',
            '/\/shorts\/([a-zA-Z0-9_-]{11})/',
            '/\/embed\/([a-zA-Z0-9_-]{11})/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $m)) {
                return $m[1];
            }
        }

        return null;
    }
}

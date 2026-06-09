<?php
declare(strict_types=1);

function github_commit_file(string $abs_path, string $content, string $commit_message): bool
{
    if (!defined('GITHUB_TOKEN') || GITHUB_TOKEN === '') {
        return false;
    }

    $rel = ltrim(str_replace('\\', '/', str_replace(BASE_PATH, '', $abs_path)), '/');

    $api_url = sprintf(
        'https://api.github.com/repos/%s/%s/contents/%s',
        GITHUB_OWNER,
        GITHUB_REPO,
        implode('/', array_map('rawurlencode', explode('/', $rel)))
    );

    $sha  = _github_get_sha($api_url);

    $body = [
        'message' => $commit_message,
        'content' => base64_encode($content),
        'branch'  => GITHUB_BRANCH,
    ];
    if ($sha !== null) {
        $body['sha'] = $sha;
    }

    $result = _github_request('PUT', $api_url, $body);
    return isset($result['content']['sha']);
}

function _github_get_sha(string $url): ?string
{
    $r = _github_request('GET', $url . '?ref=' . GITHUB_BRANCH);
    return isset($r['sha']) ? (string) $r['sha'] : null;
}

function _github_request(string $method, string $url, array $body = []): ?array
{
    $headers = [
        'Authorization: Bearer ' . GITHUB_TOKEN,
        'User-Agent: ainowguide-cms/1.0',
        'Accept: application/vnd.github+json',
        'X-GitHub-Api-Version: 2022-11-28',
    ];

    $opts = [
        'http' => [
            'method'        => $method,
            'ignore_errors' => true,
            'timeout'       => 15,
        ],
    ];

    if ($body !== []) {
        $json = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Content-Length: ' . strlen($json);
        $opts['http']['content'] = $json;
    }

    $opts['http']['header'] = implode("\r\n", $headers);

    $raw = @file_get_contents($url, false, stream_context_create($opts));
    if ($raw === false) {
        return null;
    }

    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

<?php
// templates/partials/render-blocks.php
// Centralized block renderer for all structured content blocks

if (!isset($blocks) || !is_array($blocks)) return;

foreach ($blocks as $block) {
    $type = $block['type'] ?? '';
    $class = 'block-' . preg_replace('/[^a-z0-9\-]/', '', strtolower($type));
    switch ($type) {
        case 'heading':
            $level = isset($block['level']) && $block['level'] >= 1 && $block['level'] <= 6 ? (int)$block['level'] : 2;
            echo '<h' . $level . ' class="' . $class . '">' . site_e($block['text'] ?? '') . '</h' . $level . '>';
            break;
        case 'paragraph':
            echo '<p class="' . $class . '">' . site_e($block['text'] ?? '') . '</p>';
            break;
        case 'image':
            $src = site_e($block['src'] ?? '');
            $alt = site_e($block['alt'] ?? '');
            if ($src) echo '<img class="' . $class . '" src="' . $src . '" alt="' . $alt . '" style="max-width:100%;margin:1.5em 0;" />';
            break;
        case 'list':
            $items = $block['items'] ?? [];
            $ordered = !empty($block['ordered']);
            $tag = $ordered ? 'ol' : 'ul';
            echo '<' . $tag . ' class="' . $class . '">';
            foreach ($items as $item) {
                echo '<li>' . site_e($item) . '</li>';
            }
            echo '</' . $tag . '>';
            break;
        case 'quote':
            echo '<blockquote class="' . $class . '">' . site_e($block['text'] ?? '') . '</blockquote>';
            break;
        case 'code':
            $lang = site_e($block['language'] ?? '');
            echo '<pre class="' . $class . '"><code' . ($lang ? ' class="language-' . $lang . '"' : '') . '>' . site_e($block['code'] ?? '') . '</code></pre>';
            break;
        case 'callout':
            echo '<div class="' . $class . '"><strong>' . site_e($block['title'] ?? 'Note') . ':</strong> ' . site_e($block['text'] ?? '') . '</div>';
            break;
        case 'html':
            echo '<div class="' . $class . '">' . ($block['content'] ?? '') . '</div>';
            break;
        case 'embed':
            $url = site_e($block['url'] ?? '');
            if ($url) echo '<div class="' . $class . '"><iframe src="' . $url . '" frameborder="0" allowfullscreen style="width:100%;min-height:300px;"></iframe></div>';
            break;
        default:
            // Fallback for unknown block types
            echo '<div class="block-unknown">[Unknown block type: ' . site_e($type) . ']</div>';
            break;
    }
}

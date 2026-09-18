<?php

namespace App\Services;

class HtmlSanitizer
{
    /**
     * Whitelist of tags allowed inside rich text (news content).
     */
    private const ALLOWED_TAGS = '<p><br><strong><b><em><i><u><ul><ol><li><h2><h3><h4><blockquote><a>';

    /**
     * Strip every tag outside the whitelist, then kill inline handlers
     * and javascript: / data: URLs. Script, iframe, object and embed
     * never survive strip_tags since they are not whitelisted.
     */
    public static function clean(?string $html): string
    {
        if (blank($html)) {
            return '';
        }

        $html = strip_tags($html, self::ALLOWED_TAGS);
        $html = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? '';
        $html = preg_replace('/\s(href|src)\s*=\s*(["\']?)\s*(javascript|data|vbscript):[^"\'>\s]*\2/i', ' $1="#"', $html) ?? '';

        return trim($html);
    }
}

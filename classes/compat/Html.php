<?php namespace Renick\Matomo\Classes\Compat;

class Html
{
    /** Returns the url only for http(s) or relative targets, so Matomo data can't inject javascript: links. */
    public static function safeUrl($url): string
    {
        $url = trim((string) $url);
        if ($url === '' || preg_match('#^(https?:)?//#i', $url) || !preg_match('#^[a-z][a-z0-9+.-]*:#i', $url)) {
            return $url;
        }

        return '';
    }
}

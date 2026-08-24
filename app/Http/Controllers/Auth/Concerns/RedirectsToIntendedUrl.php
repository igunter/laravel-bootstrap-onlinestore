<?php

namespace App\Http\Controllers\Auth\Concerns;

use Illuminate\Http\Request;

trait RedirectsToIntendedUrl
{
    /**
     * Remember the page the user was on before starting the auth flow, so we
     * can send them back there once they're logged in / verified. Stored in
     * the session (which survives via the browser's session cookie) rather
     * than a plain request value, since email verification can happen in a
     * separate request after the user leaves to check their inbox.
     */
    protected function rememberIntendedUrl(Request $request): void
    {
        if ($request->session()->has('intended_url')) {
            return;
        }

        $referer = $request->headers->get('referer');

        if ($referer && ! $this->isAuthUrl($referer)) {
            $request->session()->put('intended_url', $referer);
        }
    }

    /**
     * The URL to send the user back to, falling back to the homepage.
     */
    protected function intendedUrl(Request $request): string
    {
        return $request->session()->pull('intended_url', url('/'));
    }

    protected function isAuthUrl(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';

        return (bool) preg_match('#(login|register|logout|forgot-password|reset-password|email/verify)#', $path);
    }
}

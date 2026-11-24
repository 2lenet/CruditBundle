<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Exporter;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The cookie that tells the dialog of the export (controller `export` of CruditUiBundle) that the file is on its way.
 * The dialog sends a token (`download_token`) and watches `crudit_export=<token>`; this helper is the server's side of that
 * contract, for the controller of Crudit and for a project that sends its own file.
 *
 * What the script needs, and what `Cookie::create()` does not do by default: **readable by the script** (not HttpOnly,
 * else every export ends in an error after the delay, the file being downloaded all the same), at the **path `/`** (the
 * script deletes it there) and holding the token and nothing else. The token comes from the client and ends in a header:
 * only a plain identifier is accepted.
 */
final class DownloadCookie
{
    public const NAME = 'crudit_export';
    public const PARAMETER = 'download_token';
    private const TOKEN = '/^[A-Za-z0-9-]{8,64}$/';

    /**
     * Puts the cookie on the response when the request has a valid token; does nothing otherwise (the export of a link,
     * without the dialog).
     */
    public static function attach(Request $request, Response $response): void
    {
        $token = self::token($request);
        if ($token === null) {
            return;
        }

        $response->headers->setCookie(Cookie::create(self::NAME, $token, 0, '/', null, $request->isSecure(), false, false, Cookie::SAMESITE_LAX));
    }

    /**
     * The token of the request, when it is a plain identifier: the export comes from the dialog (a hidden frame), not
     * from a link.
     */
    public static function token(Request $request): ?string
    {
        $token = $request->query->all()[self::PARAMETER] ?? null;

        return is_string($token) && preg_match(self::TOKEN, $token) === 1 ? $token : null;
    }
}

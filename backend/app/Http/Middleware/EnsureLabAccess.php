<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Exceptions\ApiException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLabAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            throw ApiException::forbidden('Silakan login terlebih dahulu.');
        }

        if ($user->hasRole('super_admin')) {
            return $next($request);
        }

        $labId = $request->input('lab_id') ?? $request->route('lab_id');

        if (!$labId) {
            throw ApiException::forbidden('lab_id wajib disertakan untuk akses resource ini.');
        }

        if (!$user->hasLabAccess((int) $labId)) {
            throw ApiException::forbidden('Kamu tidak punya akses ke lab ini.');
        }

        return $next($request);
    }
}
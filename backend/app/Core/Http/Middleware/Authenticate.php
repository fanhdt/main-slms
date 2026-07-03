<?php

declare(strict_types=1);

namespace App\Core\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    protected function redirectTo(Request $request): ?string
    {
        // Untuk API request, jangan redirect — return null
        // Laravel akan throw AuthenticationException yang kita handle di bootstrap/app.php
        return null;
    }
}
<?php
namespace App\Http\Middleware;

use App\Models\Services;
use App\Traits\RespondsWithMessages;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ServcieCheck
{
    use RespondsWithMessages;
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $existService = Services::validateActiveService($request->input('service'));
        if (! $existService['success']) {
            return $this->sendError($existService['message'], Response::HTTP_BAD_REQUEST);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Models\Services;
use App\Traits\RespondsWithMessages;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ServiceCheck
{
    use RespondsWithMessages;
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $service = $request->input("service");
        if (empty($service) || !Services::findActiveServiceByName($service)) {
            return $this->sendError(
                "Ошибка проверки сервиса. Сервис не активен или не найден в реестре",
                Response::HTTP_BAD_REQUEST
            );
        }

        return $next($request);
    }
}

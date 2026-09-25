<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use function PHPUnit\Framework\throwException;

class IdempotenceMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->isMethod(Request::METHOD_POST)) {
            return $next($request);
        }
        if ($request->header("Idempotence-Key") === null) {
            return response()->json(["error" => "Idempotence-Key not found"], Response::HTTP_BAD_REQUEST);
        }
        $lock = Cache::lock('id-' . $request->header("Idempotence-Key"), 1);
        if (!$lock->get()) {
            return response(status: 409);
        }
        try {
            $cached = Cache::get($request->header("Idempotence-Key"));
            if ($cached) {
                if ($cached == "PROCESSING") {
                    return response(status: Response::HTTP_TOO_EARLY);
                }
                return response()->json($cached, Response::HTTP_OK);
            }
            Cache::put($request->header("Idempotence-Key"), "PROCESSING");
            $response = $next($request);
            if (!$response->isSuccessful()) {
                return $response;
            }
            if ($response instanceof JsonResponse) {
                $data = $response->getData(true);
                $data["duplicated"] = true;
                Cache::put($request->header("Idempotence-Key"), $data, now()->addDay());
            } else {
                throw new \Exception("Not json response");
            }
        } finally {
            $lock->release();
        }
        return $response;
    }
}

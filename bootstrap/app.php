<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: '', 
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Forzar respuestas JSON en la API
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            return $request->is('api/*') || $request->is('v1/*') || $request->expectsJson();
        });

        // Manejo personalizado de errores 404 (Ruta no encontrada)
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('v1/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'El recurso solicitado no existe.'
                ], 404);
            }
        });

        // 403: sin permiso sobre el recurso (policies / authorize() de los FormRequest)
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('v1/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para realizar esta acción.'
                ], 403);
            }
        });

        // Manejo personalizado de errores de validación (422)
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('v1/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación en los datos enviados.',
                    'errors' => $e->errors()
                ], 422);
            }
        });
    })->create();
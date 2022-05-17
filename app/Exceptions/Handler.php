<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Throwable;
// use Exception;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->renderable(function (NotFoundHttpException $e, $request) {
            return response()->json([
                'status_code' => 404,
                'message' => 'Error',
                'data' => 'Could not find what you were looking for.'], 404);
        });

        $this->renderable(function (QueryException $e, $request) {
            return response()->json([
                'status_code' => 404,
                'message' => 'Error',
                'data' => 'This query is not supported'], 404);
        });

        $this->renderable(function (MethodNotAllowedHttpException $e, $request) {
            return response()->json([
                'status_code' => 405,
                'message' => 'Error',
                'data' => 'This method is not allowed for this endpoint.'], 405);
        });

        $this->renderable(function (ModelNotFoundException $e, $request) {
            return response()->json([
                'status_code' => 404,
                'message' => 'Error',
                'data' => 'Could not find what you were looking for.'], 404);
        });

        $this->renderable(function (\InvalidArgumentException $e, $request) {
            return response()->json([
                'status_code' => 400,
                'message' => 'Error',
                'data' => 'You provided some invalid input value'
            ], 400);
        });

        $this->renderable(function (ValidationException $e, $request) {
            return response()->json([
                'status_code' => 422,
                'message' => 'Error',
                'data' => 'Some data failed validation in the request'], 422);
        });

    }
}

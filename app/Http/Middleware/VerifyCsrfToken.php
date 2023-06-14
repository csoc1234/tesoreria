<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Support\Facades\Log;


class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        //
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @throws \Illuminate\Session\TokenMismatchException
     */
    public function handle($request, Closure $next)
    {
        Log::info($request);
        Log::info('VerifyCsrfToken');

        if (
            $this->isReading($request) ||
            $this->runningUnitTests() ||
            $this->inExceptArray($request) ||
            $this->tokensMatch($request)
        ) {
            return $this->addCookieToResponse($request, $next($request));
        }

        $msg = "El token CSRF se venció, debe ingresar nuevamente. ";

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $msg
            ], 419);
            //return route('login');
        }

        throw new \Exception($msg, 419);



        //echo "<script>alert('.$msg.')</script>";
        //  return redirect('/');
        //   $msg .= "acto seguido debe intentar ingresar nuevamente";
      //  throw new \Exception($msg, 419); //TokenMismatchException;
        //throw new TokenMismatchException;
    }

    /*
        https://blog.pusher.com/csrf-laravel-verifycsrftoken/
        https://stackoverflow.com/questions/31223189/in-laravel-5-how-to-disable-verifycsrftoken-middleware-for-specific-route
        https://appdividend.com/2022/01/23/laravel-middleware/
    */
}

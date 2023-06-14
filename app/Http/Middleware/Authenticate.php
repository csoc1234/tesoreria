<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        // dd($request);

        if (!$request->expectsJson()) {
            return route('login');
        } else {
            $data = [];
            $data['message'] = 'Error: la sesión se ha cerrado por inactividad. ';
            $data['message'] .= 'Debe ingresar de nuevo';
            // $data['message'] = $ex->getMessage();

            throw new HttpResponseException(response()->json($data, 408));
            //return response()->json($data, 401);
        }
    }
}

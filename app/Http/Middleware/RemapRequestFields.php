<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RemapRequestFields
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Define your mappings here
        $mappings = [
            'forms.0.answer' => 'user_type', // Assuming 'different_user_type' is the incoming field name
            'forms.1.answer' => 'user_tgid',
            'forms.2.answer' => 'user_nickname',
            'forms.3.answer' => 'country',
            'forms.4.answer' => 'wallet',
        ];

        // Iterate over the mappings and replace the incoming data with the expected field names
        foreach ($mappings as $incomingField => $expectedField) {
            if ($request->has($incomingField)) {
                $request->request->set($expectedField, $request->$incomingField);
                $request->request->remove($incomingField); // Optional: remove the original field
            }
        }

        return $next($request);
    }
}

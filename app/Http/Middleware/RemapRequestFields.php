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
        $endpointMappings = [
            'api/create_deposit_account' => [
                'forms.0.answer' => 'user_tgid',
                'forms.1.answer' => 'plan',
            ],
            'api/update_wallet' => [
                'forms.0.answer' => 'user_tgid',
                'forms.1.answer' => 'wallet',
            ],
            'api/user' => [
                'forms.0.answer' => 'user_type',
                'forms.1.answer' => 'user_tgid',
                'forms.2.answer' => 'user_nickname',
                'forms.3.answer' => 'country',
                'forms.4.answer' => 'wallet',
            ],
        ];

        // Determine the requested endpoint
        $path = $request->path();
        var_dump($path);

        // Apply mappings if the endpoint has defined mappings
        if (array_key_exists($path, $endpointMappings)) {
            $mappings = $endpointMappings[$path];

            foreach ($mappings as $incomingField => $expectedField) {
                if ($request->has($incomingField)) {
                    $request->request->set($expectedField, $request->$incomingField);
                    $request->request->remove($incomingField); // Optional: remove the original field
                }
            }
        }

        return $next($request);
    }
}

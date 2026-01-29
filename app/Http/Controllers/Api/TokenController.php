<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TokenController extends Controller
{
    /**
     * Generate API token for authenticated user (SPA mode)
     */
    public function generateToken(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Revoke all existing tokens for this user
        $user->tokens()->delete();
        
        // Create new token
        $token = $user->createToken('spa-token')->plainTextToken;
        
        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ]);
    }
    
    /**
     * Revoke current token (logout)
     */
    public function revokeToken(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Token revoked successfully'
        ]);
    }
}

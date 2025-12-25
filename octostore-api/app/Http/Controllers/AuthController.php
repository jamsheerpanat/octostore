<?php

namespace App\Http\Controllers;

use App\Models\SuperAdmin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

// Minimal swagger annotations
/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="OctoStore API",
 *      description="Multi-tenant API for OctoStore"
 * )
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="Login user (master or tenant detected automatically)",
     *     tags={"Auth"},
     *     @OA\RequestBody(@OA\JsonContent(
     *         @OA\Property(property="email", type="string", example="admin@example.com"),
     *         @OA\Property(property="password", type="string", example="password")
     *     )),
     *     @OA\Response(response=200, description="Token generated")
     * )
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $tenant = app()->bound('tenant') ? app('tenant') : null;

        if ($tenant) {
            // Tenant Login
            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                throw ValidationException::withMessages([
                    'email' => ['Invalid credentials for this store.'],
                ]);
            }

            $token = $user->createToken('tenant_token')->plainTextToken;
            return response()->json([
                'token' => $token,
                'user' => $user,
                'role' => $user->getRoleNames(),
                'context' => 'tenant',
                'tenant' => $tenant->name
            ]);

        } else {
            // Master Admin Login
            // Must guard this so regular users can't login as superadmin by visiting IP directly if we don't want them to.
            // But for now, if no tenant is resolved, we assume system context.

            $admin = SuperAdmin::where('email', $request->email)->first();

            if (!$admin || !Hash::check($request->password, $admin->password)) {
                throw ValidationException::withMessages([
                    'email' => ['Invalid credentials.'],
                ]);
            }

            $token = $admin->createToken('admin_token')->plainTextToken;
            return response()->json([
                'token' => $token,
                'user' => $admin,
                'role' => $admin->getRoleNames(),
                'context' => 'super_admin'
            ]);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
            'roles' => $request->user()->getRoleNames(),
            'permissions' => $request->user()->getAllPermissions()->pluck('name'),
        ]);
    }
}

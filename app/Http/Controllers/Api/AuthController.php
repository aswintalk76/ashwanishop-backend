<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct(private CartService $cartService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'phone' => $request->phone,
        ]);

        $user->assignRole('user');
        $user->sendEmailVerificationNotification();

        $this->cartService->mergeGuestCart($user, $request->header('X-Guest-Token'));

        $token = $user->createToken('auth', ['*'], now()->addDays(7));
        $refresh = $user->createToken('refresh', ['refresh'], now()->addDays(30));

        return response()->json([
            'user' => $user,
            'token' => $token->plainTextToken,
            'refresh_token' => $refresh->plainTextToken,
            'message' => 'Registration successful. Please verify your email.',
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = Auth::user();

        if (! $user->is_active) {
            return response()->json(['message' => 'Account deactivated'], 403);
        }

        $this->cartService->mergeGuestCart($user, $request->header('X-Guest-Token'));

        $token = $user->createToken('auth', ['*'], now()->addDays(7));
        $refresh = $user->createToken('refresh', ['refresh'], now()->addDays(30));

        return response()->json([
            'user' => $user->load('roles'),
            'token' => $token->plainTextToken,
            'refresh_token' => $refresh->plainTextToken,
        ]);
    }

    public function adminLogin(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = Auth::user();

        if (! $user->isAdmin()) {
            Auth::logout();

            return response()->json(['message' => 'Admin access only'], 403);
        }

        $token = $user->createToken('admin', ['admin'], now()->addDays(1));

        return response()->json([
            'user' => $user,
            'token' => $token->plainTextToken,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }

    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->tokens()->where('name', 'auth')->delete();
        $token = $user->createToken('auth', ['*'], now()->addDays(7));

        return response()->json(['token' => $token->plainTextToken]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $request->user()->load('roles')]);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update($request->validated());

        return response()->json(['user' => $user]);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink($request->only('email'));

        return response()->json([
            'message' => $status === Password::RESET_LINK_SENT
                ? 'Reset link sent to your email'
                : 'Unable to send reset link',
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
                event(new PasswordReset($user));
            }
        );

        return response()->json([
            'message' => $status === Password::PASSWORD_RESET
                ? 'Password reset successful'
                : 'Password reset failed',
        ], $status === Password::PASSWORD_RESET ? 200 : 400);
    }

    public function verifyEmail(Request $request, $id, $hash): JsonResponse
    {
        $user = User::findOrFail($id);

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return response()->json(['message' => 'Invalid verification link'], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified']);
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        return response()->json(['message' => 'Email verified successfully']);
    }

    public function resendVerification(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified']);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification email sent']);
    }
}

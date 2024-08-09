<?php

namespace App\Http\Controllers\api;

use App\Common\StatusConst;
use App\Http\Controllers\Controller;
use App\Http\Requests\user\auth\ForgotPasswordRequest;
use App\Http\Requests\user\auth\LoginRequest;
use App\Http\Requests\user\auth\RegisterRequest;
use App\Http\Requests\user\auth\ResetPasswordRequest;
use App\Http\Requests\user\StoreUserRequest;
use App\Http\Resources\UserBasicResource;
use App\Mail\ResetPasswordMail;
use App\Models\ResetPassword;
use App\Models\User;
use App\Notifications\ResetPasswordNoti;
use App\Repositories\ResetPasswordRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;


class AuthController extends BaseApiController
{
    //

    public function __construct(
        protected UserRepository          $userRepository,
        protected ResetPasswordRepository $resetPasswordRepository)
    {
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (Auth::attempt($data)) {
            $user = auth()->user();

            $token = $user->createToken('authToken');

            // Token life to end of day. Must log in if pass to new day
            $endTimeOfToken = Carbon::now()->addHours(12);
            $token->accessToken->expires_at = $endTimeOfToken;
            $token->accessToken->save();

            $result = UserBasicResource::make($user);
            $data = [
                'access_token' => $token->plainTextToken,
                'user' => $result,
            ];

            return $this->sendResponse($data, __('common.request_successful'));
        }

        return $this->sendError('Thông tin đăng nhập không chính xác');
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();
        $this->userRepository->create([
            'name' => $data['name'],
            'username' => $data['username'],
            'password' => Hash::make($data['password']),
            'email' => $data['email'],
        ]);

        return $this->sendResponse($data, __('common.register_successful'));

    }
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();
        return $this->sendResponse($user, __('common.logout_successful'));
    }

    public function sendMail(Request $request): JsonResponse
    {
        $user = User::where('email', $request->email)->firstOrFail();

        $mailData = ['email' => $user->email, 'token' => rand(100000, 1000000)];

        $passwordReset = ResetPassword::updateOrCreate([
            'email' => $mailData['email'],
        ], [
            'token' => $mailData['token'],
        ]);

        if ($passwordReset) {
           Mail::to($mailData['email'])->send(new ResetPasswordMail($mailData));
        }

        return $this->sendResponse($passwordReset, 'check mail');
    }
    public function reset(ForgotPasswordRequest $request): JsonResponse
    {
        $data = $request->validated();
        $passwordReset = ResetPassword::
         where('token', $data['code'])
        ->where('email', $data['email'])
        ->firstOrFail();
//        if (Carbon::parse($passwordReset->updated_at)->addMinutes(720)->isPast()) {
//            $passwordReset->delete();
//
//            return response()->json([
//                'message' => 'This password reset token is invalid.',
//            ], 422);
//        }
        $user = User::where('email', $passwordReset->email)->firstOrFail();
        $updatePasswordUser = $user->update(['password' => $data['new_password']]);
        $passwordReset->delete();

        return response()->json([
            'success' => $updatePasswordUser,
        ]);
    }

}

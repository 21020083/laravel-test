<?php

namespace App\Http\Controllers\api;

use App\Common\StatusConst;
use App\Http\Controllers\Controller;
use App\Http\Requests\auth\ForgotPasswordRequest;
use App\Http\Requests\auth\LoginRequest;
use App\Http\Requests\auth\ResetPasswordRequest;
use App\Http\Resources\UserBasicResource;
use App\Models\User;
use App\Repositories\ResetPasswordRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();
        return $this->sendResponse(null, __('common.logout_successful'));
    }
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $this->userRepository->findByCondition([
            'username' => $data['username']
        ])->first();
        if ($user) {
            if(!$user->tokens()->where('name', 'reset_password')->exists()){
                $user->createToken('reset_password')->plainTextToken;
            }
            $result = ['reset_password_token' => $user->tokens()];
            return $this->sendResponse($user->tokens, __('common.forgot_password_successful'));
        }

        return $this->sendError(__('common.forgot_password_failed'));
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = $this->resetPasswordRepository->findByCondition([
            'email' => $data['email']
        ])->first();

        if (!$user) {
            return $this->sendError(__('common.forgot_password_failed'));
        }

        $this->userRepository->update($user->id, [
            'password' => Hash::make($data['new_password'])
        ]);

        $this->resetPasswordRepository->deleteBy(
            [
                'token' => $data['token']
            ]
        );

        return $this->sendResponse(null, __('common.forgot_password_successful'));
    }


}

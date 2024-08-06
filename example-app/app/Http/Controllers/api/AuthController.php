<?php

namespace App\Http\Controllers\api;

use App\Common\StatusConst;
use App\Http\Controllers\Controller;
use App\Http\Requests\auth\LoginRequest;
use App\Http\Resources\UserBasicResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class AuthController extends BaseApiController
{
    //
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
}

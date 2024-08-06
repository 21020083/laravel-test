<?php

namespace App\Http\Controllers\api;

use App\Http\Requests\user\ShowUserRequest;
use app\Http\Requests\user\StoreUserRequest;
use app\Http\Requests\user\UpdateUserRequest;
use App\Http\Requests\user\UserSearchRequest;
use App\Http\Resources\UserBasicResource;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;

class UserController extends BaseApiController
{
    public function __construct(protected UserRepository  $userRepository)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(UserSearchRequest $request): \Illuminate\Http\JsonResponse
    {
        //
        $dataSearch = $request->validated();

        $users = $this->userRepository->getListByConditions($dataSearch);
        $result = UserBasicResource::collection($users);

        return $this->sendPaginationResponse($users, $result);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request): \Illuminate\Http\JsonResponse
    {
        //
        $data = $request->validated();

        $user = $this->userRepository->create($data);
        $result = UserBasicResource::make($user);

        return $this->sendResponse($result);
    }

    /**
     * Display the specified resource.
     */
    public function show(ShowUserRequest $request): \Illuminate\Http\JsonResponse
    {
        //
        $user = $this->userRepository->find($request->get('user_id'));
        $result = UserBasicResource::make($user);

        return $this->sendResponse($result);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request): \Illuminate\Http\JsonResponse
    {
        //
        $data = $request->validated();

        $user = $this->userRepository->update($data['user_id'], $data);
        $result = UserBasicResource::make($user);

        return $this->sendResponse($result);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request): \Illuminate\Http\JsonResponse
    {
        //
        $check = $this->userRepository->delete($request->get('user_id'));
        if (!$check) {
            return $this->sendError(__('common.delete_failed'));
        }

        return $this->sendResponse(null, __('common.delete_successful'));
    }
}

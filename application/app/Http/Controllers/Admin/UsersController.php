<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\UserAdminService;

class UsersController extends Controller
{
    public function usersList(UserAdminService $uaService)
    {
        return [
            "success" => true,
            "data" => $uaService->getUserList()->toResourceCollection(UserResource::class),
        ];
    }
}

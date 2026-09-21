<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserAdminService;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    public function usersList(UserAdminService $uaService, Request $request)
    {
        return $uaService->getUserList(
            $request->input('perPage', 10),
            $request->all()
        )->toResourceCollection();
    }

    public function ban(UserAdminService $uaService, User $userToBan)
    {
        $uaService->banUser(
            auth()->user(),
            $userToBan
        );
        return ["success" => true];
    }
}

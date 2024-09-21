<?php

namespace App\Repositories;

use App\Models\UserMysql;
use App\Facades\UserFirebase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Services\LocalStorageService;
use App\Interfaces\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    protected $LocalStorageService;
    public function __construct(LocalStorageService $LocalStorageService)
    {
        $this->LocalStorageService = $LocalStorageService;
    }

    public function getAllUsers()
    {
        return UserFirebase::all();
    }

    public function createUser(array $data)
    {
        DB::beginTransaction();
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        $localPath = $this->LocalStorageService->storeImageLocally($data['photo'], 'images/users', uniqid() . '.jpg');
        $data['photo_url'] = $localPath;
        $firebaseUserId = UserFirebase::create($data);
        $userMysql = UserMysql::create($data);
        $userMysql->id = $firebaseUserId;
        $userMysql->save();
        DB::commit();
        return [
            'mysql' => $userMysql,
            'firebase' => $firebaseUserId,
        ];
    }


    public function getUserById(string $id)
    {
        return UserFirebase::find($id);
    }

    public function updateUser(string $id, array $data): ?array
    {
        DB::beginTransaction();
        $userMysql = UserMysql::find($id);
        if ($userMysql) {
            $userMysql->update($data);
        }
        $userFirebase = UserFirebase::find($id);
        if ($userFirebase) {
            UserFirebase::update($id, $data);
        }
        DB::commit();
        return [
            'firebase' => UserFirebase::find($id),
        ];
    }

    public function deleteUser(string $id): bool
    {
        DB::beginTransaction();
        $deletedMysql = UserMysql::destroy($id);
        $deletedFirebase = UserFirebase::delete($id);
        DB::commit();
        return $deletedMysql && $deletedFirebase;
    }
}

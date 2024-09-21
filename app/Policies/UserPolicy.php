<?php

namespace App\Policies;

use App\Models\UserMysql;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(UserMysql $user)
    {
        return $user->fonction === 'ADMIN' || ($user->fonction === 'MANAGER');
    }

    public function view(UserMysql $user, UserMysql $model)
    {
        return $user->fonction === 'ADMIN' || ($user->fonction === 'MANAGER' && $model->fonction !== 'ADMIN');
    }

    public function create(UserMysql $user)
    {
        $fonction = $user->fonction;
        return $this->canCreateUser($user, $fonction);
    }

    public function canCreateUser(UserMysql $user, string $fonction)
    {
        if ($user->fonction === 'ADMIN') {
            return in_array($fonction, ['ADMIN', 'Coach', 'MANAGER', 'CM']);
        } elseif ($user->fonction === 'MANAGER') {
            return in_array($fonction, ['Coach', 'MANAGER', 'CM']);
        }
        return false;
    }

    public function update(UserMysql $user, UserMysql $model)
    {
        return $user->fonction === 'ADMIN' || ($user->fonction === 'MANAGER' && $model->fonction !== 'ADMIN');
    }

    public function delete(UserMysql $user)
    {
        return $user->fonction === 'ADMIN';
    }
}
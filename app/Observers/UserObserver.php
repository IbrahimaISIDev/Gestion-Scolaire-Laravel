<?php

namespace App\Observers;

use App\Events\UserCreated;
use App\Models\UserMysql;
use App\Facades\UserFirebase;

class UserObserver
{
    public function created(UserMysql $userMysql)
    {
        $userFirebase = UserFirebase::find($userMysql->id);
        event(new UserCreated($userMysql, $userFirebase));
    }
}

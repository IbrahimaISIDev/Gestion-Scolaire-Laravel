<?php

namespace App\Providers;

use App\Models\UserMysql;
use App\Models\UserFirebase;
use App\Services\UserService;
use App\Observers\UserObserver;
use App\Models\PromotionFirebase;
use App\Services\PromotionService;
use App\Models\ReferentielFirebase;
use Illuminate\Database\Connection;
use App\Repositories\AuthRepository;
use App\Repositories\UserRepository;
use App\Services\ReferentielService;
use Illuminate\Support\ServiceProvider;
use App\Interfaces\UserServiceInterface;
use App\Interfaces\UserFirebaseInterface;
use App\Repositories\PromotionRepository;
use App\Interfaces\AuthRepositoryInterface;
use App\Interfaces\UserRepositoryInterface;
use App\Repositories\ReferentielRepository;
use App\Interfaces\PromotionServiceInterface;
use App\Interfaces\PromotionFirebaseInterface;
use App\Interfaces\ReferentielServiceInterface;
use App\Interfaces\PromotionRepositoryInterface;
use App\Interfaces\ReferentielFirebaseInterface;
use App\Interfaces\ReferentielRepositoryInterface;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(UserServiceInterface::class, UserService::class);
        $this->app->bind(UserFirebaseInterface::class, UserFirebase::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(AuthRepositoryInterface::class, AuthRepository::class);
        $this->app->bind('user.firebase', function ($app) {
            return $app->make(UserFirebaseInterface::class);
        });
        $this->app->bind('promotion', function ($app) {
            return new PromotionFirebase();
        });

        $this->app->bind(PromotionRepositoryInterface::class, PromotionRepository::class);
        $this->app->bind(PromotionFirebaseInterface::class, PromotionFirebase::class);
        $this->app->bind(PromotionServiceInterface::class, PromotionService::class);
        $this->app->bind('promotion.facade', function ($app) {
            return $app->make(PromotionFirebaseInterface::class);
        });
        
        $this->app->bind(ReferentielRepositoryInterface::class, ReferentielRepository::class);
        $this->app->bind(ReferentielServiceInterface::class, ReferentielService::class);
        $this->app->bind(ReferentielFirebaseInterface::class, ReferentielFirebase::class);
        $this->app->bind('referentiel.facade', function ($app) {
            return $app->make(ReferentielFirebaseInterface::class);
        });
    }
    public function boot()
    {
        UserMysql::observe(UserObserver::class);
        Connection::resolverFor('firebase', function ($connection, $database, $prefix, $config) {
            return $config['credentials'];
        });
    }
}

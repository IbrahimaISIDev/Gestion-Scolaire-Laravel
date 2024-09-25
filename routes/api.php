<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\ReferentielController;

Route::prefix('v1')->group(function () {

    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    Route::apiResource('/users', UserController::class);

    Route::middleware(['auth:api', 'blacklisted'])->group(function () {

        Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api');

        Route::get('/auth/{provider}', [AuthController::class, 'redirectToProvider']);
        Route::get('/auth/{provider}/callback', [AuthController::class, 'handleProviderCallback']);


        Route::apiResource('referentiels', ReferentielController::class);
        
        Route::get('referentiels', [ReferentielController::class, 'index']);
        Route::post('referentiels', [ReferentielController::class, 'store']);
        Route::get('referentiels/{id}', [ReferentielController::class, 'show']);
        Route::patch('referentiels/{id}', [ReferentielController::class, 'update']);
        Route::delete('/v1/referentiels/{id}', [ReferentielController::class, 'destroy']);
        // Route::delete('referentiels/{id}', [ReferentielController::class, 'delete']);
        Route::get('archive/referentiels', [ReferentielController::class, 'archived']);
        Route::get('referentiels/export', [ReferentielController::class, 'export']);


        Route::apiResource('promotions', PromotionController::class);
        Route::get('promotions/encours', [PromotionController::class, 'getPromotionEncours']);
        Route::get('promotions/{id}/stats', [PromotionController::class, 'getStats']);
        Route::patch('promotions/{id}/etat', [PromotionController::class, 'changeStatus']);
        Route::patch('promotions/{id}/cloturer', [PromotionController::class, 'cloturer']);
        Route::patch('promotions/{id}/referentiels', [PromotionController::class, 'updateReferentiels']);
        Route::get('/promotions/export/{format}', [PromotionController::class, 'export']);
    });
});

<?php

namespace App\Services;

use App\Interfaces\AuthServiceInterface;
use App\Interfaces\AuthRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class AuthService implements AuthServiceInterface
{
    protected $authRepository;

    public function __construct(AuthRepositoryInterface $authRepository)
    {
        $this->authRepository = $authRepository;
    }

    public function login(array $credentials)
    {
        $user = $this->authRepository->findUserByCredentials($credentials);
        if (!$user) {
            return null;
        }
        return $this->generateTokens($user);
    }

    public function refresh(string $refreshToken)
    {
        $user = $this->authRepository->findUserByRefreshToken($refreshToken);
        if (!$user) {
            return null;
        }
        $this->authRepository->blacklistToken($refreshToken, 'refresh');
        $this->revokeTokens($user);
        return $this->generateTokens($user);
    }

    public function logout(string $token)
    {
        $this->authRepository->blacklistToken($token, 'access');
        Auth::logout();
        return true;
    }

    public function revokeTokens($user) {}

    public function generateTokens($user)
    {
        $tokenResult = $user->createToken('API Token');
        return [
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_in' => 3600, // Example expiry time
        ];
    }

    // public function generateTokens($user)
    // {
    //     $tokenResult = $user->createToken('API Token');
    //     return [
    //         'access_token' => $tokenResult->accessToken,
    //         'token_type' => 'Bearer',
    //         'expires_in' => $tokenResult->token->expires_at->diffInSeconds(now()),
    //     ];
    // }
}

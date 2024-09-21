<?php

namespace App\Jobs;

use App\Models\UserMysql;
use App\Facades\UserFirebase;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class UploadProfilePictureJob implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    protected $userMysql;
    protected $userFirebase;

    public function __construct(UserMysql $userMysql, $userFirebase)
    {
        $this->userMysql = $userMysql;
        $this->userFirebase = $userFirebase;
    }

    public function handle()
    {
        try {
            // Récupérer le contenu de l'image stockée localement
            $imageContent = Storage::disk('local')->get($this->userMysql->photo_url);

            // Télécharger l'image vers le cloud
            $cloudUrl = app('App\Services\CloudStorageService')->uploadImage($imageContent, 'images/users');

            // Mettre à jour l'URL dans les bases de données
            $this->userMysql->update(['photo_url' => $cloudUrl]);
            $userFirebaseInstance = UserFirebase::find($this->userFirebase['id']);
            if ($userFirebaseInstance) {
                $userFirebaseInstance->update(['photo_url' => $cloudUrl]);
            } else {
                Log::error('Utilisateur Firebase non trouvé pour ID : ' . $this->userFirebase['id']);
            }

            Log::info('Image téléchargée avec succès sur le cloud pour l\'utilisateur ID : ' . $this->userMysql->id);
        } catch (\Exception $e) {
            Log::error('Erreur lors du téléchargement de l\'image vers le cloud : ' . $e->getMessage());
        }
    }
}

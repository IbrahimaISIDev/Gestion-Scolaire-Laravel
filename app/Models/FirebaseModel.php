<?php

namespace App\Models;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Database;

abstract class FirebaseModel
{
    protected $database;
    protected $reference;
    protected $path;

    public function __construct()
    {
        if (!$this->path) {
            throw new \Exception('Le chemin Firebase (path) doit être défini dans la classe enfant.');
        }

        $this->database = $this->getDatabase();
        $this->reference = $this->database->getReference($this->path);
    }


    protected function getDatabase(): Database
    {
        $factory = (new Factory())
            ->withDatabaseUri(config('database.connections.firebase.database'))
            ->withServiceAccount(config('database.connections.firebase.credentials'));
        return $factory->createDatabase();
    }

    public function all()
    {
        $result = $this->reference->getValue();
        return $result === null ? [] : $result;
    }

    // public function find($id)
    // {
    //     $result = $this->reference->getChild($id)->getValue();
    //     return $result === null ? [] : $result;
    // }

    public function find($id)
    {
        if (is_null($id)) {
            throw new \InvalidArgumentException('L\'ID ne peut pas être nul.');
        }
        $result = $this->reference->getChild($id)->getValue();
        return $result === null ? [] : $result;
    }


    public function create(array $data)
    {
        $reference = $this->reference;
        $existingUsers = $reference->orderByKey()->getValue();
        $nextId = 1;
        if ($existingUsers) {
            $keys = array_keys($existingUsers);
            $keys = array_map('intval', $keys);
            $nextId = max($keys) + 1;
        }
        $newRef = $reference->getChild((string) $nextId);
        $newRef->set($data);
        return $nextId;
    }


    public function update($id, array $data)
    {
        $this->reference->getChild($id)->update($data);
        return $id;
    }

    public function delete($id)
    {
        $this->reference->getChild($id)->remove();
        return $id;
    }
}

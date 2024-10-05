<?php
namespace App\Application;
require_once '../Domain/Users/UserEntity.php'; use App\Domain\Users\UserEntity;

class AdminService {

    /** @var UserEntity */
    public $user;

    public function __construct()
    {
        $this->user = new UserEntity();
    }

    public function addNewProduct()
    {
        if (!$this->user->isAdmin) return;
    }
}

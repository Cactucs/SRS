<?php

namespace App\Model\User;

use Kdyby\Doctrine\EntityRepository;

class UserRepository extends EntityRepository
{
    public function findUserById($id) {
        return $this->findOneBy(['id' => $id]);
    }

    public function findUserBySkautISUserIdName($skautISUserId) {
        return $this->findOneBy(['skautISUserId' => $skautISUserId]);
    }

    public function variableSymbolExists($variableSymbol) {
        return $this->findOneBy(['variableSymbol' => $variableSymbol]) !== null;
    }
}
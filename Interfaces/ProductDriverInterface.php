<?php

namespace App\Modules\Shop\Interfaces;

interface ProductDriverInterface
{
    /**
     * Применить товар на сервере
     */
    public function apply(array $data): bool;
    
    /**
     * Отменить применение товара (например, при возврате)
     */
    public function revoke(array $data): bool;
}
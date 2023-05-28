<?php

declare(strict_types=1);

use Yii\User\Tests\App\Controller\SiteController;
use Yiisoft\Router\Route;

return [
    Route::get('/')->action([SiteController::class, 'index'])->name('home'),
];

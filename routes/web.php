<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use App\jobs\SendVerificationEmailJob;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\SitemapController;

Route::get('/', function () {
    return view('welcome');
});

// Dynamic XML sitemaps — exposed via Nginx under https://startyourstory.in/
// /sitemap.xml is a sitemap index referencing the child sitemaps below.
Route::get('/sitemap.xml',          [SitemapController::class, 'index']);
Route::get('/sitemaps/static.xml',  [SitemapController::class, 'static']);
Route::get('/sitemaps/blogs.xml',   [SitemapController::class, 'blogs']);




Route::get(
    '/email/verify/{id}/{hash}',
    [UserController::class, 'verify']
)
    ->middleware('signed')
    ->name('verification.verify');

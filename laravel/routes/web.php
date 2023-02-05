<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group(['prefix' => '/sitemap_ssl'], function () {
    Route::get('/sitemap.xml', 'SitemapsController@getIndex');
    Route::get('/sitemap-{type}_{number}.xml', 'SitemapsController@getLinksBySitemapTypeAndNumber');
    Route::get('/sitemap-{type}.xml', 'SitemapsController@getLinksBySitemapTypeAndNumber');
});

// Route::group(['prefix' => '/rss_ssl'], function () {
//     Route::get('/rss.xml', 'RSSController@getRSS');
// });

<?php

/**
 * Here is where you can register web routes for your application. These
 * routes are loaded by the RouteServiceProvider within a group which
 * contains the "web" middleware group. Now create something great!
 *
 * @see \Rogue\Providers\RouteServiceProvider
 */

// Homepage & FAQ
Route::view('/', 'pages.home')->middleware('guest')->name('login');
Route::view('faq', 'pages.faq');

// Authentication
Route::get('login', 'AuthController@getLogin');
Route::get('logout', 'AuthController@getLogout');

// Server-rendered routes:
// @TODO: These should be updated to client-side routes!
Route::resource('actions', 'ActionsController', ['except' => 'show']);
Route::get('campaigns/{id}/actions/create', 'ActionsController@create');
Route::resource('campaigns', 'CampaignsController', ['except' => ['index', 'show']]);
Route::resource('group-types', 'GroupTypesController', ['except' => ['index', 'show']]);
Route::get('group-types/{id}/groups/create', 'GroupsController@create');
Route::resource('groups', 'GroupsController', ['except' => ['create', 'index', 'show']]);

// Client-side routes:
Route::middleware(['auth', 'role:staff,admin'])->group(function () {
    // Actions
    Route::view('actions/{id}', 'app');

    // Campaigns
    Route::view('campaigns', 'app');
    Route::view('campaigns/{id}', 'app');
    Route::view('campaigns/{id}/{status}', 'app');

    // Groups
    Route::view('groups', 'app');
    Route::view('groups/{id}', 'app');
    Route::view('groups/{id}/posts', 'app');

    // Group Types
    Route::view('group-types', 'app');
    Route::view('group-types/{id}', 'app');

    // Posts
    Route::view('posts', 'app');
    Route::view('posts/{id}', 'app')->name('posts.show');

    // Schools
    Route::view('schools/{id}', 'app')->name('schools.show');

    // Signups
    Route::view('signups', 'app');
    Route::view('signups/{id}', 'app')->name('signups.show');

    // Users
    Route::view('users', 'app')->name('users.index');
    Route::view('users/{id}', 'app')->name('users.show');
    Route::view('users/{id}/posts', 'app')->name('users.show');
    Route::view('users/{id}/referrals', 'app')->name('users.show');
});

// Admin image routes:
Route::post('images/{postId}', 'ImagesController@update');
Route::get('originals/{post}', 'OriginalsController@show');

// Redirects for old routes:
Route::get('campaign-ids', 'CampaignsController@redirect');
Route::get('campaign-ids/{id}', 'CampaignsController@redirect');

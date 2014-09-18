<?php
Route::get('/', 'HomeController@index');

/* Admin Panel */
Route::get('/admin', 'AdminController@index');

//Manage Content
Route::get('/admin/manage/{content_type}', 'AdminController@manage');
Route::get('/admin/create/{content_type}', 'AdminController@getCreate');
Route::get('/admin/edit/{content_type}/{content_id}', 'AdminController@getEdit');
Route::get('/admin/delete/{content_type}/{content_id}', 'AdminController@delete');

Route::post('/admin/create', 'AdminController@postCreate');
Route::post('/admin/edit', 'AdminController@postEdit');

Route::get('/admin/flag/status/{flag_id}/{status}', 'AdminController@changeStatus');
Route::get('/admin/access/{username}', 'AdminController@accessLog');

//Flush Cache
Route::get('/admin/cache/flush', 'AdminController@flush');

/* User Controller */
Route::get('/user/profile', array('before'  => 'auth',  'uses'  => 'UsersController@self'));
Route::get('/user/settings', 'UserController@settings');
Route::get('/user/{username}', 'UsersController@profile');
Route::get('/user/{user_id}/posts', 'UsersController@posts');
Route::get('/user/{user_id}/threads', 'UsersController@threads');
Route::get('/user/{user_id}/{rating_way}/{rating_type}', 'UsersController@ratings');

//User settings
Route::get('/user/settings', array('before' => 'auth', 'uses' => 'UsersController@settings'));
Route::post('/user/upload/profile', array('before' => 'auth', 'uses' => 'UsersController@uploadProfile'));
Route::post('/user/settings/save', array('before' => 'auth', 'uses' => 'UsersController@settingsSave'));

Route::post('/login',   array('before'  => 'guest', 'uses'  => 'UsersController@login'));
Route::post('/register',  array('before' => 'guest',  'uses'  => 'UsersController@register'));
Route::get('/login',   array('before'  => 'guest', 'uses'  => 'UsersController@showLogin'));
Route::get('/register',  array('before'  => 'guest', 'uses'  => 'UsersController@showRegister'));
Route::get('/logout',   array('before'  => 'auth',  'uses'  => 'UsersController@logout'));

/*	Posts	*/
Route::post('/posts/submit', array('before'  => 'auth',  'uses'  => 'PostsController@submit'));
Route::post('/posts/update', array('before'  => 'auth',  'uses'  => 'PostsController@update'));

/*	Threads	*/
Route::get('/thread/create/{forum_id}', array('before'	=>	'auth', 'uses'	=>	'ThreadsController@create'));
Route::post('/thread/create', array('before'	=>	'auth', 'uses'	=>	'ThreadsController@submitCreate'));
Route::get('/thread/delete/{thread_id}', array('before'	=>	'auth', 'uses'	=>	'ThreadsController@delete'));

//AJAX calls
Route::get('/posts/delete/{post_id}', array('before'  => 'auth',  'uses'  => 'PostsController@delete'));
Route::get('/posts/get/{post_id}', array('before'  => 'auth',  'uses'  => 'PostsController@get'));

//Standalone Pages
Route::get('/posts/update/{post_id}', array('before'  => 'auth',  'uses'  => 'PostsController@getUpdatePage'));
Route::get('/posts/delete/page/{post_id}', array('before'  => 'auth',  'uses'  => 'PostsController@getDeletePage'));
Route::get('/posts/reply/{post_id}', array('before'  => 'auth',  'uses'  => 'PostsController@getReplyPage'));

//Reports
Route::get('/posts/report/{post_id}/{page_number}', array('before'  => 'auth',  'uses'  => 'PostsController@getReportPage'));
Route::post('/posts/report/', array('before'  => 'auth',  'uses'  => 'PostsController@postReport'));

/*	Votes	*/
Route::post('/votes/vote', array('before'	=>	'auth', 'uses'	=>	'VotesController@postVote'));
Route::get('/votes/vote/', array('before'	=>	'auth', 'uses'	=>	'VotesController@getVote'));

/*	Ratings	*/
Route::post('/ratings/rate', array('before'  => 'auth',  'uses'  => 'RatingsController@rate'));

/*	Keychain Layer	*/
Route::get('/keychain/exists/{username}', 'KeychainController@exists');
Route::get('/keychain/message/{key_id}', 'KeychainController@message');
Route::get('/keychain/sync/push/users', 'KeychainController@users');
Route::get('/keychain/sync/pull/ratings',  array('before'   => 'auth',  'uses'   => 'KeychainController@pullRatings'));
Route::post('/keychain/sync/push/ratings',  array('before'   => 'auth',  'uses'   => 'KeychainController@pushRatings'));
Route::get('/keychain/sync/push/ratings', 'KeychainController@listRatings');
Route::get('/keychain/ratings',  array('before'   => 'auth',  'uses'   => 'KeychainController@myRatings'));
Route::get('/keychain/ratings/download', array('before' => 'auth', 'uses' => 'KeychainController@downloadRatings'));

Route::get('/keychain/posts/get/{thread_id}/{posts_num}', 'PostsController@listPosts');

/* Forum Structure */
Route::get('/{forum_id}/{forum_slug}', 'ForumsController@index');
Route::get('/{forum_id}/{forum_slug}/{thread_id}/{thread_slug}', 'ThreadsController@index');
Route::get('/{forum_id}/{forum_slug}/{thread_id}/{thread_slug}/{post_id}/{post_slug}', 'PostsController@index');

<?php

class UsersController extends BaseController {

	public function login() {
	
	$remember = false;
	
	if (Input::get('remember_me') == 'on') $remember = true;
		
	if (Auth::attempt(array('username' => Input::get('username'), 'password' => Input::get('password')), $remember))
	{
		$user = Auth::user();
		$user->last_login = new DateTime();
		
		
		if (Input::get('remember_for') != NULL && Input::get('remember_for') != '') 
			$user->remember_for = Input::get('remember_for');
					
		$user->save();
		
		return Redirect::to(URL::previous());
	}
	else
	{
		return View::make('user.login', array('errors' => array('Wrong username or password')));
	}
	}

	public function register() {

		$validator = User::validate(Input::all());

		if (!$validator->fails()) {

			if (Input::get('wot_register') || GPG::find(Input::get('username'))) {
				if (!Input::get('otcp'))
				{
					$key = GPG::find(Input::get('username'));
					$otcp = "forum.monero:".str_random(40)."\n"; //generate a random password of 40 chars.

					if ($key)
					{
						$key_id = $key->keyid;
					}
					else if (Input::get('key'))
						{
							$key_id = Input::get('key');
						}
					else
						return View::make('user.register', array('errors' => array('Looks like you forgot to input the Key ID.')));

					//Search for the pubkey in all the registers.
					$pubkey = @file_get_contents('http://pgp.mit.edu/pks/lookup?op=get&search=0x'.$key_id); //prevent from file_get_contents from erroring out and screwing up the registration proccess.
					if (str_contains($pubkey, 'No results found'))
					{
						$pubkey = @file_get_contents('https://hkps.pool.sks-keyservers.net/pks/lookup?op=get&search=0x'.$key_id); //prevent from file_get_contents from erroring out and screwing up the registration proccess.
					}
					if (str_contains($pubkey, 'No results found'))
					{
						return View::make('user.register', array('errors' => array('The key you have provided does not exist!')));
					}


					if ($pubkey === false)
						return View::make('user.register', array('errors' => array('The key format you have provided is wrong.'))); //if the server returns something weird, there's a 99% chance that the key is in wrong format.

					putenv("GNUPGHOME=/tmp");
					$gpg = new gnupg();
					$gpg->addencryptkey($gpg->import($pubkey)['fingerprint']);
					$message = $gpg->encrypt($otcp);

					$oldKey = Key::where('key_id', '=', Input::get('key'))->orderBy('created_at')->first();

					if ($oldKey)
						$oldKey->delete();

					$userkey = new key();
					$userkey->key_id = $key_id;
					$userkey->password = Hash::make($otcp);
					$userkey->message = $message;
					$userkey->save();

					return View::make('user.auth', array('input' => Input::all(), 'keyid' => $key_id, 'message' => $message));
				}
				else if (Input::get('otcp'))
					{
						$key = GPG::find(Input::get('username'));

						if ($key)
						{
							$key_id = $key->keyid;
						}
						else if (Input::get('key'))
							{
								$key_id = Input::get('key');
							}

						$key = Key::where('key_id', '=', $key_id)->orderBy('created_at')->first();

						$hash = $key->password;
						if (Hash::check("forum.monero:".Input::get('otcp')."\n", $hash))
						{

							//get the fingerprint for insertion into the table.
							$pubkey = @file_get_contents('http://pgp.mit.edu/pks/lookup?op=get&search=0x'.$key_id);
							if (str_contains($pubkey, 'No results found'))
							{
								$pubkey = @file_get_contents('https://hkps.pool.sks-keyservers.net/pks/lookup?op=get&search=0x'.$key_id);
							}
							if (str_contains($pubkey, 'No results found'))
							{
								return View::make('user.register', array('errors' => array('The key you have provided does not exist!')));
							}

							if ($pubkey === false)
								return View::make('user.register', array('errors' => array('The key format you have provided is wrong.')));


							putenv("GNUPGHOME=/tmp");
							$gpg = new gnupg();

							$key->delete();
							$user = new User();
							$user->password = Hash::make(Input::get('password'));
							$user->username = Input::get('username');
							$user->email = Input::get('email');
							$user->in_wot = 1;
							$user->key_id = $key_id;
							$user->fingerprint = $gpg->import($pubkey)['fingerprint'];
							$user->save();
							$member = \Role::where('name', 'Member')->get()->first();
							$user->roles()->attach($member);
							foreach (Config::get('app.admins') as $config_admin)
							{
								if ($user->username == $config_admin)
								{
									$admin = \Role::where('name', 'Admin')->get()->first();
									$user->roles()->attach($admin);
								}
							}
							Auth::attempt(array('username' => Input::get('username'), 'password' => Input::get('password')), true);
							KeychainController::pullRatings(); //Pulls ratings from the OTC database on registering.
							
							return Redirect::to('/?regSuccess');
						}
						else
							return View::make('user.auth', array('input' => Input::all(), 'message' => $key->message, 'keyid' => $key_id, 'errors' => array('Wrong decrypted message.')));
					}

			}
			else if (!GPG::find(Input::get('username'))) {
					$user = new User();
					$user->password = Hash::make(Input::get('password'));
					$user->username = Input::get('username');
					$user->email = Input::get('email');
					$user->in_wot = 0;
					$user->save();
					$member = \Role::where('name', 'Member')->get()->first();
					$user->roles()->attach($member);
					foreach (Config::get('app.admins') as $config_admin)
					{
						if ($user->username == $config_admin)
						{
							$admin = \Role::where('name', 'Admin')->get()->first();
							$user->roles()->attach($admin);
						}
					}
					Auth::attempt(array('username' => Input::get('username'), 'password' => Input::get('password')), true);
					KeychainController::pullRatings(); //Pulls ratings from the OTC database on registering.
					return Redirect::to('/');
				}
			else {
				return View::make('user.register', array('errors' => array('The person with this username is already registered in the Web of Trust. Please try a different username.')));
			}
		}
		else {
			return View::make('user.register', array('errors' => $validator->messages()->all()));
		}

	}

	public function showLogin() {
		return View::make('user.login');
	}

	public function showRegister() {
		return View::make('user.register');
	}

	public function logout() {
		Auth::logout();
		return Redirect::to('/');
	}

	public function profile($username) {
		$user = User::where('username', $username)->firstOrFail();
		$ratings = $user->rated()->orderBy('created_at', 'desc')->paginate(10);
		if ($user)
			return View::make('user.profile', array('user' => $user, 'ratings' => $ratings));
		else
			return View::make('user.notfound');
	}
	
	public function self() {
		$user = Auth::user();
		$ratings = $user->rated()->orderBy('created_at', 'desc')->paginate(10);
		if ($user)
			return View::make('user.profile', array('user' => $user, 'self' => true, 'ratings' => $ratings));
		else
			return View::make('user.notfound');
	}
	
	public function ratings($user_id, $rating_way, $rating_type) {
		$ratings_pp = Config::get('app.ratings_per_page');
		$user = User::findOrFail($user_id);
		if ($rating_way == 'received')
		{
			if ($rating_type == 'all')
				$ratings = $user->rated()->orderBy('created_at', 'desc')->paginate($ratings_pp);
			else if ($rating_type == 'negative')
				$ratings = $user->rated()->whereRaw('rating < 0')->orderBy('created_at', 'desc')->paginate($ratings_pp);
			else if ($rating_type == 'positive')
				$ratings = $user->rated()->whereRaw('rating > 0')->orderBy('created_at', 'desc')->paginate($ratings_pp);
			else $ratings = $user->rated()->orderBy('created_at', 'desc')->paginate($ratings_pp);
		}
		else
		{
			if ($rating_type == 'all')
				$ratings = $user->ratings()->orderBy('created_at', 'desc')->paginate($ratings_pp);
			else if ($rating_type == 'negative')
				$ratings = $user->ratings()->whereRaw('rating < 0')->orderBy('created_at', 'desc')->paginate($ratings_pp);
			else if ($rating_type == 'positive')
				$ratings = $user->ratings()->whereRaw('rating > 0')->orderBy('created_at', 'desc')->paginate($ratings_pp);
			else $ratings = $user->ratings()->orderBy('created_at', 'desc')->paginate($ratings_pp);
		}
		return View::make('user.ratings', array('ratings' => $ratings, 'user' => $user));
	}
	
	public function threads($user_id) {
		$per_page = Config::get('app.user_threads_per_page');
		
		$user = User::findOrFail($user_id);
		$threads = $user->threads()->paginate($per_page);
		
		return View::make('user.threads', array('threads' => $threads, 'user' => $user));
	}
	
	public function posts($user_id) {
		$per_page = Config::get('app.user_posts_per_page');
		
		$user = User::findOrFail($user_id);
		$posts = $user->posts()->paginate($per_page);
		
		return View::make('user.posts', array('posts' => $posts, 'user' => $user));
	}
	
	public function settings() {
		return View::make('user.settings', array('user' => Auth::user()));
	}
	
	public function settingsSave() {
		
		$user = Auth::user();
		$errors = array();
		
		//update email if email field has been changed.
		if (Input::get('email') != $user->email)
		{
			$check_email = Validator::make(
				array('email' => Input::get('email')),
				array('email' => 'required|email|unique:users')
			);
			//check if email is valid.
			if (!$check_email->fails())
				$user->email = Input::get('email');
			else
				$errors[] = 'Wrong email format or already in use.';
		}
		
		//update Monero Address if not empty or null.
		if (Input::has('monero_address') && Input::get('monero_address') != '')
		{
			if (substr(Input::get('monero_address'), 0, 1) == 4 && strlen(Input::get('monero_address')) > 60)
				$user->monero_address = Input::get('monero_address');
			else
				$errors[] = 'Incorrect Monero address format!';
		}
			
		//update Website if not empty or null.
		if (Input::has('website') && Input::get('website') != '')
			$user->website = Input::get('website');
			
		//update email visibility if not empty or null.
		if (Input::has('email_public') && Input::get('email_public') == 'on')
			$user->email_public = 1;
		else
			$user->email_public = 0;
			
		//check if a password has been entered
		if (Input::has('password') && Input::get('password') != '')
		{
			$check_password = Validator::make(
				array(
					'password' => Input::get('password'),
					'password_confirmation' => Input::get('password_confirmation')
				),
				array(
					'password' => 'required|confirmed'
				)
			);
			if (!$check_password->fails())
				$user->password = Hash::make(Input::get('password'));
			else
				$errors[] = 'Password mistmatch.';
		}
		
		$user->save();
		
		if (sizeof($errors) > 0)
			return Redirect::to(URL::previous())->with('errors', $errors);
		else
			return Redirect::to(URL::previous())->with('messages', array('Profile successfully updated.'));
	}
	
	public function uploadProfile() {
			
			$user = Auth::user();
			
			$mime = Input::file('profile')->getMimeType();

			if (!($mime == "image/jpeg" || $mime == "image/png") || !Input::file('profile')->isValid()) {
				return Redirect::to('/user/settings')->with('errors', array('Wrong image type!'));
			} else {				
				$fileName = $user->username;
				
				$extension = Input::file('profile')->getClientOriginalExtension();

				Input::file('profile')->move('uploads/profile', $fileName.".".$extension);
				Image::make('uploads/profile/'.$fileName.'.'.$extension)->resize(150, 150)->save('uploads/profile/'.$fileName.'.'.$extension);
				Image::make('uploads/profile/'.$fileName.'.'.$extension)->resize(24, 24)->save('uploads/profile/small_'.$fileName.'.'.$extension);

				$user->profile_picture = $fileName.".".$extension;
				$user->save();

				return Redirect::to('/user/settings')->with('messages', array('Picture uploaded!'));
			}
	}

}

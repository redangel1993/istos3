<?php
error_reporting(E_ERROR | E_PARSE);

class DB_Functions
{

	private $db;
	private $con;

	//put your code here
	// constructor
	function __construct()
	{
		require_once 'DB_Connect.php';
		// connecting to database
		$this->db = new DB_Connect();
		$this->con = $this->db->connect();

	}

	// destructor
	function __destruct()
	{

	}


	/**
	 * Storing new user
	 * returns user details
	 */
	public function storeUser($email, $password, $name)
	{
		$response = array(
			'error' => true,
			'msg' => "empty"
		);
		$result = mysqli_query($this->con, "SELECT * FROM users WHERE email='$email'");


		if (mysqli_num_rows($result) == 0) {

			$uuid = uniqid('', true);
			$hash = $this->hashSSHA($password);
			$encrypted_password = $hash["encrypted"]; // encrypted password

			$salt = $hash["salt"]; // salt
			$sql = "INSERT INTO users(unique_id, name, email, encrypted_password, salt, created_at) VALUES('$uuid', '$name', '$email', '$encrypted_password', '$salt', NOW());";

			$result = mysqli_query($this->con, $sql);
			// check for successful store
			if ($result) {
				// get user details
				$id = mysqli_insert_id($this->con); // last inserted id

				$result = mysqli_query($this->con, 'SELECT * FROM istos3.users WHERE id = ' . $id . ' ;');

				// return user details
				$response['error'] = false;
				$response['msg'] = $result;
				return $response;
			} else {
				$response['error'] = true;
				$response['msg'] = "User could not be stored";
				return $response;
			}
		}
		$response['error'] = true;
		$response['msg'] = "User already exists";
		return $response;
	}

	public function loginUser($user_id, $ip, $browser)
	{

		$result = mysqli_query($this->con, "INSERT INTO user_info(login_at,loggout_at,user_id,heartbeat,ip_adress,is_alive,browser)
                              VALUES(NOW(),NULL,$user_id,NOW(),'$ip',NOW(),'$browser')");


		$id = mysqli_insert_id();; // Initializing Session
		$user = mysqli_query($this->con, "SELECT * FROM user_info WHERE id = $id");

		// check for successful store
		if ( ! empty($user)) {
			return mysqli_fetch_array($user);
		} else {
			return false;
		}
	}

	public function isAlive($user_id, $ip, $browser)
	{
		$result = mysqli_query($this->con,
			"INSERT INTO user_info(login_at,loggout_at,user_id,ip_adress,) VALUES(NOW(),NULL,$user_id,$ip,$browsero)");
		$_SESSION['logid'] = mysqli_insert_id();; // Initializing Session

		// check for successful store
		if ($result) {
			return true;
		} else {
			return false;
		}
	}


	public function logoutUser($id)
	{
		$id = $_SESSION['user_info']['id'];
		$ok = mysqli_query($this->con, "UPDATE user_info SET loggout_at = NOW() WHERE id = $id;");

		$result = mysqli_query($this->con, "SELECT loggout_at FROM user_info
                        WHERE $id=$id
                        ORDER BY loggout_at DESC
                        LIMIT 1;");
		if ( ! empty($smt)) {
			return $smt['loggout_at'];
		} else {
			return false;
		}
	}

	public function getUsers()
	{
		$all_users = mysqli_query($this->con, "SELECT * FROM users");
		return mysqli_fetch_array($all_users);

	}

	public function getUserInfo($id)
	{
		$user_info = mysqli_query($this->con, "SELECT * FROM user_info WHERE user_id='$id'");
		$return = mysqli_fetch_array($user_info);

	}

	/**
	 * Get user by email and password
	 */
	public function getUserByEmailAndPassword($email, $password)
	{

		$query = "SELECT * FROM users WHERE email = '$email'";


		$result = mysqli_query($this->con, $query);
		//		var_dump($result);
		if (mysqli_connect_errno()) {
			echo "Failed to connect to MySQL: " . mysqli_connect_error();
		}

		$no_of_rows = mysqli_num_rows($result);

		if ($no_of_rows > 0) {

			$result = mysqli_fetch_array($result);

			$salt = $result['salt'];
			$encrypted_password = $result['encrypted_password'];
			$hash = $this->checkhashSSHA($salt, $password);

			// check for password equality
			//echo(print_r($result,true));
			if ($encrypted_password == $hash) {
				// user authentication details are correct
				return $result;
			}
		} else {

			// user not found
			return false;
		}
	}

	function getGenres()
	{

		$query = "SELECT * FROM genre";

		$result = mysqli_query($this->con, $query);
		if (mysqli_connect_errno()) {
			echo "Failed to connect to MySQL: " . mysqli_connect_error();
		}

		$no_of_rows = mysqli_num_rows($result);

		if ($no_of_rows > 0) {

			$result = mysqli_fetch_all($result);

			// check for password equality
			//echo(print_r($result,true));
			if ( ! empty($result)) {
				// user authentication details are correct
				return $result;
			} else {
				return false;
			}
		} else {

			// user not found
			return false;
		}
	}

	function getMovies($genre_id)
	{


		if ($genre_id == 0) {
			$query = "SELECT * FROM movies ;";
		} else {
			$query = "SELECT * FROM movies WHERE genre_id='$genre_id' ; ";
		}

		$result = mysqli_query($this->con, $query);
		if (mysqli_connect_errno()) {
			echo "Failed to connect to MySQL: " . mysqli_connect_error();
		}

		$no_of_rows = mysqli_num_rows($result);
		$movies_array = array();

		if ($no_of_rows > 0) {

			while ($row = mysqli_fetch_assoc($result)) {
				$movies_array[] = $row;
			}

			// check for password equality
			//echo(print_r($result,true));
			if ( ! empty($movies_array)) {
				// user authentication details are correct
				return $movies_array;
			} else {
				return false;
			}
		} else {

			// user not found
			return false;
		}
	}

	function getMovie($movie_id)
	{

		if (empty($movie_id) || ! intval($movie_id)) {
			return false;
		}

		$query = "SELECT * FROM movies WHERE id='$movie_id' ; ";

		$result = mysqli_query($this->con, $query);
		if (mysqli_connect_errno()) {
			echo "Failed to connect to MySQL: " . mysqli_connect_error();
		}

		$result = mysqli_fetch_assoc($result);

		if ( ! empty($result)) {
			// user authentication details are correct
			return $result;
		} else {
			return false;
		}

	}

	function vote($user_id, $movie_id, $vote)
	{
		//Update user_rating for this movie.
		$user_rating = $this->getUserRating($user_id, $movie_id);
		if ($user_rating == false) {
			$sql = "INSERT INTO istos3.user_ratings(user_id,movie_id,rating) VALUES( " . $user_id . " , " . $movie_id . " , " . $vote . ") ;";
		} else {

			$sql = "UPDATE istos3.user_ratings SET rating = " . $vote . " WHERE user_id=" . $user_id . " AND movie_id=" . $movie_id . ";  ";
			$id = $_SESSION['user_info']['id'];
			$ok = mysqli_query($this->con, "");

		}
		$result_rating = mysqli_query($this->con, $sql);

		//Update user_rating for this movie.
		if ( ! $result_rating) {
			return false;
		}


		//Update movie rating
		//Get current rating
		$query = mysqli_query($this->con, "SELECT movie_rating as rating FROM istos3.movies WHERE  id = $movie_id ;");
		if (mysqli_connect_errno()) {
			echo "Failed to connect to MySQL: " . mysqli_connect_error();
		}
		$current_rating = mysqli_fetch_array($query);


		if ($current_rating['rating'] == 0) {
			$movie_rating = $vote;
		} else {
			$movie_rating = ($vote + $current_rating['rating']) / 2;
		}

		$result_rating = mysqli_query($this->con,
			" UPDATE istos3.movies SET movie_rating = " . $movie_rating . " WHERE  id= " . $movie_id . " ;");
		//Update movie rating
		if ( ! $result_rating) {
			return false;
		}

		return true;


	}


	public function getUserRating($user_id, $movie_id)
	{

		if (empty($user_id) || empty($movie_id)) {
			return false;
		}
		if ( ! intval($user_id) || ! intval($movie_id)) {
			return false;
		}

		$query = "SELECT rating FROM user_ratings WHERE user_id='$user_id' AND movie_id = '$movie_id' ; ";

		$result = mysqli_query($this->con, $query);
		if (mysqli_connect_errno()) {
			echo "Failed to connect to MySQL: " . mysqli_connect_error();
		}

		$result = mysqli_fetch_assoc($result);

		if ( ! empty($result)) {
			// user authentication details are correct
			return $result;
		} else {
			return false;
		}
	}


	function getMovieRating($movie_id)
	{

		if (empty($movie_id) || ! intval($movie_id)) {
			return false;
		}

		$query = "SELECT AVG(rating) as average FROM istos3.user_ratings WHERE  movie_id = '$movie_id';";

		$result = mysqli_query($this->con, $query);
		if (mysqli_connect_errno()) {
			echo "Failed to connect to MySQL: " . mysqli_connect_error();
		}

		$result = mysqli_fetch_assoc($result);

		if ( ! empty($result)) {
			// user authentication details are correct
			return $result;
		} else {
			return false;
		}

	}

	function getNumberofVoters($movie_id)
	{

		if (empty($movie_id) || ! intval($movie_id)) {
			return false;
		}

		$query = "SELECT COUNT(DISTINCT user_id) as voters FROM istos3.user_ratings WHERE  movie_id = '$movie_id';";

		$result = mysqli_query($this->con, $query);

		if (mysqli_connect_errno()) {
			echo "Failed to connect to MySQL: " . mysqli_connect_error();
		}

		$result = mysqli_fetch_assoc($result);

		if ( ! empty($result)) {
			// user authentication details are correct
			return $result;
		} else {
			return false;
		}

	}


	/**
	 * Check user is existed or not
	 */
	public function isUserExisted($email)
	{
		$result = mysqli_query("SELECT email from users WHERE email = '$email'");
		$no_of_rows = mysqli_num_rows($result);
		if ($no_of_rows > 0) {
			// user existed
			return true;
		} else {
			// user not existed
			return false;
		}
	}

	/**
	 * Encrypting password
	 * @param password
	 * returns salt and encrypted password
	 */
	public function hashSSHA($password)
	{

		$salt = sha1(rand());
		$salt = substr($salt, 0, 10);
		$encrypted = base64_encode(sha1($password . $salt, true) . $salt);
		$hash = array("salt" => $salt, "encrypted" => $encrypted);
		return $hash;
	}

	/**
	 * Decrypting password
	 * @param salt , password
	 * returns hash string
	 */
	public function checkhashSSHA($salt, $password)
	{

		$hash = base64_encode(sha1($password . $salt, true) . $salt);

		return $hash;
	}


	function sec_session_start()
	{
		$session_name = 'sec_session_id';   // Set a custom session name
		$secure = SECURE;
		// This stops JavaScript being able to access the session id.
		$httponly = true;
		// Forces sessions to only use cookies.
		if (ini_set('session.use_only_cookies', 1) === false) {
			header("Location: ../error.php?err=Could not initiate a safe session (ini_set)");
			exit();
		}
		// Gets current cookies params.
		$cookieParams = session_get_cookie_params();
		session_set_cookie_params($cookieParams["lifetime"],
			$cookieParams["path"],
			$cookieParams["domain"],
			$secure,
			$httponly);
		// Sets the session name to the one set above.
		session_name($session_name);
		session_start();            // Start the PHP session
		session_regenerate_id(true);    // regenerated the session, delete the old one.
	}

	function checkbrute($user_id, $mysqli)
	{
		// Get timestamp of current time
		$now = time();

		// All login attempts are counted from the past 2 hours.
		$valid_attempts = $now - (2 * 60 * 60);

		if ($stmt = $mysqli->prepare("SELECT time
								 FROM login_attempts 
								 WHERE user_id = ? 
								AND time > '$valid_attempts'")
		) {
			$stmt->bind_param('i', $user_id);

			// Execute the prepared query.
			$stmt->execute();
			$stmt->store_result();

			// If there have been more than 5 failed logins
			if ($stmt->num_rows > 5) {
				return true;
			} else {
				return false;
			}
		}
	}

	function esc_url($url)
	{

		if ('' == $url) {
			return $url;
		}

		$url = preg_replace('|[^a-z0-9-~+_.?#=!&;,/:%@$\|*\'()\\x80-\\xff]|i', '', $url);

		$strip = array('%0d', '%0a', '%0D', '%0A');
		$url = (string)$url;

		$count = 1;
		while ($count) {
			$url = str_replace($strip, '', $url, $count);
		}

		$url = str_replace(';//', '://', $url);

		$url = htmlentities($url);

		$url = str_replace('&amp;', '&#038;', $url);
		$url = str_replace("'", '&#039;', $url);

		if ($url[0] !== '/') {
			// We're only interested in relative links from $_SERVER['PHP_SELF']
			return '';
		} else {
			return $url;
		}
	}


}

?>
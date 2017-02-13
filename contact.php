<?php
//DB Connection class - to be in different file
class DBConnection{

	protected static $db;
	private $host, $dbname, $user, $pass, $charset;
	public $errors = array();
	public $success = array();

	private function __construct() {
		$this->host = '127.0.0.1';
		$this->dbname   = 'samples';
		$this->user = 'root';
		$this->pass = '';
		$this->charset = 'utf8';
		try {
			self::$db = new PDO("mysql:host=$this->host;dbname=$this->dbname", $this->user, $this->pass);
			self::$db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		} catch (PDOException $e) {
			echo "Connection Error: " . $e->getMessage();
		}
	}

	public static function getConnection() {
		if (!self::$db) new DBConnection();
		return self::$db;
	}
}

//Utility class - to send email - to be in utilities file
class Mailer { 
	
	public static function sendMail($to, $from, $subject, $message){
		$to = "To: ".$to;
		$from = "From:".$from;
		$subject = $subject;
		$message = $message;
		$headers = "From:".$from." \r\n";
		$headers .= "MIME-Version: 1.0\r\n";
		$headers .= "Content-type: text/html\r\n";
		try{ 
			$sendmail = mail($to, $subject, $message, $headers);
		} catch(Exception $e) {
			echo "Unable to send Email: " . $e->getMessage();
		}

	}
}

//Utility class - validation - to be in utilities file
class Validate{
	
	public static function isEmpty($var){
		$condition = isset($var) ? $var : null;
		return $condition;
	}
}

class contactUs {
	
	public $username, $email, $url, $comments;
	public $errors = array();
	public $success = array();
	
	public function __construct($username,$email="",$website="",$comments=""){
		//Could also Validate and Sanitize and display more specific errors
		//Directly sanitizing here. Returns false in isEmpty, so direct error message is displayed
		$this->username = filter_var($username, FILTER_SANITIZE_STRING);
		$this->email = filter_var($email, FILTER_SANITIZE_EMAIL);
		$this->subject = "Contact Us";
		$this->website = filter_var($website, FILTER_SANITIZE_URL);
		$this->comments = filter_var($comments, FILTER_SANITIZE_STRING);
	}

	public function renderThanks($username){
		$this->success['thanks'] = "Dear " .$this->username.". Appreciate your inputs. Thank you.<br />"; 
	}
	
	public function execute(){
		if(!Validate::isEmpty($this->username)){ 	
			$this->errors['username'] = "Please enter your User Name<br /> ";
		}
		if(!Validate::isEmpty($this->email)){ 
			$this->errors['email'] = "Please enter your Email<br />";
		}
		if(!Validate::isEmpty($this->comments)){ 
			$this->errors['comments'] = "Please enter your Comments<br />";
		}
		if(count($this->errors)<=0) {
			//DBConnection to insert comment
			$db = DBConnection::getConnection();
			$stmt = $db->prepare("INSERT INTO contact(name,	email, website, subject, message, time) VALUES (:name,:email,:website,:subject,:message, NOW())");
			$stmt->execute(array(':name' => $this->username, ':email' => $this->email, ':website' => $this->website, ':subject' => $this->subject, ':message' => $this->comments));
			$affected_rows = $stmt->rowCount();
			if($affected_rows) {
				//Display Thanks
				$this->renderThanks($this->username);
				
				//Send mail to Admin
				$to = "siteadmin@gmail.com";
				$from = $this->email;
				$subject = "Feedback form";
				$message = $this->comments;
				
				Mailer::sendMail($to, $from, $subject, $message);
			} else {
				$this->errors['wrong'] = "Oops. Something went wrong. Please submit your comments again.<br />";
			}
		}
	}
}

if($_POST){
	$contact = new contactUs($_POST['username']);
	$contact->username = $_POST['username'];
	$contact->email = $_POST['email'];
	$contact->website = $_POST['website'];
	$contact->comments = $_POST['comments'];
	
	$contact->execute();
	//var_dump($contact->errors);
}

	$username =  isset($_POST['username'])?htmlentities($_POST['username']):"";
	$email =  isset($_POST['email'])?htmlentities($_POST['email']):""; 
	$website =  isset($_POST['website'])?htmlentities($_POST['website']):""; 
	$comments =  isset($_POST['comments'])?htmlentities($_POST['comments']):"";
?>
<!DOCTYPE html>
<html>
<head>
<title>Contact Me</title>
<meta charset="utf-8" />
<link rel="stylesheet" href="contact.css" type="text/css">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
<div id="contactForm">
<form action="#" method="post" name="contactForm" id="contactForm">
    <h3>Post your comments</h3>
	<div id="thankyou">
<?php 
if($_POST) { 
	if(isset($contact)) {	
		foreach($contact->errors as $errMsg) {
			echo $errMsg;
		}
		foreach($contact->success as $successMsg) {
			echo $successMsg;
		}
		
	}
	
 }?>
 </div>
    <p>
        <label for="name">Name *</label>
        <input name="username" id="username" type="text" placeholder="Your Name" value="<?php echo $username; ?>" />
    </p>
    <p>
	        <label for="email">E-mail *</label>
        <input name="email" id="email" type="email" placeholder="Your Email" value="<?php echo $email; ?>" />
    </p>
    <p>
        <label for="website">Website</label>
        <input name="website" id="website" type="url" placeholder="Your Website" value="<?php echo $website; ?>" />
    </p>
    <p>
        <label for="comment">Comment *</label>
        <textarea name="comments" id="comments" ><?php echo $comments; ?></textarea>
    </p>
    <p><input type="submit" value="Post comment" /></p>
	<p><input type="reset" value="Reset" /></p>
</form>
</div>

</body>
</html>
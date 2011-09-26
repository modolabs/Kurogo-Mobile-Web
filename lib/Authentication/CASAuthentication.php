<?php
/**
 * @package Authentication
 */

/**
 * An authentication method for the Central Authentication Service (CAS) http://www.jasig.org/cas
 *
 * @package Authentication
 */
class CASAuthentication
	extends AuthenticationAuthority
{
	/**
	 * Class for user objects. Most subclasses will override this
	 * @var string
	 */
	protected $userClass='CASUser';

	/**
	 * Initializes the authority objects based on an associative array of arguments
	 * @param array $args an associate array of arguments. The argument list is dependent on the authority
	 *
	 * Required keys:
	 * TITLE => The human readable title of the AuthorityImage
	 * INDEX => The tag used to identify this authority @see AuthenticationAuthority::getAuthenticationAuthority
	 *
	 * Optional keys:
	 * LOGGEDIN_IMAGE_URL => a url to an image/badge that is placed next to the user name when logged in
	 *
	 * Specific authorities might have other required or optional keys
	 *
	 * NOTE: Any subclass MUST call parent::init($args) to ensure proper operation
	 *
	 */
	public function init($args) {
		parent::init($args);
	
		// include the PHPCAS library
		if (empty($args['CAS_PHPCAS_PATH']))
			require_once('CAS.php');
		else
			require_once($args['CAS_PHPCAS_PATH'].'/CAS.php');
	
		if (empty($args['CAS_PROTOCOL']))
			throw new Exception('CAS_PROTOCOL value not set for ' . $this->AuthorityTitle);
	
		if (empty($args['CAS_HOST']))
			throw new Exception('CAS_HOST value not set for ' . $this->AuthorityTitle);
	
		if (empty($args['CAS_PORT']))
			throw new Exception('CAS_PORT value not set for ' . $this->AuthorityTitle);
	
		if (empty($args['CAS_PATH']))
			throw new Exception('CAS_PATH value not set for ' . $this->AuthorityTitle);
	
		phpCAS::client($args['CAS_PROTOCOL'], $args['CAS_HOST'], intval($args['CAS_PORT']), $args['CAS_PATH'], false);
	
		if (empty($args['CAS_CA_CERT']))
			phpCAS::setNoCasServerValidation();
		else
			phpCAS::setCasServerCACert($args['CAS_CA_CERT']);
	}

	/**
	 * Attempts to authenticate the user using the included credentials
	 * @param string $login the userid to login (this will be blank for OAUTH based authorities)
	 * @param string $password password (this will be blank for OAUTH based authorities)
	 * @param User &$user This object is passed by reference and should be set to the logged in user upon sucesssful login
	 * @return int should return one of the AUTH_ constants
	 */
	protected function auth($login, $password, &$user) {
		phpCAS::forceAuthentication();
	
		$user = new $this->userClass($this);
	
		return AUTH_OK;
	}

	/**
	 * Retrieves a user object from this authority
	 * @param string $login the userid to retrieve
	 * @return User a valid user object or false if the user could not be found
	 * @see User object
	 */
	public function getUser($login) {
		// don't try if it's empty
		if (empty($login) || !phpCAS::isAuthenticated()) {
			return new AnonymousUser();
		}

		if ($login == phpCAS::getUser()) {
			return new $this->userClass($this);
		}
	}

	/**
	 * Retrieves a group object from this authority. Authorities which do not provide group information
	 * should always return false
	 * @param string $group the shortname of the group to retrieve
	 * @return UserGroup a valid group object or false if the group could not be found
	 * @see UserGroup object
	 */
	public function getGroup($group) {
		return false;
	}

	/**
	 * Validates an authority for connectivity
	 * @return boolean. True if connectivity is established or false if it is not. Authorities may also set an error object to provide more information.
	 */
	public function validate(&$error) {
		return true;
	}

	/**
	  * Returns an array of valid user login types. Subclasses can override this to indicate valid
	  * values
	  * @return array a list of valid user login types
	  */
	protected function validUserLogins() {
		return array('LINK', 'NONE');
	}
}

/**
  * @package Authentication
  */
class CASUser
	extends User
{
	/**
	 * Constructor
	 *
	 * @param AuthenticationAuthority $AuthenticationAuthority
	 * @return void
	 */
	public function __construct (AuthenticationAuthority $AuthenticationAuthority) {
		parent::__construct($AuthenticationAuthority);

		if (!phpCAS::isAuthenticated())
			phpCAS::forceAuthentication();

		$this->setUserID(phpCAS::getUser());
		
		$this->setEmail(phpCAS::getAttribute('EMail'));
		$this->setFirstName(phpCAS::getAttribute('FirstName'));
		$this->setLastName(phpCAS::getAttribute('LastName'));
	}
}
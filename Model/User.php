<?php

App::uses('UsersAppModel', 'Users.Model');

/**
 * User Model
 *
 * @property Group $Group
 * @property Task $Task
 * @property DayOff $DayOff
 * @package Plugins
 * @subpackage Users.Models
 */
class User extends UsersAppModel {

/**
 * Plugin name
 *
 * @var string $plugin
 */
	public $plugin = 'Users';

/**
 * Name
 *
 * @var string
 */
	public $name = 'User';

/**
 * Acts as
 *
 * @var array
 */
	public $actsAs = array(
		'Acl' => array(
			'type' => 'requester'
		)
	);

/**
 * Display field
 *
 * @var string
 */
	public $displayField = 'fullname';

/**
 * set the expiration time
 *
 * @var integer
 */
	public $tokenExpirationTime = 86400;

/**
 * Validation rules
 *
 * @var array
 */
	public $validate = array(
		'username' => array(
			'notEmpty' => array(
				'rule' => array('notEmpty'),
			),
			'unique' => array(
				'rule' => array('isUnique', 'username'),
				'message' => 'This username is already in use.'
			),
			'alpha' => array(
				'rule' => array('alphaNumeric'),
				'message' => 'The username must be alphanumeric.'
			),
		),
		'password' => array(
			'notEmpty' => array(
				'rule' => array('notEmpty'),
			),
			'too_short' => array(
				'rule' => array('minLength', '6'),
				'message' => 'The password must have at least 6 characters.'),
		),
		'firstname' => array(
			'notempty' => array(
				'rule' => array('notempty'),
			),
			'alpha' => array(
				'rule' => array('alphaNumeric'),
				'message' => 'The firstname must be alphanumeric.'
			),
		),
		'lastname' => array(
			'notempty' => array(
				'rule' => array('notempty'),
			),
			'alpha' => array(
				'rule' => array('alphaNumeric'),
				'message' => 'The lastname must be alphanumeric.'
			),
		),
		'email' => array(
			'email' => array(
				'rule' => array('email'),
			),
			'isUnique' => array(
				'rule' => array('isUnique'),
				'message' => 'This email is already in use.'),
		),
		'token_email_expires' => array(
			'email' => array(
				'rule' => array('email'),
			),
			'isUnique' => array(
				'rule' => array('isUnique'),
				'message' => 'This email is already in use.'),
		),
		'new_password' => array(
			'too_short' => array(
				'rule' => array('minLength', '6'),
				'message' => 'The password must have at least 6 characters.'
			),
			'diffOldPassword' => array(
				'rule' => array('compareFieldsDiff', 'old_password', 'new_password'),
				'message' => 'The new password must be different from the old'
			)
		),
		'old_password' => array(
			'validateOldPassword' => array(
				'rule' => 'validatePassword',
				'message' => 'Invalid password',
			)
		),
		'verify_password' => array(
			'required' => array(
				'rule' => array('compareFields', 'new_password', 'verify_password'),
				'message' => 'The passwords are not equal.',
			),
		),
	);

/**
 * belongsTo associations
 *
 * @var array
 */
	public $belongsTo = array(
		'Group' => array(
			'className' => 'Users.Group',
			'foreignKey' => 'group_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		),
		'BankAccount' => array(
			'className' => 'BankAccount',
			'foreignKey' => 'bank_account_id',
			'dependent' => false
		)
	);

/**
 * hasOne associations
 *
 * @var type
 */
	public $hasOne = array(
	);

/**
 * hasMany associations
 *
 * @var array
 */
	public $hasMany = array(
		'Task' => array(
			'className' => 'Task',
			'foreignKey' => 'responsible_user_id',
			'dependent' => false,
			'conditions' => '',
			'fields' => '',
			'order' => array(
				'ISNULL(Task.completed) DESC',
				'Task.completed DESC',
				'Task.created DESC',
			),
			'limit' => '',
			'offset' => '',
			'exclusive' => '',
			'finderQuery' => '',
			'counterQuery' => ''
		),
		'Login' => array(
			'className' => 'Login',
			'foreignKey' => 'user_id',
			'dependent' => true,
			'conditions' => '',
			'fields' => '',
			'order' => '',
			'limit' => '',
			'offset' => '',
			'exclusive' => true,
			'finderQuery' => '',
			'counterQuery' => ''
		),
		'DayOff' => array(
			'className' => 'DayOff',
			'foreignKey' => 'user_id',
			'dependent' => false,
			'conditions' => '',
			'fields' => '',
			'order' => '',
			'limit' => '',
			'offset' => '',
			'exclusive' => '',
			'finderQuery' => '',
			'counterQuery' => ''
		),
		'Event' => array(
			'className' => 'Calendar.Event',
			'foreignKey' => 'user_id',
			'dependent' => false,
			'conditions' => '',
			'fields' => '',
			'order' => '',
			'limit' => '',
			'offset' => '',
			'exclusive' => '',
			'finderQuery' => '',
			'counterQuery' => ''
		)
	);

/**
 * We need to create the virtual fields in construct method
 * because we use User model for other models too.
 */
	public function __construct($id = false, $table = null, $ds = null) {
		parent::__construct($id, $table, $ds);
		$alias = $this->alias;
		$this->virtualFields = array(
			'fullname' => "CONCAT($alias.lastname, ' ', $alias.firstname)",
			'shortname' => "CONCAT($alias.lastname, ' ', LEFT($alias.firstname, 1),'.')",
			'phones' => "TRIM(CONCAT($alias.phone, ' ', $alias.cellphone))",
			'time_created' => "DATE_FORMAT($alias.created, '%H:%i')",
			'time_updated' => "DATE_FORMAT($alias.updated, '%H:%i')",
			'date_created' => "DATE_FORMAT($alias.created, '%Y-%m-%d')",
			'date_updated' => "DATE_FORMAT($alias.updated, '%Y-%m-%d')",
		);
	}

/**
 * parentNode method
 *
 * @return null
 */
	public function parentNode() {
		if (!$this->id && empty($this->data)) {
			return null;
		}
		if (isset($this->data['User']['group_id'])) {
			$groupId = $this->data['User']['group_id'];
		} else {
			$groupId = $this->field('group_id');
		}
		if (!$groupId) {
			return null;
		} else {
			return array('Users.Group' => array('id' => $groupId));
		}
	}

/**
 * bindNode method
 *
 * @param type $user
 * @TODO Write dynamically the model
 * @return type
 */
	public function bindNode($user) {
		return array('model' => 'Users.Group', 'foreign_key' => $user['Users.User']['group_id']);
	}

/**
 * Changes the password for a user
 *
 * @param array $data Post data from controller
 * @return boolean True on success
 */
	public function changePassword($data = array()) {
		$this->set($data);
		if ($this->validates()) {
			$this->data[$this->alias]['password'] = $this->hash($this->data[$this->alias]['new_password']);
			$this->save($data, array(
				'validate' => false,
				'callbacks' => false));
			return true;
		}
		return false;
	}

/**
 * Save password of a user
 *
 * @param type $password
 * @return type
 */
	public function savePassword($password) {
		$password = $this->hash($password);
		if (!$this->saveField('password', $password)) {
			throw new Exception('Password is not saved');
		}
	}

/**
 *
 * @param type $check
 * @return type
 */
	public function validatePassword($check) {
		$value = array_values($check);
		$value = $value[0];
		return $this->verifyPassword($value);
	}

/**
 * verify the password of the current record
 *
 * @param string $token
 * @return boolean
 */
	public function verifyPassword($password) {
		if (empty($this->id)) {
			if (empty($this->data[$this->alias]['id'])) {
				return false;
			}
			$this->id = $this->data[$this->alias]['id'];
		}
		if (!$this->exists($this->id)) {
			throw new Exception('The user does not exists');
		}
		$password = $this->hash($password);
		return $password === $this->field('password');
	}

/**
 * verify the token of the current record
 *
 * @param string $token
 * @return boolean
 */
	public function verifyToken($token, $conditions = null) {
		if ($token === $this->field('token', $conditions) && ($this->field('token_expires', $conditions) < time())) {
			return true;
		}
		return false;
	}

/**
 * verify the email_token of the current record
 *
 * @param string $token
 * @return boolean
 */
	public function verifyEmailToken($token, $conditions = null) {
		if ($token === $this->field('token_email', $conditions) && ($this->field('token_email_expires', $conditions) < time())) {
			return true;
		}
		return false;
	}

/**
 * Generate token used by the user registration system
 *
 * @param int $length Token Length
 * @return string
 */
	public function generateToken($length = 10) {
		$possible = '0123456789abcdefghijklmnopqrstuvwxyz';
		$token = "";
		$i = 0;

		while($i < $length) {
			$char = substr($possible, mt_rand(0, strlen($possible) - 1), 1);
			if (!stristr($token, $char)) {
				$token .= $char;
				$i++;
			}
		}
		return $token;
	}

/**
 *
 * @return boolean
 */
	public function renewToken() {
		$data['User']['token'] = $this->generateToken();
		$data['User']['token_expires'] = $this->tokenExpirationTime();
		return $this->save($data);
	}

/**
 *
 * @return boolean
 */
	public function renewTokenEmail() {
		$data['User']['token_email'] = $this->generateToken();
		$data['User']['token_email_expires'] = $this->tokenExpirationTime();
		return $this->save($data);
	}

/**
 * Returns the time the email verification token expires
 *
 * @return datetime
 */
	public function tokenExpirationTime() {
		return date('Y-m-d H:i:s', time() + $this->tokenExpirationTime);
	}

/**
 * Create a hash from string using given method.
 * Fallback on next available method.
 *
 * Override this method to use a different hashing method
 *
 * @param string $string String to hash
 * @param string $type Method to use (sha1/sha256/md5)
 * @param boolean $salt If true, automatically appends the application's salt
 * 	 value to $string (Security.salt)
 * @return string Hash
 */
	public function hash($string) {
		return AuthComponent::password($string);
	}

/**
 * beforeSave method
 *
 * @param array $options
 * @return boolean
 */
	public function beforeSave($options = array()) {
		if (isset($this->data['User']['id'])) {
			if (!isset($this->data['User']['password'])) {
				return parent::beforeSave($options);
			}
			if (empty($this->data['User']['password'])) {
				return false;
			}
			if ($this->verifyPassword($this->data['User']['password'])) {
				return parent::beforeSave($options);
			}
		}
		if (isset($this->data['User']['password'])) {
			$this->data['User']['password'] = $this->hash($this->data['User']['password']);
			$this->beforeRegister();
		}
		return parent::beforeSave($options);
	}

/**
 * beforeRegister method
 *
 * @param array $data
 * @param array $options
 */
	public function beforeRegister() {
		$data = array();
		$data['User']['token'] = $this->generateToken();
		$data['User']['token_expires'] = $this->tokenExpirationTime();
		$data['User']['token_email'] = $this->generateToken();
		$data['User']['token_email_expires'] = $this->tokenExpirationTime();
		if (!isset($this->data['User']['group_id'])) {
			$data['User']['group_id'] = $this->Group->field('Group.id', array('name' => 'user'));
		}
		$this->data = array_merge_recursive($this->data, $data);
	}

/**
 * afterLogin method
 *
 * @param array $data
 */
	public function afterLogin($data = array(), $options = array()) {
		$this->Login->create();
		if (!$this->Login->save($data)) {
			return false;
		}
		return true;
	}

}

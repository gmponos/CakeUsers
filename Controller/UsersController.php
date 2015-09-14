<?php

App::uses('CakeEmail', 'Network/Email');
App::uses('UsersAppController', 'Users.Controller');

/**
 * Users Controller
 *
 * @property	User $User
 * @package		Plugins
 * @subpackage	Users.Controllers
 */
class UsersController extends UsersAppController {

/**
 * beforeFilter method
 *
 * @return void
 */
	public function beforeFilter() {
		parent::beforeFilter();
		//Allow the guest user to register and login
		$this->Auth->allow(
				'register', 'login', 'logout', 'confirm', 'confirmResend', 'requestPassword', 'newPassword', 'resetPassword'
		);
	}

/**
 * Sets the default pagination settings up
 *
 * Override this method or the index action directly if you want to change
 * pagination settings.
 *
 * @return void
 */
	protected function _paginate($scope = array()) {
		$whitelist = array(
			'User.username'
		);
		$this->Paginator->settings = array(
			'conditions' => array(
				$this->modelClass . '.active' => 1,
				$this->modelClass . '.verified' => 1
			)
		);
		$this->User->recursive = 0;
		return $this->Paginator->paginate('User', $scope, $whitelist);
	}

/**
 * Sets the default pagination settings up
 *
 * Override this method or the index() action directly if you want to change
 * pagination settings. admin_index()
 *
 * @return void
 */
	protected function _admin_paginate($scope = array()) {
		$whitelist = array(
			'User.username'
		);
		$this->Paginator->settings = array(
			'order' => array(
				$this->modelClass . '.created' => 'desc'
			),
		);
		$this->User->recursive = 0;
		return $this->Paginator->paginate('User', $scope, $whitelist);
	}

/**
 * index method
 *
 * @return void
 */
	public function index() {
		$this->set('users', $this->_paginate());
	}

/**
 * view method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function view($id = null) {
		if (!$this->User->exists($id)) {
			throw new NotFoundException(__('Invalid user'));
		}
		$this->set('user', $this->User->findById($id));
	}

/**
 * Related method
 *
 * @throws NotFoundException
 */
	public function related() {
		$query = $this->request->params['named'];
		if (empty($query)) {
			throw new NotFoundException('No Parameters');
		}
		$this->set('users', $this->User->find('all', array('conditions' => $query)));
	}

/**
 * personal method
 *
 * @throws NotFoundException
 * @return void
 */
	public function personal() {
		//Check if user exists
		$id = $this->Auth->user('id');
		if (!$this->User->exists($id)) {
			throw new NotFoundException(__('Invalid user'));
		}
		if ($this->request->is('post') || $this->request->is('put')) {
			if ($this->User->save($this->request->data)) {
				return $this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The user could not be saved. Please, try again.'));
			}
		} else {
			$this->request->data = $this->User->findById($id);
		}
		$bankAccounts = $this->User->BankAccount->find('list');
		$this->set(compact('bankAccounts'));
	}

/**
 * admin_index method
 *
 * @return void
 */
	public function admin_index() {
		$this->set('users', $this->_admin_paginate());
	}

/**
 * admin_view method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function admin_view($id = null) {
		if (!$this->User->exists($id)) {
			throw new NotFoundException(__('Invalid user'));
		}
		$this->set('user', $this->User->findById($id));
	}

/**
 * admin_add method
 *
 * @return void
 */
	public function admin_add() {
		if ($this->request->is('post')) {
			$this->User->create();
			debug($this->request->data);
			if ($this->User->save($this->request->data)) {
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The user could not be saved. Please, try again.'));
			}
		}
		$bankAccounts = $this->User->BankAccount->find('list');
		$groups = $this->User->Group->find('list');
		$this->set(compact('groups', 'bankAccounts'));
	}

/**
 * admin_edit method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function admin_edit($id = null) {
		if (!$this->User->exists($id)) {
			throw new NotFoundException(__('Invalid user'));
		}
		if ($this->request->is('post') || $this->request->is('put')) {
			if ($this->User->save($this->request->data)) {
				return $this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The user could not be saved. Please, try again.'));
			}
		} else {
			$this->User->recursive = 0;
			$this->request->data = $this->User->findById($id);
		}
		$bankAccounts = $this->User->BankAccount->find('list');
		$groups = $this->User->Group->find('list');
		$this->set(compact('groups', 'bankAccounts'));
	}

/**
 * admin_delete method
 *
 * @throws NotFoundException
 * @throws MethodNotAllowedException
 * @param string $id
 * @return void
 */
	public function admin_delete($id = null) {
		$this->request->onlyAllow('post', 'delete');
		$this->User->id = $id;
		if (!$this->User->exists()) {
			throw new NotFoundException(__('Invalid user'));
		}
		if (!$this->User->delete()) {
			throw new NotFoundException(__('The user was not deleted'));
		}
		if ($this->request->is('ajax')) {
			return $this->redirect($this->referer());
		}
		return $this->redirect(array('action' => 'index'));
	}

/**
 *
 * @throws NotFoundException
 */
	public function admin_related() {
		$query = $this->request->params['named'];
		if (empty($query)) {
			throw new NotFoundException('No Parameters');
		}
		$this->set('users', $this->User->find('all', array('conditions' => $query)));
	}

/**
 * login method
 *
 */
	public function login() {
		if ($this->Auth->loggedIn()) {
			return $this->redirect('/dashboard');
		}
		if ($this->request->is('post')) {
			if ($this->Auth->login()) {
				$data['Login']['IP'] = $this->request->clientIp();
				$data['Login']['browser'] = env('HTTP_USER_AGENT');
				$data['Login']['user_id'] = $this->Auth->user('id');
				$this->User->afterLogin($data);
				return $this->redirect('/dashboard');
			} else {
				$this->Session->setFlash('Your username or password was incorrect.');
			}
		}
	}

/**
 * Common logout action
 *
 * @return void
 */
	public function logout() {
		$user = $this->Auth->user();
		$this->Session->destroy();
		$this->Session->setFlash(sprintf(__('%s you have successfully logged out'), $user[$this->User->displayField]), 'alert', array(
			'class' => 'alert-info'
		));
		$this->redirect($this->Auth->logout());
	}

/**
 * register method
 *
 */
	public function register() {
		if ($this->request->is('post')) {
			$this->User->create();
			if ($this->User->save($this->request->data)) {
				$user = $this->User->findById($this->User->getLastInsertID());
				$this->_sendConfirmEmail($user);
				$this->Session->setFlash(__('The registration was successfull. Check your email to confirm your account'));
				return $this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The user could not be registered. Please, try again.'));
			}
		}
	}

/**
 * confirm account
 *
 * @param type $id
 * @param type $emailToken
 * @return void
 */
	public function confirm($id, $emailToken) {
		$this->User->id = $id;
		if (!$this->User->exists()) {
			throw new NotFoundException(__('Invalid user'));
		}
		if ($this->User->verifyEmailToken($emailToken)) {
			$this->User->saveField('active', true);
			$this->User->saveField('verified', true);
		}
	}

/**
 * Resends the email for the user to verify it account
 *
 *
 */
	public function confirmResend() {
		if ($this->request->is('post')) {
			$email = $this->request->data['User']['email'];
			$user = $this->User->findByEmail($email);
			if (empty($user)) {
				$this->Session->setFlash(sprintf(__('There is no user with %s email'), $email));
			} else {
				$this->_sendConfirmEmail($user);
				$this->Session->setFlash(__('You have 24 hours to confirm your account'));
			}
		}
	}

/**
 * Used for logged in user to change their password.
 *
 * @param type $id
 * @param type $token
 * @return type
 * @throws NotFoundException
 */
	public function changePassword($id, $token = null) {
		$this->User->id = $this->Auth->user('id');
		if (!$this->User->exists()) {
			throw new NotFoundException(__('Invalid user'));
		}
		if ($this->request->is('post')) {
			if (!$this->User->verifyToken($token)) {
				$this->Session->setFlash(__('The user token is incorect.The password is not saved.'));
				return $this->redirect(array('action' => 'index'));
			}
			if ($this->User->changePassword($this->request->data)) {
				return $this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The user password could not be changed. Please, try again.'));
			}
		}
		$this->set('token', $this->User->field('token'));
		$this->set('id', $id);
	}

/**
 * Reset password for users that are not logged in
 *
 * @return null
 */
	public function resetPassword() {
		if ($this->request->is('post')) {
			$email = $this->request->data['User']['email'];
			$user = $this->User->findByEmail($email);
			if (empty($user)) {
				$this->Session->setFlash(sprintf(__('There is no user with %s email'), $email));
			} else {
				$this->_sendResetPasswordEmail($user);
				$time = $this->User->tokenExpirationTime();
				$this->User->set($user);
				$this->User->saveField('token_email_expires', $time);
				$this->Session->setFlash(__('You have 24 hours to reset your password'));
				return $this->redirect(array('action' => 'login'));
			}
		}
	}

/**
 * New password
 *
 * @param type $id
 * @param type $emailToken
 * @throws NotFoundException
 */
	public function newPassword($id, $emailToken) {
		$this->User->id = $id;
		if (!$this->User->exists()) {
			throw new NotFoundException(__('Invalid user'));
		}
		if (!$this->User->verifyEmailToken($emailToken)) {
			throw new NotFoundException(__('The page you are trying to show is invalid'));
		}
		if ($this->request->is('post')) {
			$this->User->savePassword($this->request->data['User']['password']);
			return $this->redirect(array('action' => 'login'));
		}
		$this->set('token', $emailToken);
		$this->set('id', $id);
	}

/**
 * Renew the expiration date of token
 *
 * @param type $id
 * @return type
 * @throws NotFoundException
 */
	public function admin_renewToken($id = null) {
		$this->User->id = $id;
		if (!$this->User->exists()) {
			throw new NotFoundException(__('Invalid user'));
		}
		if ($this->User->renewToken()) {
			$this->Session->setFlash(__('The token expires in 24 hours.'));
		} else {
			$this->Session->setFlash(__('The token could not be updated'));
		}
		return $this->redirect($this->referer());
	}

/**
 * Renew the expiration date of email token
 *
 * @param type $id
 * @return type
 * @throws NotFoundException
 */
	public function admin_renewTokenEmail($id = null) {
		$this->User->id = $id;
		if (!$this->User->exists()) {
			throw new NotFoundException(__('Invalid user'));
		}
		if ($this->User->renewTokenEmail()) {
			$this->Session->setFlash(__('The token email expires in 24 hours.'));
		} else {
			$this->Session->setFlash(__('The token email could not be updated'));
		}
		return $this->redirect($this->referer());
	}

/**
 * _sendRegisterEmail method
 *
 * @param type $data
 * @param type $options
 */
	protected function _sendConfirmEmail($user = array()) {
		$this->Email->subject = sprintf(__('Welcome %s to CRM'), $user['User']['fullname']);
		$this->Email->template = 'users/confirm';
		$this->Email->to = $user['User']['email'];
		$this->set('user', $user);
		$this->Email->send();
	}

/**
 * _sendRegisterEmail method
 *
 * @param type $data
 * @param type $options
 */
	protected function _sendResetPasswordEmail($user = array()) {
		$this->Email->subject = sprintf(__('Reset password %s'), $user['User']['fullname']);
		$this->Email->template = 'users/reset_password';
		$this->Email->to = $user['User']['email'];
		$this->set('user', $user);
		$this->Email->send();
	}

}

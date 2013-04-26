<?php
App::uses('AppController', 'Controller');
App::import('Vendor', 'OAuth/OAuthClient');
/**
 * Users Controller
 *
 * @property User $User
 */
class UsersController extends AppController {

/**
 *  Layout
 *
 * @var string
 */
	//public $layout = 'bootstrap';

/**
 * Helpers
 *
 * @var array
 */
	//public $helpers = array('TwitterBootstrap.BootstrapHtml', 'TwitterBootstrap.BootstrapForm', 'TwitterBootstrap.BootstrapPaginator');
/**
 * Components
 *
 * @var array
 */

    public function beforeFilter() {
            parent::beforeFilter();
            $this->Auth->allow('twitter_login','login','callback');
    }

    public function twitter_login() {
            $client = $this->createClient();
            //$requestToken = $client->getRequestToken('https://api.twitter.com/oauth/request_token', 'http://' . $_SERVER['HTTP_HOST'] . '/nomado/users/callback');
            //$client = $this->createClient();
            $requestToken = $client->getRequestToken('https://api.twitter.com/oauth/request_token', FULL_BASE_URL . '/nomado/users/callback');
            debug($requestToken);
            if ($requestToken) {
                $this->Session->write('twitter_request_token',$requestToken);
                $this->redirect('https://api.twitter.com/oauth/authorize?oauth_token=' . $requestToken->key);
            } else {
                    $this->Session->setFlash(__('signed out'));
                    $this->redirect(array('controller' => 'users','action' => 'login'));
            }

            //Configure::write('debug',0);
            //$this->layout = 'ajax';
    }

    public function callback() {
            $requestToken = $this->Session->read('twitter_request_token');
            $client = $this->createClient();
            $accessToken = $client->getAccessToken('https://api.twitter.com/oauth/access_token',$requestToken);

            if ($accessToken) {
                    debug($accessToken);
                    $user_json = $client->get($accessToken->key, $accessToken->secret,'https://api.twitter.com/1.1/account/verify_credentials.json');
                    $user = json_decode($user_json, true);

                    if ($user) {
                            $this->User->twitterUpdate(array(
                                                        'twitter_id' => $user['id_str'],
                                                        'username' => $user['screen_name'],
                                                        'oauth_token' => $accessToken->key,
                                                        'oauth_token_secret' => $accessToken->secret,
                                                        'file' => $user['profile_image_url'],
                                                ));
                            $auth = array('User' => array('oauth_token' => $accessToken->key,
                                    'oauth_token_secret' => $accessToken->secret,
                            ));

                            if ($this->Auth->login($auth)) {
                                    $this->redirect($this->Auth->redirect());
                            } /*else {
                                    $this->redirect(array('controller' => 'users','action' => 'login'));
                            }*/
                    } /*else {
                                    $this->redirect(array('controller' => 'users','action' => 'login'));
                    } */

                    //debug($user);
            }
    }

    public function login() {
    }

    public function logout() {
            $this->Session->destroy();
            $this->Session->setFlash(__('signed out'));
            $this->Session->delete($this->Auth->sessionKey);
            $this->redirect($this->Auth->logoutRedirect);
    }
	//public $components = array('Session');
/**
 * index method
 *
 * @return void
 */
	public function index() {
		$this->User->recursive = 0;
		$this->set('users', $this->paginate());
    }

    /*public function oauth_callback() {

            if (!$this->Twitter->isRequested()) {
                    $this->flash(__('invalid access'), '/',5);
                    return;
            }

            //アクセストークンの取得
            $this->Twitter->setTwitterSource('twitter');
            $token = $this->Twitter->getAccessToken();

            //アクセストークンを正しく取得できなかった場合の処理
            if (is_string($token)) {
                    $this->flash(__('fail get access token.') . $token, '/', 5);
                    return;
            }

            //以上のifに一致しなかった場合は正しく処理されたものとして扱い
            //$data['User']に情報を設定する
            $data['User'] = array(
                    'id' => $token['user_id'],
                    'username' => $token['screen_name'],
                    'password' => Security::hash($token['oauth_token']),
                    'oauth_token' => $token['oauth_token'],
                    'oauth_token_secret' => $token['oauth_token_secret'],
            );

            //設定したトークンをusersテーブルに書き込む処理と、失敗した場合の処理
            if (!$this->User->save($data)) {
                    $this->flash(__('user not saved.'), 'login',5);
                    return;
            }
            //認証およびusersテーブルへの書き込みが完了したので、
            //CakePHPサービス自信へのログインを実施し、ログイン完了後の画面にリダイレクト
            $this->Auth->login($data);
            $this->redirect($this->Auth->loginRedirect);


    }*/

/**
 * view method
 *
 * @param string $id
 * @return void
 */
	public function view($id = null) {
		$this->User->id = $id;
		if (!$this->User->exists()) {
			throw new NotFoundException(__('Invalid %s', __('user')));
		}
		$this->set('user', $this->User->read(null, $id));
	}

/**
 * add method
 *
 * @return void
 */
	public function add() {
		if ($this->request->is('post')) {
			$this->User->create();
			if ($this->User->save($this->request->data)) {
				$this->Session->setFlash(
					__('The %s has been saved', __('user')),
					'alert',
					array(
						'plugin' => 'TwitterBootstrap',
						'class' => 'alert-success'
					)
				);
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(
					__('The %s could not be saved. Please, try again.', __('user')),
					'alert',
					array(
						'plugin' => 'TwitterBootstrap',
						'class' => 'alert-error'
					)
				);
			}
		}
	}

/**
 * edit method
 *
 * @param string $id
 * @return void
 */
	public function edit($id = null) {
		$this->User->id = $id;
		if (!$this->User->exists()) {
			throw new NotFoundException(__('Invalid %s', __('user')));
		}
		if ($this->request->is('post') || $this->request->is('put')) {
			if ($this->User->save($this->request->data)) {
				$this->Session->setFlash(
					__('The %s has been saved', __('user')),
					'alert',
					array(
						'plugin' => 'TwitterBootstrap',
						'class' => 'alert-success'
					)
				);
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(
					__('The %s could not be saved. Please, try again.', __('user')),
					'alert',
					array(
						'plugin' => 'TwitterBootstrap',
						'class' => 'alert-error'
					)
				);
			}
		} else {
			$this->request->data = $this->User->read(null, $id);
		}
	}

/**
 * delete method
 *
 * @param string $id
 * @return void
 */
	public function delete($id = null) {
		if (!$this->request->is('post')) {
			throw new MethodNotAllowedException();
		}
		$this->User->id = $id;
		if (!$this->User->exists()) {
			throw new NotFoundException(__('Invalid %s', __('user')));
		}
		if ($this->User->delete()) {
			$this->Session->setFlash(
				__('The %s deleted', __('user')),
				'alert',
				array(
					'plugin' => 'TwitterBootstrap',
					'class' => 'alert-success'
				)
			);
			$this->redirect(array('action' => 'index'));
		}
		$this->Session->setFlash(
			__('The %s was not deleted', __('user')),
			'alert',
			array(
				'plugin' => 'TwitterBootstrap',
				'class' => 'alert-error'
			)
		);
		$this->redirect(array('action' => 'index'));
    }

    private function createClient() {
            return new OAuthClient('Ec5mcESyv0AhWrc46GbHrg', 'PFX8NjtBS1XdLuuHIhQ4TGFLH8NHzhP5ijWS8UK0Js');
    }
}
<?php

defined('SYSPATH') or die('No direct script access.');

/**
 * Auth module demo controller. This controller should NOT be used in production.
 * It is for demonstration purposes only!
 *
 * $Id: auth_demo.php 3267 2008-08-06 03:44:02Z Shadowhand $
 *
 * @package    Auth
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Auth_Controller extends Controller {
   // Do not allow to run in production
   const ALLOW_PRODUCTION = TRUE;

   // Use the default Kohana template
   public $defaulttemplate = 'auth/template';
   public $message = '';

   public function __construct() {
      parent::__construct();
      $this->view = new View($this->defaulttemplate);
   }

   public function index() {
      $this->login();
   }

   public function create() {
      $this->view->title = 'Create User';

      $form = new Forge('auth/create');
      $form->input('email')->label(TRUE)->rules('required|length[4,32]|valid_email')->class("grid_4");
      $form->input('username')->label(TRUE)->rules('required|length[4,32]')->class("grid_4");
      $form->password('password')->label(TRUE)->rules('required|length[5,40]')->class("grid_4");
      $form->submit('Create New User');

      if ($form->validate()) {
         // Create new user
         $user = ORM::factory('user');

         if (!$user->username_exists($form->username->value)) {
            foreach ($form->as_array() as $key => $val) {
               // Set user data
               $user->$key = $val;
            }

            if ($user->save() AND $user->add(ORM::factory('role', 'login'))) {
               Auth::instance()->login($user, $form->password->value);

               // Redirect to the login page
               url::redirect('auth/login');
            }
         } else {
            
         }
      }

      // Display the form
      $this->view->content = $form->render();
   }

   public function login($redirect = null) {
      if (Auth::instance()->logged_in()) {
         if ($this->message) {
            $this->view->title = $this->message;
         } else if ($redirects = Kohana::config('auth.redirect')) {

            $redirects = Kohana::config('auth.redirect');
            if (is_array($redirects)) {

               $user = ORM::factory('user', $form->username->value);
               foreach ($redirects as $role => $redirect) {
                  if ($user->has(ORM::Factory('role', $redirectURI))) {
                     url::redirect($redirectURI);
                     return;
                  }
               }
            } else {
               //auth.redirect should have roles and then a default
               //processed in order of precidence
               url::redirect($redirects);
            }
         } else {
            $this->view->title = 'User Logout';
         }

         $form = new Forge('auth/logout');
         $form->submit('Logout Now');
      } else {
         if ($redirect == 'resetPasswordSuccess') {
            $this->view->message = Kohana::lang('auth.resetPasswordSuccess');
            $redirect = null;
         }
         $this->view->title = 'User Login';

         $form = new Forge('auth/login');
         $form->input('username')->label(TRUE)->rules('required|length[4,32]')->class("grid_4");
         $form->password('password')->label(TRUE)->rules('required|length[5,40]')->class("grid_4");
         if ($redirect) {
            $form->hidden('redirect')->value($redirect);
         }
         $form->submit('Login');

         if ($form->validate()) {
            // Load the user
            $user = ORM::factory('user', $form->username->value);

            if (Auth::instance()->login($user, $form->password->value)) {
               // Login successful, redirect
            
               if (isset($_POST['redirect']) && $_POST['redirect']) {
                  url::redirect($_POST['redirect']);
               } else if ($redirects = Kohana::config('auth.redirect')) {
                  if (is_array($redirects)) {

                     $user = ORM::factory('user', $form->username->value);
                     foreach ($redirects as $role => $redirectURI) {
                        if ($user->has(ORM::Factory('role', $role))) {
                           url::redirect($redirectURI);
                           return;
                        }
                     }
                  } else {
                     //auth.redirect should have roles and then a default
                     //processed in order of precidence
                     url::redirect($redirects);
                  }
               } else {
                  url::redirect('auth/login');
               }
            } else {
               $form->password->add_error('login_failed', 'Invalid username or password.');
            }
         }
      }

      // Display the form
      $this->view->content = $form->render();
   }

   public function logout() {
      // Force a complete logout
      Auth::instance()->logout(TRUE);

      // Redirect back to the login page
      url::redirect('auth/login');
   }

   public function noaccess($controller = NULL) {
      $this->message = 'You do not have access to the requested page';
      $this->login($controller);
   }

   public function forgot() {
      if (isset($_POST['email'])) {
         $user = ORM::Factory('user')->where('email', $_POST['email'])->find();
         if ($user->loaded) {
            $password = $this->randomPassword();
            $user->password = $password;
            $user->save();
            $body = Kohana::lang('auth.forgotPasswordEmailBody');
            $body = str_replace('___MOP___username___MOP___', $user->username, $body);
            $body = str_replace('___MOP___password___MOP___', $password, $body);
            mail($user->email, Kohana::lang('auth.forgotPasswordEmailSubject'), $body);
            url::redirect('auth/login/resetPasswordSuccess');
         } else {
            $this->view = new View('auth/forgot');
            $this->view->message = Kohana::lang('auth.resetPasswordFailed');
         }
      } else {
         $this->view = new View('auth/forgot');
      }
   }

   public function randomPassword() {
      $password_length = 9;

      function make_seed() {
         list($usec, $sec) = explode(' ', microtime());
         return (float) $sec + ((float) $usec * 100000);
      }

      srand(make_seed());

      $alfa = "1234567890qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM";
      $token = "";
      for ($i = 0; $i < $password_length; $i++) {
         $token .= $alfa[rand(0, strlen($alfa) - 1)];
      }
      return $token;
   }

}

// End Auth Controller

<?php
require_once __DIR__ . '/../includes/functions.php';

class SocialLogin {
    private $config;
    
    public function __construct() {
        $this->config = require_once __DIR__ . '/../includes/social-config.php';
    }
    
    public function getGoogleLoginUrl() {
        $params = [
            'client_id' => $this->config['google']['client_id'],
            'redirect_uri' => $this->config['google']['redirect_uri'],
            'scope' => 'email profile',
            'response_type' => 'code',
            'state' => $this->generateState()
        ];
        
        $_SESSION['oauth_state'] = $params['state'];
        
        return 'https://accounts.google.com/o/oauth2/auth?' . http_build_query($params);
    }
    
    public function getFacebookLoginUrl() {
        $params = [
            'client_id' => $this->config['facebook']['app_id'],
            'redirect_uri' => $this->config['facebook']['redirect_uri'],
            'scope' => 'email,public_profile',
            'response_type' => 'code',
            'state' => $this->generateState()
        ];
        
        $_SESSION['oauth_state'] = $params['state'];
        
        return 'https://www.facebook.com/v18.0/dialog/oauth?' . http_build_query($params);
    }
    
    public function handleGoogleCallback($code, $state) {
        if (!$this->validateState($state)) {
            throw new Exception('Invalid state parameter');
        }
        
        // Exchange code for access token
        $tokenData = $this->getGoogleAccessToken($code);
        
        // Get user info
        $userInfo = $this->getGoogleUserInfo($tokenData['access_token']);
        
        // Create or login user
        return $this->createOrLoginUser($userInfo, 'google');
    }
    
    public function handleFacebookCallback($code, $state) {
        if (!$this->validateState($state)) {
            throw new Exception('Invalid state parameter');
        }
        
        // Exchange code for access token
        $tokenData = $this->getFacebookAccessToken($code);
        
        // Get user info
        $userInfo = $this->getFacebookUserInfo($tokenData['access_token']);
        
        // Create or login user
        return $this->createOrLoginUser($userInfo, 'facebook');
    }
    
    private function getGoogleAccessToken($code) {
        $data = [
            'client_id' => $this->config['google']['client_id'],
            'client_secret' => $this->config['google']['client_secret'],
            'redirect_uri' => $this->config['google']['redirect_uri'],
            'grant_type' => 'authorization_code',
            'code' => $code
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    private function getGoogleUserInfo($accessToken) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/oauth2/v2/userinfo');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    private function getFacebookAccessToken($code) {
        $data = [
            'client_id' => $this->config['facebook']['app_id'],
            'client_secret' => $this->config['facebook']['app_secret'],
            'redirect_uri' => $this->config['facebook']['redirect_uri'],
            'code' => $code
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://graph.facebook.com/v18.0/oauth/access_token?' . http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    private function getFacebookUserInfo($accessToken) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://graph.facebook.com/me?fields=id,name,email,picture&access_token=' . $accessToken);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    private function createOrLoginUser($userInfo, $provider) {
        global $mysqli;
        
        $email = $userInfo['email'] ?? '';
        $name = $userInfo['name'] ?? '';
        $socialId = $userInfo['id'] ?? '';
        $avatar = '';
        
        if ($provider === 'google') {
            $avatar = $userInfo['picture'] ?? '';
        } elseif ($provider === 'facebook') {
            $avatar = $userInfo['picture']['data']['url'] ?? '';
        }
        
        // Check if user exists by email
        $stmt = $mysqli->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if ($user) {
            // Update social info if not exists
            $stmt = $mysqli->prepare('UPDATE users SET social_provider = ?, social_id = ?, avatar = ? WHERE id = ?');
            $stmt->bind_param('sssi', $provider, $socialId, $avatar, $user['id']);
            $stmt->execute();
            
            // Login user
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            
            return $user;
        } else {
            // Create new user
            $stmt = $mysqli->prepare('INSERT INTO users (name, email, social_provider, social_id, avatar, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
            $stmt->bind_param('sssss', $name, $email, $provider, $socialId, $avatar);
            
            if ($stmt->execute()) {
                $userId = $mysqli->insert_id;
                
                // Login user
                $_SESSION['user_id'] = $userId;
                $_SESSION['name'] = $name;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = 'user';
                
                return [
                    'id' => $userId,
                    'name' => $name,
                    'email' => $email,
                    'role' => 'user'
                ];
            }
        }
        
        return false;
    }
    
    private function generateState() {
        return bin2hex(random_bytes(16));
    }
    
    private function validateState($state) {
        return isset($_SESSION['oauth_state']) && $_SESSION['oauth_state'] === $state;
    }
}
?>
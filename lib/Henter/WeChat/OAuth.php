<?php

namespace Henter\WeChat;

class OAuth {
    /**
     * @ignore
     */
    private $app_id;

    /**
     * @ignore
     */
    private $secret;

    /**
     * @ignore
     */
    private $access_token;

    /**
     * @ignore
     */
    private $refresh_token;

    /**
     * @ignore
     */
    private $expires_in;

    /**
     * @ignore
     */
    private $openid;

    /**
     * Set up the API root URL
     *
     * @ignore
     */
    private $host = "https://open.weixin.qq.com/";

    /**
     * Set timeout
     *
     * @ignore
     */
    private $timeout = 5;

    /**
     * Set the user agnet
     *
     * @ignore
     */
    private $user_agent = 'Henter WeChat OAuth SDK';

    private $error;

    /**
     * @param $app_id
     * @param $secret
     * @param null $access_token
     * @return OAuth
     */
    public function __construct($app_id, $secret, $access_token = null) {
        $this->app_id = $app_id;
        $this->secret = $secret;
        $this->access_token = $access_token;
        return $this;
    }

    public function error($error = NULL){
        if(is_null($error))
            return $this->error;

        $this->error = $error;
        return false;
    }

    /**
     * get authorize url, with callback url and scope
     *
     * @param $redirect_uri
     * @param string $scope
     * @param null $state
     * @return string
     */
    public function getAuthorizeURL($redirect_uri, $scope = 'snsapi_login', $state = null) {
        $params = array();
        $params['app_id'] = $this->app_id;
        $params['redirect_uri'] = $redirect_uri;
        $params['response_type'] = 'code';
        $params['scope'] = $scope;
        $params['state'] = $state;
        return $this->host . "connect/qrconnect?" . http_build_query($params);
    }

    /**
     * get access_token
     *
     * @param string $type [code|token]
     * @param $key [code|refresh_token]
     * @return string
     */
    public function getAccessToken($type = 'code', $key) {
        $params = array();
        $params['app_id'] = $this->app_id;
        $params['secret'] = $this->secret;

        if ($type === 'token') {
            $uri = 'sns/oauth2/refresh_token';
            $params['app_id'] = $this->app_id;
            $params['grant_type'] = 'refresh_token';
            $params['refresh_token'] = $key;
        }elseif($type === 'code') {
            $uri = 'sns/oauth2/access_token';
            $params['app_id'] = $this->app_id;
            $params['secret'] = $this->secret;
            $params['code'] = $key;
            $params['grant_type'] = 'authorization_code';
        }else{
            return $this->error("wrong auth type");
        }

        $return = $this->request($this->host.$uri, 'GET', $params)->toArray();
        if(!is_array($return) || !$return)
            return $this->error("get access token failed");

        if (!isset($return['errcode'])){
            $this->access_token = $return['access_token'];
            $this->refresh_token = $return['refresh_token'];
            $this->expires_in = $return['expires_in'];
            $this->openid = $return['openid'];
        }else{
            return $this->error("get access token failed: " . $return['errmsg']);
        }
        return $this->access_token;
    }

    /**
     * refresh access_token
     *
     * @param string $refresh_token
     * @return string
     */
    public function refreshAccessToken($refresh_token){
        return $this->getAccessToken('token', $refresh_token);
    }

    /**
     * get refresh_token
     * @return string
     */
    public function getRefreshToken(){
        return $this->refresh_token;
    }

    /**
     * get expires time (seconds)
     * @return integer
     */
    public function getExpiresIn(){
        return $this->expires_in;
    }

    /**
     * get openid
     * @return string
     */
    public function getOpenid(){
        return $this->openid;
    }

    /**
     * request api
     *
     * @param $api
     * @param array $params
     * @param string $method
     * @return array|false
     */
    public function api($api, $params = array(), $method = 'GET'){
        if(!isset($params['access_token']) && !$this->access_token)
            return $this->error('access_token error');

        $params['access_token'] = $this->access_token;

        $return = $this->request($this->host.$api, $method, $params)->toArray();
        if(!is_array($return) || !$return)
            return $this->error("request failed");

        if (!isset($return['errcode'])) {
            return $return;
        }else{
            return $this->error("request failed: " . $return['errmsg']);
        }
    }

    /**
     * http request wrapper
     * @param $url
     * @param $method
     * @param $parameters
     * @return \Henter\WeChat\Response
     */
    function request($url, $method, $parameters) {
        return Request::create(array(
            'url'     => $url,
            'method'  => $method,
            'headers' => array(),
            'form'    => $parameters,
            'timeout' => $this->timeout
        ))->send();
    }

}
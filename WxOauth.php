<?php

namespace Weixin;

require 'Conf.php';
require 'RuntimeException.php';
require 'Common.php';

class Oauth extends Common {

    private $scope = 'snsapi_base'; // snsapi_userinfo
	private $code;
	private $access_token;
	private $refresh_token;

    public function setScope($scope)
    {
        $this->scope = $scope;
    }

	public function createCodeURL($redirect_url)
	{
		$urlObj["appid"] = Conf::APPID;
		$urlObj["redirect_uri"] = $redirect_url;
		$urlObj["response_type"] = 'code';
		$urlObj["scope"] = $this->scope;
		$urlObj["state"] = 'STATE';
		return 'https://open.weixin.qq.com/connect/oauth2/authorize?'.http_build_query($urlObj).'#wechat_redirect';
	}

	public function setCode($code) {
		$this->code = $code;
	}

	private function createOpenidURL()
	{
		$urlObj["appid"] = Conf::APPID;
		$urlObj["secret"] = Conf::APPSECRET;
		$urlObj["code"] = $this->code;
		$urlObj["grant_type"] = 'authorization_code';
		return "https://api.weixin.qq.com/sns/oauth2/access_token?".http_build_query($urlObj);
	}

    public function setRefreshToken($refresh_token) {
        $this->refresh_token = $refresh_token;
    }

	private function createRefreshURL() {
		$urlObj["appid"] = Conf::APPID;
		$urlObj["grant_type"] = "refresh_token";
		$urlObj["refresh_token"] = $this->refresh_token;
		return "https://api.weixin.qq.com/sns/oauth2/refresh_token?".http_build_query($urlObj);
	}

	public function getOauthInfo($type = 1) {
		$url = '';
		if($type == 1) $url = $this->createOpenidURL();
		else if($type == 2) $url = $this->createRefreshURL();
		else return false;

        //取出openid
        $data = $this->getCurl($url);
		$data = json_decode($data,true);
		
		// 这些数据都应该保存在服务端
		$this->openid = $data['openid'];
		$this->access_token = $data['access_token'];
		$this->refresh_token = $data['refresh_token'];

		return $data;
	}

    public function refreshOauthInfo() {
        return $this->getOauthInfo(2);
    }

	public function getOpenid()
	{
		if(!$this->openid) $this->getOauthInfo();
		return $this->openid;
	}

	public function getAccessToken() {
		if(!$this->access_token) $this->getOauthInfo(2);
		return $this->access_token;
	}

	public function getRefreshToken() {
		if(!$this->refresh_token) $this->getOauthInfo(2);
		return $this->refresh_token;
	}

	public function getUserInfo() {
		// 生成URL
		$urlObj["access_token"] = $this->access_token;
		$urlObj["openid"] = $this->openid;
		$urlObj["lang"] = "zh_CN";
		$url = "https://api.weixin.qq.com/sns/userinfo?".http_build_query($urlObj);
		// 获取用户信息
		$data = $this->getCurl($url);
		$data = json_decode($data,true);

		return $data;
	}

	/**
	 * 判断当前的access token是否是有效的
	 */
	public function isAccessToken() {
		// 生成URL
		$urlObj["access_token"] = $this->access_token;
		$urlObj["openid"] = $this->openid;
		$url = "https://api.weixin.qq.com/sns/auth?".http_build_query($urlObj);
		// 获取用户信息
		$data = $this->getCurl($url);
		$data = json_decode($data,true);

		return $data['errcode'] === 0;
	}

}
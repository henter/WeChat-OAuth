<?php
namespace Henter\WeChat;


class OAuthErrorTest extends \PHPUnit_Framework_TestCase
{
    public function testError()
    {

        $oauth = new OAuth('wxbdc5610cc59_error_app_id', 'error_secret');

        $oauth->getAccessToken('code', 'error_authorization_code');
        $this->assertEquals('get access token failed', $oauth->error());

        $oauth = new OAuth('wxbdc5610cc59_error_app_id', 'error_secret', 'error_access_token');
        $oauth->api('sns/userinfo', array('openid'=>'error_openid'));
        $this->assertEquals('request failed', $oauth->error());

    }
}

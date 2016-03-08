<?php

use veqryn\Curl\Curl;
use veqryn\Curl\CurlException;

/**
 * Class CurlTest
 * @author Chris Duncan
 */
class CurlTest extends \PHPUnit_Framework_TestCase {

    public function testGetSucceeds() {
        $curl = new Curl();
        $response = $curl->get('www.google.com');
        $this->assertEquals(200, $response->headers['Status-Code']);
        $this->assertContains('google', $response->body);
    }
    public function testGetFails302() {
		$curl = new Curl();
        $curl->follow_redirects = false;
        $response = $curl->get('www.google.com');
        $this->assertEquals(302, $response->headers['Status-Code']);

    }
    public function testGetFails404() {
		$curl = new Curl();
        $curl->follow_redirects = false;
        $response = $curl->get('www.google.com/404');
        $this->assertEquals(404, $response->headers['Status-Code']);
        $this->assertEquals(null, $response->body);

    }
    public function testPostFails405() {
		$curl = new Curl();
        $response = $curl->post('www.google.com','a=b');
        $this->assertEquals(405, $response->headers['Status-Code']);

    }
    public function testGetFails() {
        $curl = new Curl();
        try {
            $response = $curl->get('diaewkaksdljf-invalid-url-dot-com.com');
        } catch (CurlException $e) {
            $this->assertContains("CURLE_COULDNT_RESOLVE_HOST: ", $e->getMessage());
            return;
        }
        $this->assertTrue(false);
    }
}
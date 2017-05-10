<?php 

use Agavi\Testing\FlowTestCase;

/**
 * @routingInput /en/auth/login
 */
class LoginFlowTest extends FlowTestCase
{
	
	/**
	 * @requestMethod write
	 */
	public function testValidWriteRequest()
	{
		$this->dispatch(array('username' => 'Chuck Norris', 'password' => 'kick'));
		$this->assertResponseHasTag(array('tag' => 'body'));
		$this->assertResponseHasTag(array('tag' => 'h2', 'content' => 'Login Successful'));
	}
	
	/**
	 * @requestMethod write
	 */
	public function testInvalidWriteRequest()
	{
		$this->dispatch(array('username' => 'Chuck Norris', 'password' => 'foo'));
		$this->assertResponseHasTag(array('tag' => 'body'));
		$this->assertResponseHasNotTag(array('tag' => 'h2', 'content' => 'Login Successful'));
		$this->assertResponseHasTag(array('tag' => 'p', 'content' => 'Wrong Password'));
	}
}

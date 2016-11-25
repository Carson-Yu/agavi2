<?php
namespace Agavi\Tests\Unit\Routing;
use Agavi\Core\Context;
use Agavi\Testing\PhpUnitTestCase;
use Agavi\Testing\Routing\TestingWebRouting;

class Ticket1294Test extends PhpUnitTestCase
{
	/**
	 * @var TestingWebRouting
	 */
	protected $routing;
	protected $parameters = array('enabled' => true);
	
	/**
	 * Constructs a test case with the given name.
	 *
	 * @param  string $name
	 * @param  array  $data
	 * @param  string $dataName
	 */
	public function __construct($name = NULL, array $data = array(), $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
	}
	
	public function setUp()
	{
		// otherwise, the full URI wouldn't work
		$_SERVER['REQUEST_URI'] = '/index.php';
		$_SERVER['SCRIPT_NAME'] = '/index.php';
		
		$this->routing = new TestingWebRouting();
		$this->routing->initialize(Context::getInstance(null), $this->parameters);
		$this->routing->startup();
	}
	
	public function testQueryStringParametersCanBeUnsetUsingNull()
	{
		$this->routing->setInput('/ticket_1294');
		$this->routing->setInputParameters(array('foo' => 'bar'));
		$this->routing->execute();
		$url = $this->routing->gen(null, array('foo' => null));
		$this->assertEquals('/index.php/ticket_1294', $url);
	}
}


?>
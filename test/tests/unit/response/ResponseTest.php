<?php
namespace Agavi\Tests\Unit\Response;

use Agavi\Dispatcher\OutputType;
use Agavi\Response\Response;
use Agavi\Testing\UnitTestCase;

class SampleResponse extends Response
{
    public function clear()
    {
    }

    public function send(OutputType $ot = null)
    {
    }
    
    public function setRedirect($to)
    {
    }
    
    public function getRedirect()
    {
    }
    
    public function hasRedirect()
    {
    }
    
    public function clearRedirect()
    {
    }
    
    public function merge(Response $other)
    {
    }
}

class ResponseTest extends UnitTestCase
{
    /**
     * @var Response
     */
    private $_r = null;

    public function setUp()
    {
        $this->_r = new SampleResponse();
        $this->_r->initialize($this->getContext());
    }

    public function testGetContext()
    {
        $ctx = $this->getContext();
        $ctx_test = $this->_r->getContext();
        $this->assertSame($ctx, $ctx_test);
    }

    public function testSetGetContent()
    {
        $r = $this->_r;
        $this->assertEquals('', $r->getContent());
        $r->setContent('test1');
        $this->assertEquals('test1', $r->getContent());
    }

    public function testPrependContent()
    {
        $r = $this->_r;

        $r->setContent('content a');
        $r->prependContent('content b');
        $this->assertEquals('content b' . 'content a', $r->getContent());
    }

    public function testAppendContent()
    {
        $r = $this->_r;

        $r->setContent('content a');
        $r->appendContent('content b');
        $this->assertEquals('content a' . 'content b', $r->getContent());
    }
}

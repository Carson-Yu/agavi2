<?php

use Agavi\Testing\ViewTestCase;
use Agavi\Request\WebRequestDataHolder;

class Products_Product_ViewSuccessViewTest extends ViewTestCase
{

    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        // FIXME: the underlying issue must be solved
        $this->controllerName = 'Product.View';
        $this->moduleName = 'Products';
        $this->viewName   = 'Success';
    }
    
    /**
     * @dataProvider supportedOtProvider
     */
    public function testHandlesOutputType($ot_name)
    {
        $this->assertHandlesOutputType($ot_name);
    }
    
    public function supportedOtProvider()
    {
        return array(
            'html'   => array('html'),
            'html'   => array('text'),
            // 'json'   => array('json'),
            'soap'   => array('soap'),
            'xmlrpc' => array('xmlrpc'),
        );
    }
    
    public function testNotHandlesXmlOutputType()
    {
        $this->assertNotHandlesOutputType('xml');
    }
    
    // FIXME: needs to be updated
    public function testResponseHtml()
    {
        $this->setArguments($this->createRequestDataHolder(array(WebRequestDataHolder::SOURCE_PARAMETERS => array('product_name' => 'spam'))));

        $this->setAttribute('product_id', 1234);
        $this->setAttribute('product_name', 'spam');
        $this->setAttribute('product_price', '123.45');
        $this->runView();
        $this->assertViewResponseHasHTTPStatus(200);
        $this->assertViewResultEquals('');
        $this->assertHasLayer('content');
        $this->assertHasLayer('decorator');
        $this->assertViewRedirectsNot();
        $this->assertContainerAttributeExists('_title');
    }
    
    // public function testResponseJson()
    // {
    // 	$this->setArguments($this->createRequestDataHolder(array(AgaviWebRequestDataHolder::SOURCE_PARAMETERS => array('product_name' => 'spam'))));
    //
    // 	$this->setAttribute('product_id', 1234);
    // 	$this->setAttribute('product_name', 'spam');
    // 	$this->setAttribute('product_price', '123.45');
    // 	$this->runView('json');
    // 	$this->assertResponseHasHTTPStatus(200);
    // 	$this->assertViewResultEquals('{"product_price":"123.45"}');
    // 	$this->assertResponseHasNoRedirect();
    // }
}

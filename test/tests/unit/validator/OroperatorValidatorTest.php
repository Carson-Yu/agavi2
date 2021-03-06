<?php
namespace Agavi\Tests\Unit\Validator;

use Agavi\Request\RequestDataHolder;
use Agavi\Test\Validator\DummyValidator;
use Agavi\Testing\UnitTestCase;
use Agavi\Validator\OroperatorValidator;
use Agavi\Validator\Validator;

class OroperatorValidatorTest extends UnitTestCase
{
    public function testvalidate()
    {
        $vm = $this->getContext()->createInstanceFor('validation_manager');
        $vm->clear();
        /** @var OroperatorValidator $o */
        $o = $vm->createValidator('Agavi\\Validator\\OroperatorValidator', array(), array(), array('severity' => 'error'));

        /** @var DummyValidator $val1 */
        $val1 = $vm->createValidator('Agavi\\Test\\Validator\\DummyValidator', array(), array(), array('severity' => 'error'));
        /** @var DummyValidator $val2 */
        $val2 = $vm->createValidator('Agavi\\Test\\Validator\\DummyValidator', array(), array(), array('severity' => 'error'));
        $o->registerValidators(array($val1, $val2));
        
        // 1st test: both successful
        $val1->val_result = true;
        $val2->val_result = true;
        $this->assertEquals($o->execute(new RequestDataHolder()), Validator::SUCCESS);
        $this->assertTrue($val1->validated);
        $this->assertTrue($val2->validated);
        $val1->clear();
        $val2->clear();

        // 2nd test: first successful
        $val1->val_result = true;
        $val2->val_result = false;
        $this->assertEquals($o->execute(new RequestDataHolder()), Validator::SUCCESS);
        $this->assertTrue($val1->validated);
        $this->assertTrue($val2->validated);
        $val1->clear();
        $val2->clear();

        // 3rd test: last successful
        $val1->val_result = false;
        $val2->val_result = true;
        $this->assertEquals($o->execute(new RequestDataHolder()), Validator::SUCCESS);
        $this->assertTrue($val1->validated);
        $this->assertTrue($val2->validated);
        $val1->clear();
        $val2->clear();

        // 4th test: none successful
        $val1->val_result = false;
        $val2->val_result = false;
        $this->assertEquals($o->execute(new RequestDataHolder()), Validator::ERROR);
        $this->assertTrue($val1->validated);
        $this->assertTrue($val2->validated);
        $val1->clear();
        $val2->clear();

        // 5th test: successful with break enabled
        $o->setParameter('break', true);
        $val1->val_result = true;
        $val2->val_result = false;
        $this->assertEquals($o->execute(new RequestDataHolder()), Validator::SUCCESS);
        $this->assertTrue($val1->validated);
        $this->assertFalse($val2->validated);
        $val1->clear();
        $val2->clear();

        // 6th test: fails because of CRITICAL result
        $o->setParameter('break', false);
        $val1->val_result = false;
        $val1->setParameter('severity', 'critical');
        $val2->val_result = true;
        $this->assertEquals($o->execute(new RequestDataHolder()), Validator::CRITICAL);
        $this->assertTrue($val1->validated);
        $this->assertFalse($val2->validated);
        $this->assertEquals($vm->getResult(), Validator::CRITICAL);
        $val1->clear();
        $val2->clear();
    }
}

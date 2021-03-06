<?php
use Agavi\Validator\Validator;

class SampleAppProductValidator extends Validator
{
    public function validate()
    {
        if ($this->hasMultipleArguments()) {
            $arguments = $this->getArguments();
            if (!isset($arguments['id']) || !isset($arguments['name'])) {
                throw new Exception('Expecting arguments "id" and "name"');
            }
            $id = $this->getData($arguments['id']);
            $name = $this->getData($arguments['name']);
            
            $product = $this->getContext()->getModel('ProductFinder')->retrieveByIdAndName($id, $name);
        } else {
            $id = $this->getData($this->getArgument());
            
            $product = $this->getContext()->getModel('ProductFinder')->retrieveById($id);
        }
        
        if (!$product) {
            $this->throwError();
            return false;
        }
        
        $this->export($product);
        
        return true;
    }
}

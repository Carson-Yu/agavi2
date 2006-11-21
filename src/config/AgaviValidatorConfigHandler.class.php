<?php

// +---------------------------------------------------------------------------+
// | This file is part of the Agavi package.                                   |
// | Copyright (c) 2003-2006 the Agavi Project.                                |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code. You can also view the    |
// | LICENSE file online at http://www.agavi.org/LICENSE.txt                   |
// |   vi: set noexpandtab:                                                    |
// |   Local Variables:                                                        |
// |   indent-tabs-mode: t                                                     |
// |   End:                                                                    |
// +---------------------------------------------------------------------------+

/**
 * AgaviValidatorConfigHandler allows you to register validators with the
 * system.
 *
 * @package    agavi
 * @subpackage config
 *
 * @author     Uwe Mesecke <uwe@mesecke.net>
 * @author     Dominik del Bondio <ddb@bitxtender.com>
 * @copyright  (c) Authors
 * @since      0.11.0
 *
 * @version    $Id$
 */
class AgaviValidatorConfigHandler extends AgaviConfigHandler
{
	/**
	 * @var        array operator => validator mapping
	 */
	protected $classMap = array();

	/**
	 * Execute this configuration handler.
	 *
	 * @param      string An absolute filesystem path to a configuration file.
	 * @param      string An optional context in which we are currently running.
	 *
	 * @return     string Data to be written to a cache file.
	 *
	 * @throws     <b>AgaviUnreadableException</b> If a requested configuration
	 *                                             file does not exist or is not
	 *                                             readable.
	 * @throws     <b>AgaviParseException</b> If a requested configuration file is
	 *                                        improperly formatted.
	 *
	 * @author     Uwe Mesecke <uwe@mesecke.net>
	 * @author     Dominik del Bondio <ddb@bitxtender.com>
	 * @since      0.11.0
	 */
	public function execute($config, $context = null)
	{
		$this->classMap = array(
			'and' => array('class' => 'AgaviAndoperatorValidator', 'parameters' => array('break' => '1')),
			'date' => array('class' => 'AgaviDateValidator', 'parameters' => array('check' => '1')),
			'email' => array('class' => 'AgaviEmailValidator', 'parameters' => array()),
			'equals' => array('class' => 'AgaviEqualsValidator', 'parameters' => array()),
			'inarray' => array('class' => 'AgaviInarrayValidator', 'parameters' => array('sep' => ',')),
			'isset' => array('class' => 'AgaviIssetValidator', 'parameters' => array()),
			'isuploadedimage' => array('class' => 'AgaviIsuploadedimageValidator', 'parameters' => array()),
			'mktimestamp' => array('class' => 'AgaviMktimestampValidator', 'parameters' => array()),
			'not' => array('class' => 'AgaviNotoperatorValidator', 'parameters' => array()),
			'number' => array('class' => 'AgaviNumberValidator', 'parameters' => array('type' => 'int')),
			'or' => array('class' => 'AgaviOroperatorValidator', 'parameters' => array('break' => '1')),
			'regex' => array('class' => 'AgaviRegexValidator', 'parameters' => array('match' => '1')),
			'set' => array('class' => 'AgaviSetValidator', 'parameters' => array()),
			'string' => array('class' => 'AgaviStringValidator', 'parameters' => array('min' => '1')),
			'time' => array('class' => 'AgaviTimeValidator', 'parameters' => array('check' => '1')),
			'uploadedfile' => array('class' => 'AgaviUploadedFileValidator', 'parameters' => array()),
			'xor' => array('class' => 'AgaviXoroperatorValidator', 'parameters' => array()),
		);

		// parse the config file
		$configurations = $this->orderConfigurations(AgaviConfigCache::parseConfig($config, true, $this->getValidationFile(), $this->parser)->configurations, AgaviConfig::get('core.environment'), $context);

		$code = array();//array('lines' => array(), 'order' => array());

		foreach($configurations as $cfg) {
			if(isset($cfg->validator_definitions)) {
				foreach($cfg->validator_definitions as $vDev) {
					$name = $vDev->getAttribute('name');
					if(!isset($this->classMap[$name])) {
						$this->classMap[$name] = array('class' => $vDev->getAttribute('class'), 'parameters' => array());
					}
					$this->classMap[$name]['class'] = $vDev->getAttribute('class',$this->classMap[$name]['class']);
					$this->classMap[$name]['parameters'] = $this->getItemParameters($vDev, $this->classMap[$name]['parameters']);
				}
			}

			if(isset($cfg->validators)) {
				$stdSeverity = $cfg->validators->getAttribute('severity', 'error');
				foreach($cfg->getChildren() as $validators) {
					if($validators->getName() == 'validators') {
						$stdMethod = $validators->getAttribute('method');
						foreach($validators as $validator) {
							$code = $this->getValidatorArray($validator, $code, $stdSeverity, 'validatorManager', $stdMethod);
						}
					}
				}
			}
		}

		// compile data
		$retval = "<?php\n" .
				  "// auto-generated by ".__CLASS__."\n" .
				  "// date: %s GMT\n%s\n?>";
		$retval = sprintf($retval, gmdate('m/d/Y H:i:s'), join("\n", $code));

		return $retval;
	}

	/**
	 * Builds an array of php code strings, each of them creating a validator
	 *
	 * @param      AgaviConfigValueHolder The value holder of this validator.
	 * @param      array  The code of old validators (we simply overwrite "old" 
	 *                    validators here).
	 * @param      string The severity of the parent container.
	 * @param      string The name of the parent container.
	 * @param      string The method of the parent container.
	 * @param      bool Whether the parent container is required.
	 *
	 * @return     array php code blocks that register the validators
	 *
	 * @author     Uwe Mesecke <uwe@mesecke.net>
	 * @author     Dominik del Bondio <ddb@bitxtender.com>
	 * @since      0.11.0
	 */
	public function getValidatorArray($validator, $code, $stdSeverity, $parent, $stdMethod, $stdRequired = true)
	{
		if(!isset($this->classMap[$validator->getAttribute('class')])) {
			$class = $validator->getAttribute('class');
			if(!class_exists($class)) {
				throw new AgaviValidatorException('unknown validator found: ' . $class);
			}
			$this->classMap[$class] = array('class' => $class, 'parameters' => array());
		} else {
			$class = $this->classMap[$validator->getAttribute('class')]['class'];
		}

		// setting up parameters
		$parameters = array(
			'severity' => $validator->getAttribute('severity', $stdSeverity),
			'method' => $validator->getAttribute('method', $stdMethod),
			'required' => $stdRequired,
		);

		$arguments = array();
		$errors = array();

		$stdMethod = $parameters['method'];
		$name = $validator->getAttribute('name', uniqid('val'.rand()));

		$parameters = array_merge($this->classMap[$validator->getAttribute('class')]['parameters'], $parameters);
		$parameters = array_merge($parameters, $validator->getAttributes());
		$parameters = $this->getItemParameters($validator, $parameters);
		if(isset($validator->arguments)) {
			if($validator->arguments->hasAttribute('base')) {
				$parameters['base'] = $validator->arguments->getAttribute('base');
			}
			$args = array();
			foreach($validator->arguments as $argument) {
				if($argument->hasAttribute('name')) {
					$args[$argument->getAttribute('name')] = $argument->getValue();
				} else {
					$args[] = $argument->getValue();
				}
			}
			$arguments = $args;
		}
		if(isset($validator->errors)) {
			foreach($validator->errors as $error) {
				if($error->hasAttribute('for')) {
					$errors[$error->getAttribute('for')] = $error->getValue();
				} else {
					$errors[''] = $error->getValue();
				}
			}
		}
		if($validator->hasAttribute('required')) {
			$stdRequired = $parameters['required'] = $this->literalize($validator->getAttribute('required'));
		}

		if(isset($validator->validators)) {
			// create operator
			$code[$name] = '$'.$name.' = new '.$class.'($'.$parent.', '.var_export($arguments, true).', '.var_export($errors, true).', '.var_export($parameters, true).', '.var_export($name, true).');' .
											'$'.$parent.'->addChild($'.$name.');';

			$childSeverity = $validator->validators->getAttribute('severity', $stdSeverity);
			$childMethod = $validator->validators->getAttribute('method', $stdMethod);
			$childRequired = $stdRequired;
			if($validator->validators->hasAttribute('required')) {
				$childRequired = $this->literalize($validator->validators->getAttribute('required'));
			}
			foreach($validator->validators as $v) {
				$code = $this->getValidatorArray($v, $code, $childSeverity, $name, $childMethod, $childRequired);
			}
				// create child validators
		} else {
			// create new validator
			$code[$name] = '$'.$parent.'->addChild(new '.$class.'($'.$parent.', '.var_export($arguments, true).', '.var_export($errors, true).', '.var_export($parameters, true).', '.var_export($name, true).'));';
		}

		return $code;
	}
}

?>
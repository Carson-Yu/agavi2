<?php

// +---------------------------------------------------------------------------+
// | This file is part of the Agavi package.                                   |
// | Copyright (c) 2005-2008 the Agavi Project.                                |
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
 * AgaviValidationValidatorResult provides access to the validation result for a given validator
 *
 * @package    agavi
 * @subpackage validator
 *
 * @author     Dominik del Bondio <dominik.del.bondio@bitextender.com>
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      1.0.0
 *
 * @version    $Id$
 */
class AgaviValidationValidatorResult
{
	/**
	 * @var        AgaviValidationResult the result
	 */
	protected $validationResult;
	
	/**
	 * @var        string the affected validators name
	 */
	protected $validatorName;
	
	/**
	 * create a new AgaviValidationValidatorResult
	 * 
	 * @param      AgaviValidationResult the validation result
	 * @param      string the affected validators name
	 * 
	 * @author     Dominik del Bondio <dominik.del.bondio@bitextender.com>
	 * @copyright  Authors
	 * @copyright  The Agavi Project
	 *
	 * @since      1.0.0
	 */
	public function __construct(AgaviValidationResult $result, $name)
	{
		$this->validationResult = $result;
		$this->validatorName = $name;
	}
	
	/**
	 * retrieve the affected validators name
	 * 
	 * @return     string the validators name
	 * 
	 * @author     Dominik del Bondio <dominik.del.bondio@bitextender.com>
	 * @copyright  Authors
	 * @copyright  The Agavi Project
	 *
	 * @since      1.0.0
	 */
	public function getValidatorName()
	{
		return $this->validatorName;
	}
	
	/**
	 * retrieve all AgaviValidationIncidents for this instances' validator
	 * 
	 * @return     array a collection of affected {@see AgaviValidationIncident}
	 * 
	 * @author     Dominik del Bondio <dominik.del.bondio@bitextender.com>
	 * @copyright  Authors
	 * @copyright  The Agavi Project
	 *
	 * @since      1.0.0
	 */
	public function getIncidents()
	{
		$affectedIncidents = array();
		$incidents = $this->validationResult->getIncidents();
		foreach($incidents as $incident) {
			if($incident->getValidator()->getName() == $this->validatorName) {
				$affectedIncidents[] = $incident;
			}
		}
		return $affectedIncidents;
	}
}

?>
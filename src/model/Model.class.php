<?php

// +---------------------------------------------------------------------------+
// | This file is part of the Agavi package.                                   |
// | Copyright (c) 2003, 2004 Agavi Foundation.                                |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code. You can also view the    |
// | LICENSE file online at http://www.agavi.org.                              |
// +---------------------------------------------------------------------------+

/**
 * Model provides a convention for separating business logic from application
 * logic. When using a model you're providing a globally accessible API for
 * other modules to access, which will boost interoperability among modules in
 * your web application.
 *
 * @package    agavi
 * @subpackage model
 *
 * @author    Agavi Foundation (info@agavi.org)
 * @copyright (c) Agavi Foundation, {@link http://www.agavi.org}
 * @since     3.0.0
 * @version   $Id$
 */
abstract class Model extends AgaviObject
{

	// +-----------------------------------------------------------------------+
	// | PRIVATE VARIABLES                                                     |
	// +-----------------------------------------------------------------------+
	private
		$context = null;

	// +-----------------------------------------------------------------------+
	// | METHODS                                                               |
	// +-----------------------------------------------------------------------+

	/**
	 * Retrieve the current application context.
	 *
	 * @return Context The current Context instance.
	 *
	 * @author Agavi Foundation (info@agavi.org)
	 * @since  3.0.0
	 */
	public final function getContext ()
	{

		return $this->context;

	}

	// -------------------------------------------------------------------------

	/**
	 * Initialize this model.
	 *
	 * @param Context The current application context.
	 *
	 * @return bool true, if initialization completes successfully, otherwise
	 *              false.
	 *
	 * @author Agavi Foundation (info@agavi.org)
	 * @since  3.0.0
	 */
	public function initialize ($context)
	{

		$this->context = $context;

		return true;

	}

}

?>
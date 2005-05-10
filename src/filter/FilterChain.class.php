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
 * FilterChain manages registered filters for a specific context.
 *
 * @package    agavi
 * @subpackage filter
 *
 * @author    Agavi Foundation (info@agavi.org)
 * @copyright (c) Agavi Foundation, {@link http://www.agavi.org}
 * @since     1.0.0
 * @version   $Id$
 */
class FilterChain extends AgaviObject
{

	// +-----------------------------------------------------------------------+
	// | PRIVATE VARIABLES                                                     |
	// +-----------------------------------------------------------------------+

	private
		$chain = array(),
		$index = -1;

	// +-----------------------------------------------------------------------+
	// | METHODS                                                               |
	// +-----------------------------------------------------------------------+

	/**
	 * Execute the next filter in this chain.
	 *
	 * @return void
	 *
	 * @author Agavi Foundation (info@agavi.org)
	 * @since  3.0.0
	 */
	public function execute ()
	{

		// skip to the next filter
		$this->index++;

		if ($this->index < count($this->chain))
		{

			// execute the next filter
			$this->chain[$this->index]->execute($this);

		}

	}

	// -------------------------------------------------------------------------

	/**
	 * Register a filter with this chain.
	 *
	 * @param Filter A Filter implementation instance.
	 *
	 * @return void
	 *
	 * @author Agavi Foundation (info@agavi.org)
	 * @since  3.0.0
	 */
	public function register ($filter)
	{

		$this->chain[] = $filter;

	}

}

?>
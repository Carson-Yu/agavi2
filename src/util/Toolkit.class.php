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
 * Toolkit provides basic utility methods.
 *
 * @package    agavi
 * @subpackage util
 *
 * @author    Agavi Foundation (info@agavi.org)
 * @copyright (c) Agavi Foundation, {@link http://www.agavi.org}
 * @since     1.0.0
 * @version   $Id$
 */
class Toolkit extends AgaviObject
{

	// +-----------------------------------------------------------------------+
	// | METHODS                                                               |
	// +-----------------------------------------------------------------------+

	/**
	 * Extract the class or interface name from filename.
	 *
	 * @param string A filename.
	 *
	 * @return string A class or interface name, if one can be extracted,
	 *                otherwise null.
	 *
	 * @author Agavi Foundation (info@agavi.org)
	 * @since  3.0.0
	 */
	public static function extractClassName ($filename)
	{

		$retval = null;

		if (self::isPathAbsolute($filename))
		{

			$filename = basename($filename);

		}

		$pattern = '/(.*?)\.(class|interface)\.php/i';

		if (preg_match($pattern, $filename, $match))
		{

			$retval = $match[1];

		}

		return $retval;

	}

	// -------------------------------------------------------------------------

	/**
	 * Determine if a filesystem path is absolute.
	 *
	 * @param path A filesystem path.
	 *
	 * @return bool true, if the path is absolute, otherwise false.
	 *
	 * @author Agavi Foundation (info@agavi.org)
	 * @since  3.0.0
	 */
	public static function isPathAbsolute ($path)
	{

		if ($path{0} == '/' || $path{0} == '\\' ||
			(strlen($path) > 3 && ctype_alpha($path{0}) &&
			 $path{1} == ':' &&
			 ($path{2} == '\\' || $path{2} == '/')
			)
		   )
		{

			return true;

		}

		return false;

	}

}

?>
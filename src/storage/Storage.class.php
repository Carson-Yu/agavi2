<?php
namespace Agavi\Storage;

// +---------------------------------------------------------------------------+
// | This file is part of the Agavi package.                                   |
// | Copyright (c) 2005-2011 the Agavi Project.                                |
// | Based on the Mojavi3 MVC Framework, Copyright (c) 2003-2005 Sean Kerr.    |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code. You can also view the    |
// | LICENSE file online at http://www.agavi.org/LICENSE.txt                   |
// |   vi: set noexpandtab:                                                    |
// |   Local Variables:                                                        |
// |   indent-tabs-mode: t                                                     |
// |   End:                                                                    |
// +---------------------------------------------------------------------------+

use Agavi\Core\Context;
use Agavi\Exception\InitializationException;
use Agavi\Util\ParameterHolder;

/**
 * Storage allows you to customize the way Agavi stores its persistent
 * data.
 *
 * @package    agavi
 * @subpackage storage
 *
 * @author     Sean Kerr <skerr@mojavi.org>
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      0.9.0
 *
 * @version    $Id$
 */
abstract class Storage extends ParameterHolder
{
    /**
     * @var        Context A Context instance.
     */
    protected $context = null;

    /**
     * Retrieve the current application context.
     *
     * @return     Context A Context instance.
     *
     * @author     Sean Kerr <skerr@mojavi.org>
     * @since      0.9.0
     */
    final public function getContext()
    {
        return $this->context;
    }

    /**
     * Initialize this Storage.
     *
     * @param      Context $context    A Context instance.
     * @param      array   $parameters An associative array of initialization parameters.
     *
     * @throws     InitializationException If an error occurs while
     *                                                 initializing this Storage.
     *
     * @author     Sean Kerr <skerr@mojavi.org>
     * @since      0.9.0
     */
    public function initialize(Context $context, array $parameters = array())
    {
        $this->context = $context;

        $this->setParameters($parameters);
    }

    /**
     * Executes code necessary to startup the storage (a session, for example).
     * This code cannot be run in initialize(), because initialization has to
     * finish completely, for all instances, before a session can be created.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @since      0.11.0
     */
    public function startup()
    {
    }

    /**
     * Read data from this storage.
     *
     * The preferred format for a key is directory style so naming conflicts can
     * be avoided.
     *
     * @param      string $key A unique key identifying your data.
     *
     * @return     mixed Data associated with the key.
     *
     * @throws     <b>AgaviStorageException</b> If an error occurs while reading
     *                                          data from this storage.
     *
     * @author     Sean Kerr <skerr@mojavi.org>
     * @since      0.9.0
     */
    abstract function read($key);

    /**
     * Remove data from this storage.
     *
     * The preferred format for a key is directory style so naming conflicts can
     * be avoided.
     *
     * @param      string $key A unique key identifying your data.
     *
     * @return     mixed Data associated with the key.
     *
     * @throws     <b>AgaviStorageException</b> If an error occurs while removing
     *                                          data from this storage.
     *
     * @author     Sean Kerr <skerr@mojavi.org>
     * @since      0.9.0
     */
    abstract function remove($key);

    /**
     * Execute the shutdown procedure.
     *
     * @throws     <b>AgaviStorageException</b> If an error occurs while shutting
     *                                          down this storage.
     *
     * @author     Sean Kerr <skerr@mojavi.org>
     * @since      0.9.0
     */
    abstract function shutdown();

    /**
     * Write data to this storage.
     *
     * The preferred format for a key is directory style so naming conflicts can
     * be avoided.
     *
     * @param      string $key  A unique key identifying your data.
     * @param      mixed  $data Data associated with your key.
     *
     * @throws     <b>AgaviStorageException</b> If an error occurs while writing
     *                                          to this storage.
     *
     * @author     Sean Kerr <skerr@mojavi.org>
     * @since      0.9.0
     */
    abstract function write($key, $data);
}
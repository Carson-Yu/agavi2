<?php
namespace Agavi\Filter;

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
use Agavi\Dispatcher\ExecutionContainer;

/**
 * AgaviBasicSecurityFilter checks security by calling the getCredentials()
 * method of the controller. Once the credential has been acquired,
 * AgaviBasicSecurityFilter verifies the user has the same credential
 * by calling the hasCredentials() method of SecurityUser.
 *
 * @package    agavi
 * @subpackage filter
 *
 * @author     Sean Kerr <skerr@mojavi.org>
 * @author     David Zülke <dz@bitxtender.com>
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      0.9.0
 *
 * @version    $Id$
 */
class SecurityFilter extends Filter implements ControllerFilterInterface, SecurityFilterInterface
{
    /**
     * Execute this filter.
     *
     * @param      FilterChain        $filterChain A FilterChain instance.
     * @param      ExecutionContainer $container The current execution container.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @author     Sean Kerr <skerr@mojavi.org>
     * @since      0.9.0
     */
    public function execute(FilterChain $filterChain, ExecutionContainer $container)
    {
        // get the cool stuff
        $context    = $this->getContext();

        /** @var \SecurityUser $user */
        $user       = $context->getUser();

        // get the current controller instance
        $controllerInstance = $container->getControllerInstance();

        if (!$controllerInstance->isSecure()) {
            // the controller instance does not require authentication, so we can continue in the chain and then bail out early
            return $filterChain->execute($container);
        }

        // get the credential required for this controller
        $credential = $controllerInstance->getCredentials();

        // credentials can be anything you wish; a string, array, object, etc.
        // as long as you add the same exact data to the user as a credential,
        // it will use it and authorize the user as having the credential
        //
        // NOTE: the nice thing about the Controller class is that getCredential()
        //       is vague enough to describe any level of security and can be
        //       used to retrieve such data and should never have to be altered
        if ($user->isAuthenticated() && ($credential === null || $user->hasCredentials($credential))) {
            // the user has access, continue
            $filterChain->execute($container);
        } else {
            if ($user->isAuthenticated()) {
                // the user doesn't have access
                $container->setNext($container->createSystemControllerForwardContainer('secure'));
            } else {
                // the user is not authenticated
                $container->setNext($container->createSystemControllerForwardContainer('login'));
            }
        }
    }
}

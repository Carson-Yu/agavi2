<?php

// +---------------------------------------------------------------------------+
// | This file is part of the Agavi package.                                   |
// | Copyright (c) 2005-2011 the Agavi Project.                                |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code. You can also view the    |
// | LICENSE file online at http://www.agavi.org/LICENSE.txt                   |
// |   vi: set noexpandtab:                                                    |
// |   Local Variables:                                                        |
// |   indent-tabs-mode: t                                                     |
// |   End:                                                                    |
// +---------------------------------------------------------------------------+

namespace Agavi\Dispatcher;

use Agavi\Controller\Controller;
use Agavi\Config\ConfigCache;
use Agavi\Exception\AgaviException;
use Agavi\Exception\ViewException;
use Agavi\Filter\FilterChain;
use Agavi\Request\RequestDataHolder;
use Agavi\Response\Response;
use Agavi\Util\AttributeHolder;
use Agavi\Core\Context;
use Agavi\Config\Config;
use Agavi\Exception\DisabledModuleException;
use Agavi\Exception\FileNotFoundException;
use Agavi\Exception\ConfigurationException;
use Agavi\Util\Toolkit;
use Agavi\Validator\ValidationManager;
use Agavi\View\View;

/**
 * A container used for each controller execution that holds necessary information,
 * such as the output type, the response etc.
 *
 * @package    agavi
 * @subpackage Dispatcher
 *
 * @author     David Zülke <dz@bitxtender.com>
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      0.11.0
 *
 * @version    $Id$
 */

class ExecutionContainer extends AttributeHolder
{
    /**
     * @var        Context The context instance.
     */
    protected $context = null;

    /**
     * @var        string The context name.
     */
    protected $contextName = null;

    /**
     * @var        string The output type name.
     */
    protected $outputTypeName = null;

    /**
     * @var        FilterChain The container's filter chain.
     */
    protected $filterChain = null;

    /**
     * @var        ValidationManager The validation manager instance.
     */
    protected $validationManager = null;

    /**
     * @var        string The request method for this container.
     */
    protected $requestMethod = null;

    /**
     * @var        RequestDataHolder A request data holder with request info.
     */
    protected $requestData = null; // TODO: check if this can actually be protected
                                   // or whether it should be private (would break controller tests though)

    /**
     * @var        RequestDataHolder A pointer to the global request data.
     */
    private $globalRequestData = null;

    /**
     * @var        RequestDataHolder A request data holder with arguments.
     */
    protected $arguments = null;

    /**
     * @var        Response A response instance holding the Controller's output.
     */
    protected $response = null;

    /**
     * @var        OutputType The output type for this container.
     */
    protected $outputType = null;

    /**
     * @var        float The microtime at which this container was initialized.
     */
    protected $microtime = null;

    /**
     * @var        Controller The Controller instance that belongs to this container.
     */
    protected $controllerInstance = null;

    /**
     * @var        ViewException The View instance that belongs to this container.
     */
    protected $viewInstance = null;

    /**
     * @var        string The name of the Controller's Module.
     */
    protected $moduleName = null;

    /**
     * @var        string The name of the Controller.
     */
    protected $controllerName = null;

    /**
     * @var        string Name of the module of the View returned by the Controller.
     */
    protected $viewModuleName = null;

    /**
     * @var        string The name of the View returned by the Controller.
     */
    protected $viewName = null;

    /**
     * @var        ExecutionContainer The next container to execute.
     */
    protected $next = null;

    /**
     * Controller names may contain any valid PHP token, as well as dots and slashes
     * (for sub-controllers).
     */
    const SANE_CONTROLLER_NAME = '/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\/.]*/';
    
    /**
     * View names may contain any valid PHP token, as well as dots and slashes
     * (for sub-controllers).
     */
    const SANE_VIEW_NAME   = '/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\/.]*/';
    
    /**
     * Only valid PHP tokens are allowed in module names.
     */
    const SANE_MODULE_NAME = '/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/';
    
    /**
     * Pre-serialization callback.
     *
     * Will set the name of the context instead of the instance, and the name of
     * the output type instead of the instance. Both will be restored by __wakeup
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @since      0.11.0
     */
    public function __sleep()
    {
        $this->contextName = $this->context->getName();
        if (!empty($this->outputType)) {
            $this->outputTypeName = $this->outputType->getName();
        }
        $arr = get_object_vars($this);
        unset($arr['context'], $arr['outputType'], $arr['requestData'], $arr['globalRequestData']);
        return array_keys($arr);
    }

    /**
     * Post-unserialization callback.
     *
     * Will restore the context and output type instances based on their names set
     * by __sleep.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @since      0.11.0
     */
    public function __wakeup()
    {
        $this->context = Context::getInstance($this->contextName);
        
        if (!empty($this->outputTypeName)) {
            $this->outputType = $this->context->getDispatcher()->getOutputType($this->outputTypeName);
        }
        
        try {
            $this->globalRequestData = $this->context->getRequest()->getRequestData();
        } catch (AgaviException $e) {
            $this->globalRequestData = new RequestDataHolder();
        }
        unset($this->contextName, $this->outputTypeName);
    }

    /**
     * Initialize the container. This will create a response instance.
     *
     * @param      Context $context    The current Context instance.
     * @param      array   $parameters An array of initialization parameters.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @since      0.11.0
     */
    public function initialize(Context $context, array $parameters = array())
    {
        $this->microtime = microtime(true);

        $this->context = $context;

        $this->parameters = $parameters;

        $this->response = $this->context->createInstanceFor('response');
    }

    /**
     * Creates a new container instance with the same output type and request
     * method as this one.
     *
     * @param      string            $moduleName    The name of the module.
     * @param      string            $controllerName    The name of the controller.
     * @param      RequestDataHolder $arguments     A RequestDataHolder with additional
     *                                              request arguments.
     * @param      string            $outputType    Optional name of an initial output type
     *                                              to set.
     * @param      string            $requestMethod Optional name of the request method to
     *                                              be used in this container.
     *
     * @return     ExecutionContainer A new execution container instance,
     *                                     fully initialized.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @since      0.11.0
     */
    public function createExecutionContainer($moduleName = null, $controllerName = null, RequestDataHolder $arguments = null, $outputType = null, $requestMethod = null)
    {
        if ($outputType === null) {
            $outputType = $this->getOutputType()->getName();
        }
        if ($requestMethod === null) {
            $requestMethod = $this->getRequestMethod();
        }
        
        $container = $this->context->getDispatcher()->createExecutionContainer($moduleName, $controllerName, $arguments, $outputType, $requestMethod);
        
        // copy over parameters (could be is_slot, is_forward etc)
        $container->setParameters($this->getParameters());
        
        return $container;
    }

    /**
     * Start execution.
     *
     * This will create an instance of the controller and merge in request parameters.
     *
     * This method returns a response. It is not necessarily the same response as
     * the one of this container, but instead the one that contains the actual
     * content that should be used for output etc, since the container's own
     * response might be empty or invalid due to a "next" container that has been
     * set and executed.
     *
     * @return     Response The "real" response.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @since      0.11.0
     */
    public function execute()
    {
        $dispatcher = $this->context->getDispatcher();

        $dispatcher->countExecution();

        $moduleName = $this->getModuleName();

        try {
            $controllerInstance = $this->getControllerInstance();
        } catch (DisabledModuleException $e) {
            $this->setNext($this->createSystemControllerForwardContainer('module_disabled'));
            return $this->proceed();
        } catch (FileNotFoundException $e) {
            $this->setNext($this->createSystemControllerForwardContainer('error_404'));
            return $this->proceed();
        } // do not catch AgaviClassNotFoundException, we want that to bubble up since it means the class in the controller file is named incorrectly
        
        // copy and merge request data as required
        $this->initRequestData();

        /** @var FilterChain $filterChain */
        $filterChain = $this->getFilterChain();
        
        if (!$controllerInstance->isSimple()) {
            // simple controllers have no filters

            if (Config::get('core.available', false)) {
                // the application is available so we'll register
                // globally defined and module-specific controller filters, otherwise skip them

                // does this controller require security?
                if (Config::get('core.use_security', false)) {
                    // register security filter
                    $filterChain->register($dispatcher->getFilter('security'), 'agavi_security_filter');
                }

                // load filters
                $dispatcher->loadFilters($filterChain, 'controller');
                $dispatcher->loadFilters($filterChain, 'controller', $moduleName);
            }
        }

        // register the execution filter
        $filterChain->register($dispatcher->getFilter('execution'), 'agavi_execution_filter');

        // process the filter chain
        $filterChain->execute($this);
        
        return $this->proceed();
    }
    
    /**
     * Copies and merges the global request data.
     *
     * @author       Felix Gilcher <felix.gilcher@bitextender.com>
     * @since        1.1.0
     */
    protected function initRequestData()
    {
        if ($this->getControllerInstance()->isSimple()) {
            if ($this->arguments !== null) {
                // clone it so mutating it has no effect on the "outside world"
                $this->requestData = clone $this->arguments;
            } else {
                $rdhc = $this->getContext()->getRequest()->getParameter('request_data_holder_class');
                $this->requestData = new $rdhc();
            }
        } else {
            // mmmh I smell awesomeness... clone the RD JIT, yay, that's the spirit
            $this->requestData = clone $this->globalRequestData;

            if ($this->arguments !== null) {
                $this->requestData->merge($this->arguments);
            }
        }
    }
    
    /**
     * Create a system forward container
     *
     * Calling this method will set the attributes:
     *  - requested_module
     *  - requested_controller
     *  - (optional) exception
     * in the appropriate namespace on the created container as well as the global
     * request (for legacy reasons)
     *
     *
     * @param      string          $type The type of forward to create (error_404,
     *                                   module_disabled, secure, login, unavailable).
     * @param      AgaviException  $e    Optional exception thrown by the Dispatcher
     *                                   while resolving the module/controller.
     *
     * @return     ExecutionContainer The forward container.
     *
     * @author     Felix Gilcher <felix.gilcher@bitextender.com>
     * @since      1.0.0
     */
    public function createSystemControllerForwardContainer($type, AgaviException $e = null)
    {
        if (!in_array($type, array('error_404', 'module_disabled', 'secure', 'login', 'unavailable'))) {
            throw new AgaviException(sprintf('Unknown system forward type "%1$s"', $type));
        }
        
        // track the requested module so we have access to the data in the error 404 page
        $forwardInfoData = array(
            'requested_module' => $this->getModuleName(),
            'requested_controller' => $this->getControllerName(),
            'exception'        => $e,
        );
        $forwardInfoNamespace = 'org.agavi.dispatcher.forwards.' . $type;
        
        $moduleName = Config::get('controllers.' . $type . '_module');
        $controllerName = Config::get('controllers.' . $type . '_controller');
        
        if (false === $this->context->getDispatcher()->checkControllerFile($moduleName, $controllerName)) {
            // cannot find unavailable module/controller
            $error = 'Invalid configuration settings: controllers.%3$s_module "%1$s", controllers.%3$s_controller "%2$s"';
            $error = sprintf($error, $moduleName, $controllerName, $type);
            
            throw new ConfigurationException($error);
        }
        
        $forwardContainer = $this->createExecutionContainer($moduleName, $controllerName);
        
        $forwardContainer->setAttributes($forwardInfoData, $forwardInfoNamespace);
        // legacy
        $this->context->getRequest()->setAttributes($forwardInfoData, $forwardInfoNamespace);
        
        return $forwardContainer;
    }
    
    /**
     * Proceed to the "next" container by running it and returning its response,
     * or return our response if there is no "next" container.
     *
     * @return     Response The "real" response.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @since      1.0.0
     */
    protected function proceed()
    {
        if ($this->next !== null) {
            return $this->next->execute();
        } else {
            return $this->getResponse();
        }
    }

    /**
     * Get the Context.
     *
     * @return     Context The Context.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @since      0.11.0
     */
    final public function getContext()
    {
        return $this->context;
    }

    /**
     * Retrieve the ValidationManager
     *
     * @return     ValidationManager The container's ValidationManager
     *                               implementation instance.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @since      0.11.0
     */
    public function getValidationManager()
    {
        if ($this->validationManager === null) {
            $this->validationManager = $this->context->createInstanceFor('validation_manager');
        }
        return $this->validationManager;
    }
    
    /**
     * Get the container's filter chain.
     *
     * @return     FilterChain The container's filter chain.
     *
     * @author     David Zülke <david.zuelke@bitextender.com>
     * @since      1.1.0
     */
    public function getFilterChain()
    {
        if ($this->filterChain === null) {
            $this->filterChain = $this->context->createInstanceFor('filter_chain');
            $this->filterChain->setType(FilterChain::TYPE_CONTROLLER);
        }
        
        return $this->filterChain;
    }
    
    /**
     * Execute the Controller.
     *
     * @return     mixed The processed View information returned by the Controller.
     *
     * @author     David Zülke <david.zuelke@bitxtender.com>
     * @author     Felix Gilcher <felix.gilcher@bitextender.com>
     * @since      1.0.0
     */
    public function runController()
    {
        $viewName = null;

        $request = $this->context->getRequest();
        $validationManager = $this->getValidationManager();

        // get the current controller instance
        $controllerInstance = $this->getControllerInstance();

        // get the current controller information
        $moduleName = $this->getModuleName();
        $controllerName = $this->getControllerName();

        // get the (already formatted) request method
        $method = $this->getRequestMethod();

        $requestData = $this->getRequestData();

        $useGenericMethods = false;
        $executeMethod = 'execute' . $method;
        if (!is_callable(array($controllerInstance, $executeMethod))) {
            $executeMethod = 'execute';
            $useGenericMethods = true;
        }

        if ($controllerInstance->isSimple() || ($useGenericMethods && !is_callable(array($controllerInstance, $executeMethod)))) {
            // this controller will skip validation/execution for this method
            // get the default view
            $key = $request->toggleLock();
            try {
                $viewName = $controllerInstance->getDefaultViewName();
            } catch (\Exception $e) {
                // we caught an exception... unlock the request and rethrow!
                $request->toggleLock($key);
                throw $e;
            }
            $request->toggleLock($key);
            
            // run the validation manager - it's going to take care of cleaning up the request data, and retain "conditional" mode behavior etc.
            // but only if the controller is not simple; otherwise, the (safe) arguments in the request data holder will all be removed
            if (!$controllerInstance->isSimple()) {
                $validationManager->execute($requestData);
            }
        } else {
            if ($this->performValidation()) {
                // execute the controller
                // prevent access to Request::getParameters()
                $key = $request->toggleLock();
                try {
                    $viewName = $controllerInstance->$executeMethod($requestData);
                } catch (\Exception $e) {
                    // we caught an exception... unlock the request and rethrow!
                    $request->toggleLock($key);
                    throw $e;
                }
                $request->toggleLock($key);
            } else {
                // validation failed
                $handleErrorMethod = 'handle' . $method . 'Error';
                if (!is_callable(array($controllerInstance, $handleErrorMethod))) {
                    $handleErrorMethod = 'handleError';
                }
                $key = $request->toggleLock();
                try {
                    $viewName = $controllerInstance->$handleErrorMethod($requestData);
                } catch (\Exception $e) {
                    // we caught an exception... unlock the request and rethrow!
                    $request->toggleLock($key);
                    throw $e;
                }
                $request->toggleLock($key);
            }
        }

        if (is_array($viewName)) {
            // we're going to use an entirely different controller for this view
            $viewModule = $viewName[0];
            $viewName   = $viewName[1];
        } elseif ($viewName !== View::NONE) {
            // use a view related to this controller
            $viewName = Toolkit::evaluateModuleDirective(
                $moduleName,
                'agavi.view.name',
                array(
                    'controllerName' => $controllerName,
                    'viewName' => $viewName,
                )
            );
            $viewModule = $moduleName;
        } else {
            $viewName = View::NONE;
            $viewModule = View::NONE;
        }

        return array($viewModule, $viewName === View::NONE ? View::NONE : Toolkit::canonicalName($viewName));
    }
    
    /**
     * Performs validation for this execution container.
     *
     * @return     bool true if the data validated successfully, false otherwise.
     *
     * @author     David Zülke <david.zuelke@bitxtender.com>
     * @author     Felix Gilcher <felix.gilcher@bitextender.com>
     * @since      1.0.0
     */
    public function performValidation()
    {
        $validationManager = $this->getValidationManager();

        // get the current controller instance
        $controllerInstance = $this->getControllerInstance();
        // get the (already formatted) request method
        $method = $this->getRequestMethod();

        $requestData = $this->getRequestData();
        
        // set default validated status
        $validated = true;

        $this->registerValidators();

        // process validators
        $validated = $validationManager->execute($requestData);

        $validateMethod = 'validate' . $method;
        if (!is_callable(array($controllerInstance, $validateMethod))) {
            $validateMethod = 'validate';
        }

        // process manual validation
        return $controllerInstance->$validateMethod($requestData) && $validated;
    }

    /**
     * Register validators for this execution container.
     *
     * @author     David Zülke <david.zuelke@bitxtender.com>
     * @author     Felix Gilcher <felix.gilcher@bitextender.com>
     * @since      1.0.0
     */
    public function registerValidators()
    {

        // get the current controller instance
        $controllerInstance = $this->getControllerInstance();
        
        // get the current controller information
        $moduleName = $this->getModuleName();
        $controllerName = $this->getControllerName();
        
        // get the (already formatted) request method
        $method = $this->getRequestMethod();

        // get the current controller validation configuration
        $validationConfig = Toolkit::evaluateModuleDirective(
            $moduleName,
            'agavi.validate.path',
            array(
                'moduleName' => $moduleName,
                'controllerName' => $controllerName,
            )
        );
        $validationManager = $this->getValidationManager();

        if (is_readable($validationConfig)) {
            // load validation configuration
            // do NOT use require_once
            require(ConfigCache::checkConfig($validationConfig, $this->context->getName()));
        }

        // manually load validators
        $registerValidatorsMethod = 'register' . $method . 'Validators';
        if (!is_callable(array($controllerInstance, $registerValidatorsMethod))) {
            $registerValidatorsMethod = 'registerValidators';
        }
        $controllerInstance->$registerValidatorsMethod();
    }
    
    /**
     * Retrieve this container's request method name.
     *
     * @return     string The request method name.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @since      1.0.0
     */
    public function getRequestMethod()
    {
        return $this->requestMethod;
    }

    /**
     * Set this container's request method name.
     *
     * @param      string $requestMethod The request method name.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @since      1.0.0
     */
    public function setRequestMethod($requestMethod)
    {
        $this->requestMethod = $requestMethod;
    }

    /**
     * Retrieve this container's request data holder instance.
     *
     * @return     RequestDataHolder The request data holder.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @since      0.11.0
     */
    final public function getRequestData()
    {
        return $this->requestData;
    }

    /**
     * Set this container's global request data holder reference.
     *
     * @param      RequestDataHolder $rd The request data holder.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @since      0.11.0
     */
    final public function setRequestData(RequestDataHolder $rd)
    {
        $this->globalRequestData = $rd;
    }

    /**
     * Get this container's request data holder instance for additional arguments.
     *
     * @return     RequestDataHolder The additional arguments.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @since      0.11.0
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Set this container's request data holder instance for additional arguments.
     *
     * @return     RequestDataHolder The request data holder.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @since      0.11.0
     */
    public function setArguments(RequestDataHolder $arguments)
    {
        $this->arguments = $arguments;
    }

    /**
     * Retrieve this container's response instance.
     *
     * @return     Response The Response instance for this controller.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @since      0.11.0
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Set a new response.
     *
     * @param      Response $response A new Response instance.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @since      0.11.0
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;
        // do not set the output type on the response here!
    }

    /**
     * Retrieve the output type of this container.
     *
     * @return     OutputType The output type object.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @since      0.11.0
     */
    public function getOutputType()
    {
        return $this->outputType;
    }

    /**
     * Set a different output type for this container.
     *
     * @param      OutputType $outputType An output type object.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @since      0.11.0
     */
    public function setOutputType(OutputType $outputType)
    {
        $this->outputType = $outputType;
        if ($this->response) {
            $this->response->setOutputType($outputType);
        }
    }

    /**
     * Retrieve this container's microtime.
     *
     * @return     string A string representing the microtime this container was
     *                    initialized.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @since      0.11.0
     */
    public function getMicrotime()
    {
        return $this->microtime;
    }

    /**
     * Retrieve this container's controller instance.
     *
     * @return     Controller An controller implementation instance.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @since      0.11.0
     */
    public function getControllerInstance()
    {
        if ($this->controllerInstance === null) {
            $dispatcher = $this->context->getDispatcher();

            $moduleName = $this->getModuleName();
            $controllerName = $this->getControllerName();
            
            $this->controllerInstance = $dispatcher->createControllerInstance($moduleName, $controllerName);
            
            // initialize the controller
            $this->controllerInstance->initialize($this);
        }
        
        return $this->controllerInstance;
    }

    /**
     * Retrieve this container's view instance.
     *
     * @return     View A view implementation instance.
     *
     * @author     Ross Lawley <ross.lawley@gmail.com>
     * @since      0.11.0
     */
    public function getViewInstance()
    {
        if ($this->viewInstance === null) {
            // get the view instance
            $this->viewInstance = $this->getContext()->getDispatcher()->createViewInstance($this->getViewModuleName(), $this->getViewName());
            // initialize the view
            $this->viewInstance->initialize($this);
        }
        
        return $this->viewInstance;
    }

    /**
     * Set this container's view instance.
     *
     * @param      View $viewInstance A view implementation instance.
     *
     * @author     Ross Lawley <ross.lawley@gmail.com>
     * @since      0.11.0
     */
    public function setViewInstance($viewInstance)
    {
        return $this->viewInstance = $viewInstance;
    }

    /**
     * Retrieve this container's module name.
     *
     * @return     string A module name.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @since      0.11.0
     */
    public function getModuleName()
    {
        return $this->moduleName;
    }

    /**
     * Retrieve this container's controller name.
     *
     * @return     string An controller name.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @since      0.11.0
     */
    public function getControllerName()
    {
        return $this->controllerName;
    }

    /**
     * Retrieve this container's view module name. This is the name of the module of
     * the View returned by the Controller.
     *
     * @return     string A view module name.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @since      0.11.0
     */
    public function getViewModuleName()
    {
        return $this->viewModuleName;
    }

    /**
     * Retrieve this container's view name.
     *
     * @return     string A view name.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @since      0.11.0
     */
    public function getViewName()
    {
        return $this->viewName;
    }

    /**
     * Set the module name for this container.
     *
     * @param      string $moduleName A module name.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @since      0.11.0
     */
    public function setModuleName($moduleName)
    {
        if (null === $moduleName) {
            $this->moduleName = null;
        } elseif (preg_match(self::SANE_MODULE_NAME, $moduleName)) {
            $this->moduleName = $moduleName;
        } else {
            throw new AgaviException(sprintf('Invalid module name "%1$s"', $moduleName));
        }
    }

    /**
     * Set the controller name for this container.
     *
     * @param      string $controllerName An controller name.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @since      0.11.0
     */
    public function setControllerName($controllerName)
    {
        if (null === $controllerName) {
            $this->controllerName = null;
        } elseif (preg_match(self::SANE_CONTROLLER_NAME, $controllerName)) {
            $controllerName = Toolkit::canonicalName($controllerName);
            $this->controllerName = $controllerName;
        } else {
            throw new AgaviException(sprintf('Invalid controller name "%1$s"', $controllerName));
        }
    }

    /**
     * Set the view module name for this container.
     *
     * @param      string $viewModuleName A view module name.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @since      0.11.0
     */
    public function setViewModuleName($viewModuleName)
    {
        if (null === $viewModuleName) {
            $this->viewModuleName = null;
        } elseif (preg_match(self::SANE_MODULE_NAME, $viewModuleName)) {
            $this->viewModuleName = $viewModuleName;
        } else {
            throw new AgaviException(sprintf('Invalid view module name "%1$s"', $viewModuleName));
        }
    }

    /**
     * Set the module name for this container.
     *
     * @param      string $viewName A view name.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @since      0.11.0
     */
    public function setViewName($viewName)
    {
        if (null === $viewName) {
            $this->viewName = null;
        } elseif (preg_match(self::SANE_VIEW_NAME, $viewName)) {
            $viewName = Toolkit::canonicalName($viewName);
            $this->viewName = $viewName;
        } else {
            throw new AgaviException(sprintf('Invalid view name "%1$s"', $viewName));
        }
    }

     /**
     * Check if a "next" container has been set.
     *
     * @return     bool True, if a container for eventual execution has been set.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @since      0.11.0
     */
    public function hasNext()
    {
        return $this->next !== null;
    }

    /**
     * Get the "next" container.
     *
     * @return     ExecutionContainer The "next" container, of null if unset.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @since      0.11.0
     */
    public function getNext()
    {
        return $this->next;
    }

    /**
     * Set the container that should be executed once this one finished running.
     *
     * @param      ExecutionContainer $container An execution container instance.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @since      0.11.0
     */
    public function setNext(ExecutionContainer $container)
    {
        $this->next = $container;
    }

    /**
     * Remove a possibly set "next" container.
     *
     * @return     ExecutionContainer The removed "next" container, or null
     *                                     if none had been set.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @since      0.11.0
     */
    public function clearNext()
    {
        $retval = $this->next;
        $this->next = null;
        return $retval;
    }
}

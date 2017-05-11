<?php
namespace Agavi\Validator;

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

/**
 * ValidationError stores an error message and the fields of an error.
 *
 * @package    agavi
 * @subpackage validator
 *
 * @author     Dominik del Bondio <ddb@bitxtender.com>
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      0.11.0
 *
 * @version    $Id$
 */
class ValidationError
{
    /**
     * @var        string The message for this error.
     */
    protected $message = null;

    /**
     * @var        string The name of the message.
     */
    protected $name = null;

    /**
     * @var        ValidationArgument[] The fields this error affects.
     */
    protected $arguments = array();

    /**
     * @var        ValidationIncident The incident in which this error
     *                                     occurred.
     */
    protected $incident = null;

    /**
     * Constructor
     *
     * @param      string $message   The message of this error.
     * @param      string $name      The name of the message.
     * @param      array  $arguments The arguments affected by this error.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     */
    public function __construct($message, $name, array $arguments)
    {
        $this->message = $message;
        $this->name = $name;
        foreach ($arguments as $argument) {
            if (!($argument instanceof ValidationArgument)) {
                $argument = new ValidationArgument($argument);
            }
            $this->arguments[$argument->getHash()] = $argument;
        }
    }

    /**
     * Sets the name of this error.
     *
     * @param      string $name The error name.
     *
     * @author     David Zülke <david.zuelke@bitextender.com>
     * @author     Dominik del Bondio <dominik.del.bondio@bitextender.com>
     * @since      1.0.0
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Sets the message index of this error.
     *
     * @param      string $messageIndex The message index.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     *
     * @deprecated Superseded by setName()
     */
    public function setMessageIndex($messageIndex)
    {
        $this->setName($messageIndex);
    }

    /**
     * Retrieves the name of this error.
     *
     * @return     string The error name.
     *
     * @author     David Zülke <david.zuelke@bitextender.com>
     * @author     Dominik del Bondio <dominik.del.bondio@bitextender.com>
     * @since      1.0.0
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Retrieves the message index of this error.
     *
     * @return     string The message index.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     *
     * @deprecated Superseded by getName()
     */
    public function getMessageIndex()
    {
        return $this->getName();
    }

    /**
     * Sets the message of this error.
     *
     * @param      string $message The message.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * Retrieves the message of this error.
     *
     * @return     string The message.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Sets the incident which caused this error.
     *
     * @param      ValidationIncident $incident The incident.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     */
    public function setIncident(ValidationIncident $incident)
    {
        $this->incident = $incident;
    }

    /**
     * Retrieves the incident which caused this error.
     *
     * @return     ValidationIncident The incident.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     */
    public function getIncident()
    {
        return $this->incident;
    }

    /**
     * Retrieves the arguments which caused this error.
     *
     * @return     ValidationArgument[] An array of ValidationArgument.
     *
     * @author     Dominik del Bondio <dominik.del.bondio@bitextender.com>
     * @since      1.0.0
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Checks if this error was caused for the given argument
     *
     * @param      ValidationArgument $argument The argument.
     *
     * @return     bool The result.
     *
     * @author     Dominik del Bondio <dominik.del.bondio@bitextender.com>
     * @since      1.0.0
     */
    public function hasArgument(ValidationArgument $argument)
    {
        return isset($this->arguments[$argument->getHash()]);
    }
    
    /**
     * Retrieves the fields which caused this error.
     *
     * @return     array An array of field names.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     */
    public function getFields()
    {
        $fields = array();
        foreach ($this->arguments as $argument) {
            $fields[] = $argument->getName();
        }
        return $fields;
    }

    /**
     * Checks if this error was caused for the given field
     *
     * @param      string $fieldname The name of the field to check.
     *
     * @return     bool The result.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     */
    public function hasField($fieldname)
    {
        return $this->hasArgument(new ValidationArgument($fieldname));
    }
}

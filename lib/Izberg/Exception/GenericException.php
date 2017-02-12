<?php
namespace Izberg\Exception;
use Exception;
use RuntimeException;

/**
 * GenericException Representation of generic exception
 */
class GenericException extends Exception
{
  /**
   * Constructor
   *
   * @param message[optional]
   * @param code[optional]
   * @param previous[optional]
   */
  public function __construct ($message = null, $code = null, $previous = null)
  {
    parent::__construct($message, $code, $previous);
  }
}

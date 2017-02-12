<?php
class Sebfie_Izberg_Model_Autoloader
{

	protected static $registered = false;

	public function addAutoloader(Varien_Event_Observer $observer)
	{
		// this should not be necessary.  Just being done as a check
		if (self::$registered) {
			return;
		}
		spl_autoload_register(array($this, 'autoload'), false, true);
		self::$registered = true;
	}


	public function autoload($class)
	{
		$classFile = str_replace('\\', '/', $class) . '.php';
		// Only include a namespaced class.  This should leave the regular Magento autoloader alone
		if (strpos($classFile, '/') !== false) {
			if (stream_resolve_include_path($classFile)) {
				include $classFile;
				return true;
			} else {
				if (strpos($class,'Izberg\Resource') !== false) {
					$split = explode('\\', $class);
					eval("namespace Izberg\Resource;use Izberg\Resource;class " . end($split) . " extends Resource {};");
						return true;
				}
				return false;
			}
		}
		return true;
	}

}

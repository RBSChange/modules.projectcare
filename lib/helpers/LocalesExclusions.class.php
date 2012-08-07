<?php
/**
 * @package modules.projectcare.lib.services
 * 
 * provide exclusions intended to customize results of 
 */
class projectcare_LocalesExclusions extends ModuleBaseService
{
	
	/**
	 * Singleton
	 *
	 * @var projectcare_LocalesService
	 */
	private static $instance;
	protected $_types = array('localizables', 'localizeds');
	
	/**
	 *
	 * @return projectcare_LocalesService
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		
		return self::$instance;
	}
	
	/**
	 * sample, intended to illustrate how to fetching exclusions should likel
	 * asterisk operator can be used as used to match any number of characters
	 * 
	 * default supported keys are 'localizables' and 'localizeds'
	 * 
	 * @return array
	 * 
	 * TODO: use a configuration file to store patterns
	 */
	public function getExclusions()
	{
		$input = array('localizeds' => array('t.*.templates.*', 't.*.skin.*'), 
			'localizables' => array('f.date.date.smart-', 'm.catalog.attributes.label-', '*.workflow.bo.*'));
		$input = array('localizeds' => array(), 'localizables' => array());
		return $this->prepareExclusions($input);
	}
	
	/**
	 * return an array of type supposed to be supported in exclusions
	 *  
	 * @return array
	 */
	public function getSupporteds()
	{
		return array_unique(array_merge(array_keys($this->getExclusions()), $this->_types));
	}
	
	/**
	 * intended to clean a simple array (list) of localiz(able|ed) strings
	 * removing keys defined as excluded, based on pattern matching: a reduced subset of regex
	 * asterisk operator can be used as used to match any number of characters
	 * 
	 * @param array $input
	 * @param $type should be: ('localizables'|'localizeds') or any key defined by configuration
	 * @return array where strings matching one or more pattern have been removed
	 */
	public function cleanArray($input, $type)
	{
		if (!in_array($type, $this->getSupporteds()))
		{
			throw new Exception("Undefined type: `$type'");
		}
		
		$exclusions = $this->getExclusions();
		if (!in_array($type, array_keys($exclusions)))
		{
			return $input;
		}
		
		foreach ($input as $i => $localizable)
		{
			foreach ($exclusions[$type] as $regex)
			{
				preg_match_all($regex, $localizable, $matches);
				if ($matches[0])
				{
					unset($input[$i]);
				}
			}
		}
		return $input;
	}
	
	/**
	 * escape exclusions before use
	 * 
	 * @return array
	 */
	protected function prepareExclusions($exclusions)
	{
		foreach ($exclusions as $type => $values)
		{
			foreach ($values as $i => $value)
			{
				$exclusions[$type][$i] = '#^' . str_replace('\*', '.*', preg_quote($value, '#')) . '$#';
			}
		}
		return $exclusions;
	}
}

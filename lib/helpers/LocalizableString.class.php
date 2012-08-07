<?php
/**
 * @package modules.projectcare.lib.services
 * 
 * naive class intended to have a simple representation of a localizable string 
 * based on its key and their files
 */
class projectcare_LocalizableString
{
	public $files = array();
	public $key = '';
		
	/**
	 * @param string $key 
	 */
	public function __construct($key)
	{
		$this->key = $this->normalizeKey($key);
	}
	
	/**
	 * @param string $key 
	 * @return string
	 */

	public function normalizeKey($key)
	{
		$parts = explode('.', $key);
		switch ($parts[0]) {
			case 'framework':
				$parts[0] = 'f';
				break;
			case 'modules':
				$parts[0] = 'm';
				break;
			case 'themes':
				$parts[0] = 't';
				break;
		}
		return join('.', $parts);
	}
	
	/**
	 * @param string $filepath
	 * @return $this
	 */
	public function add_file($filepath)
	{
		if (!in_array($filepath, $this->files))
		{
			$this->files[] = $filepath;
		}
		sort($this->files);
		return $this;
	}
	
	/**
	 * return localized string
	 * return key if not localizable or empty string if $nullify
	 * 
	 * @param string $lang
	 * @return string
	 */
	public function localize($lang, $nullify=false)
	{
		$result = LocaleService::getInstance()->formatKey($lang, $this->key);
		if ($nullify) {
			return (string) (($result == $this->key) ? null : $result);
		} else {
			return $result;
		}
	}
	
	/**
	 * return true is key seems to be localized in $lang
	 * 
	 * @param string $lang
	 * @return boolean
	 */
	public function is_localized($lang)
	{
		return (bool) $this->localize($lang, true);
	}

	/**
	 * return string
	 */
	public function __toString()
	{
		return $this->key;
	}
}

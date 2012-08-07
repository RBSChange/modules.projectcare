<?php
/**
 * @package modules.projectcare.lib.services
 */
class projectcare_LocalesServiceCheck extends ModuleBaseService
{
	/**
	 * Singleton
	 *
	 * @var projectcare_LocalesService
	 */
	private static $instance;
	
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
	 *
	 */
	public function getUntranslateds($in = null)
	{
		$localizables = projectcare_LocalesServiceIndexer::getInstance()->getLocalizables();
		$untranslateds = array();
		
		foreach ($this->getLanguages() as $lang)
		{
			$untranslateds[$lang] = array();
			foreach ($localizables as $k => $locale)
			{
				if (!$locale->is_localized($lang))
				{
					$untranslateds[$lang][$k] = $locale;
					ksort($untranslateds[$lang]);
				}
			}
		}
		
		if ($in)
		{
			if (!in_array($in, $this->getLanguages()))
			{
				throw new Exception("Unsupported lang: `$in'");
			}
			else
			{
				return array($in => $untranslateds[$in]);
			}
		}
		
		return $untranslateds;
	}
	
	/**
	 * find unused locale keys
	 * @return array of keys which are defined in XML locale files but unused in both files (themes, php and xml)
	 */
	public function getUnuseds()
	{
		$indexer = projectcare_LocalesServiceIndexer::getInstance();
		$localizeds = $indexer->getLocalizeds();
		$localizables = $indexer->getLocalizables();
		$unuseds = array();
		
		foreach ($localizeds as $key)
		{
			// file_put_contents('php://stderr', $key."\n");
			if (!in_array($key, $localizables))
			{
				$unuseds[] = $key;
			}
		}
		return $unuseds;
	}
	
	/**
	 */
	public function getLocalesFromDB()
	{
		$query = 'SELECT DISTINCT key_path, id FROM `f_locale`';
		$sql = f_persistentdocument_PersistentProviderMySql::getInstance();
		$rec = $sql->executeSQLSelect($query);
		foreach ($rec->fetchAll() as $row)
		{
		}
	}
	
	/**
	 * return supported languages
	 * 
	 * @return array
	 */
	public function getLanguages()
	{
		return RequestContext::getInstance()->getSupportedLanguages();
	}
}

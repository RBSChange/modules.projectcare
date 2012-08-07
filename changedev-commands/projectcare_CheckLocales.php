<?php
/**
 * commands_projectcare_CheckLocales
 * @package modules.projectcare.command
 */
class commands_projectcare_CheckLocales extends commands_AbstractChangeCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "unused, localized, missing [fr|en]";
	}
	
	/**
	 * @return String
	 */
	function getDescription()
	{
		return "Check locales in project";
	}
	
	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 */
	protected function validateArgs($params, $options)
	{
		if (count($params) == 1 || (count($params) == 2 && $params[0] == 'missing'))
		{
			return true;
		}
		
		return false;
	}
	
	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Check Project Locales ==");
		
		$this->loadFramework();
		
		$type = $params[0];
		$lang = $params[1];
		
		switch ($type)
		{
			case 'unused' :
				$unuseds = projectcare_LocalesServiceCheck::getInstance()->getUnuseds();
				echo implode(PHP_EOL, $unuseds), PHP_EOL;
				echo 'Count: ', count($unuseds), PHP_EOL;
				break;
			case 'localized' :
				$localizeds = projectcare_LocalesServiceIndexer::getInstance()->getLocalizeds();
				echo implode(PHP_EOL, $localizeds), PHP_EOL;
				echo 'Count: ', count($localizeds), PHP_EOL;
				break;
			case 'missing' :
				$untranslateds = projectcare_LocalesServiceCheck::getInstance()->getUntranslateds($lang);
				$missings = 0;
				$resultString = array();
				foreach ($untranslateds as $lang => $item)
				{
					$resultString[] = '-- missing localizations in locale ' . $lang . ' : ' . count($item);
					$missings += count($item);
					
					foreach ($item as $key => $object)
					{
						$resultString[] = '  -- ' . $key;
					}
				}
				
				echo implode(PHP_EOL, $resultString), PHP_EOL;
				echo 'Total count: ', $missings, PHP_EOL;
				break;
		}
		
		return $this->quitOk("See report");
	}
}
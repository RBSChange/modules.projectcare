<?php
/**
 * commands_projectcare_Check
 * @package modules.projectcare.command
 */
class commands_projectcare_Check extends commands_AbstractChangeCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "Check quality of code";
	}
	
	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 */
	protected function validateArgs($params, $options)
	{
		return true;
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Check Project Quality ==");

		$this->loadFramework();
		
		projectcare_CommandCheckService::getInstance()->execute();
		
		return $this->quitOk("See report");
	}
}
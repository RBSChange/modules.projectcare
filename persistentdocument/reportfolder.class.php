<?php
/**
 * Class where to put your custom methods for document projectcare_persistentdocument_reportfolder
 * @package modules.projectcare.persistentdocument
 */
class projectcare_persistentdocument_reportfolder extends projectcare_persistentdocument_reportfolderbase 
{
	/**
	 * @return string
	 */
	public function getLabel()
	{
		return LocaleService::getInstance()->transBO(parent::getLabel(), array('ucf'));
	}
}
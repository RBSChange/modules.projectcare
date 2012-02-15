<?php
/**
 * Class where to put your custom methods for document projectcare_persistentdocument_report
 * @package modules.projectcare.persistentdocument
 */
class projectcare_persistentdocument_report extends projectcare_persistentdocument_reportbase 
{
	/**
	 * @return string
	 */
	public function getLabel()
	{
		return LocaleService::getInstance()->transBO(parent::getLabel(), array('ucf'));
	}
	
	/**
	 * @return string
	 */
	public function getFilePath()
	{
		$file = $this->getCsvFile();
		return $file->getDocumentService()->getOriginalPath($file, true);
	}
	
	/**
	 * @param string $csvFragment
	 */
	public function appendFragmentToCsv($csvFragment)
	{
		f_util_FileUtils::append($this->getFilePath(), $csvFragment);
	}
	
	/**
	 * @return boolean
	 */
	public function isRunning()
	{
		return $this->getEndDate() === null;
	}
}
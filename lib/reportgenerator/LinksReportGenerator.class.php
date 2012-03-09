<?php

class projectcare_LinksReportGenerator extends projectcare_ReportGenerator
{
	/**
	 * 
	 */
	protected function getReportFileName()
	{
		$fileName = 'reportLinks_' . date_Formatter::format(date_Calendar::getInstance(), 'Y-m-d_H-i');
		return $fileName;
	}
	
	/**
	 * 
	 */
	protected function getReportFileLabel()
	{
		return "CSV link report";
	}
	
	/**
	 * @return array<string => string>
	 */
	protected function getRowsFields()
	{
		return array('httpStatus' => 'httpStatus', 'curlError' => 'curlError', 'containerId' => 'containerId', 'containerUrl' => 'containerUrl', 
			'containerLabel' => 'containerLabel', 'containerModel' => 'containerModel', 'containerLang' => 'containerLang', 'linkType' => 'linkType', 
			'propertyName' => 'propertyName', 'targetId' => 'targetId', 'targetUrl' => 'targetUrl');
	}
	
	/**
	 * 
	 */
	protected function getNotificationCodeName()
	{
		return 'modules_projectcare/checklinksdone';
	}
	
	/**
	 * 
	 */
	protected function getReportLabel()
	{
		return 'm.projectcare.bo.general.check-links-report';
	
	}
	
	/**
	 * 
	 */
	protected function getReportFolderCodeReference()
	{
		return "check-links";
	
	}

}
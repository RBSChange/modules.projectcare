<?php

class projectcare_OrphanPageReferenceReportGenerator extends projectcare_ReportGenerator
{
	/**
	 * 
	 */
	protected function getReportFileName()
	{
		$fileName = 'reportOrphanPageReference_' . date_Formatter::format(date_Calendar::getInstance(), 'Y-m-d_H-i');
		return $fileName;
	}
	
	/**
	 * 
	 */
	protected function getReportFileLabel()
	{
		return "CSV orphan page reference report";
	}
	
	/**
	 * @return array<string => string>
	 */
	protected function getRowsFields()
	{
		return array('id' => 'id', 'path' => 'path');
	}
	
	/**
	 * 
	 */
	protected function getNotificationCodeName()
	{
		return 'modules_projectcare/checkorphanpagereference';
	}
	
	/**
	 * 
	 */
	protected function getReportLabel()
	{
		return 'm.projectcare.bo.general.check-orphan-page-reference-report';
	
	}
	
	/**
	 * 
	 */
	protected function getReportFolderCodeReference()
	{
		return "orphan-page-reference";
	
	}

}
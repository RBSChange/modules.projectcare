<?php

class projectcare_DocumentInBlockReportGenerator extends projectcare_ReportGenerator
{
	/**
	 * 
	 */
	protected function getReportFileName()
	{
		$fileName = 'reportDocumentInBlock_' . date_Formatter::format(date_Calendar::getInstance(), 'Y-m-d_H-i');
		return $fileName;
	}
	
	/**
	 * 
	 */
	protected function getReportFileLabel()
	{
		return "CSV document in block report";
	}
	
	/**
	 * @return array<string => string>
	 */
	protected function getRowsFields()
	{
		return array('containerId' => 'containerId', 'containerUrl' => 'containerUrl', 'containerLabel' => 'containerLabel', 
			'containerModel' => 'containerModel', 'containerLang' => 'containerLang', 'targetId' => 'targetId', 'blockType' => 'blockType',  'paramName' => 'paramName');
	}
	
	/**
	 * 
	 */
	protected function getNotificationCodeName()
	{
		return 'modules_projectcare/checkdocumentinblocksdone';
	}
	
	/**
	 * 
	 */
	protected function getReportLabel()
	{
		return 'm.projectcare.bo.general.check-document-in-blocks-report';
	
	}
	
	/**
	 * 
	 */
	protected function getReportFolderCodeReference()
	{
		return "check-document-in-blocks";
	
	}

}
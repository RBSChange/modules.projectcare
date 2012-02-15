<?php
/**
 * projectcare_ReportScriptDocumentElement
 * @package modules.projectcare.persistentdocument.import
 */
class projectcare_ReportScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return projectcare_persistentdocument_report
     */
    protected function initPersistentDocument()
    {
    	return projectcare_ReportService::getInstance()->getNewDocumentInstance();
    }
    
    /**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_projectcare/report');
	}
}
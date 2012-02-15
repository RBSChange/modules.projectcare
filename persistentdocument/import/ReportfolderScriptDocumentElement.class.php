<?php
/**
 * projectcare_ReportfolderScriptDocumentElement
 * @package modules.projectcare.persistentdocument.import
 */
class projectcare_ReportfolderScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return projectcare_persistentdocument_reportfolder
     */
    protected function initPersistentDocument()
    {
    	return projectcare_ReportfolderService::getInstance()->getNewDocumentInstance();
    }
    
    /**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_projectcare/reportfolder');
	}
}
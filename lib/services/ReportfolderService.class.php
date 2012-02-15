<?php
/**
 * projectcare_ReportfolderService
 * @package modules.projectcare
 */
class projectcare_ReportfolderService extends generic_FolderService
{
	/**
	 * @var projectcare_ReportfolderService
	 */
	private static $instance;

	/**
	 * @return projectcare_ReportfolderService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}

	/**
	 * @return projectcare_persistentdocument_reportfolder
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_projectcare/reportfolder');
	}

	/**
	 * Create a query based on 'modules_projectcare/reportfolder' model.
	 * Return document that are instance of modules_projectcare/reportfolder,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_projectcare/reportfolder');
	}
	
	/**
	 * Create a query based on 'modules_projectcare/reportfolder' model.
	 * Only documents that are strictly instance of modules_projectcare/reportfolder
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_projectcare/reportfolder', false);
	}
	
	/**
	 * @return projectcare_persistentdocument_reportfolder
	 */
	public function getCheckLinksReportFolder()
	{
		return $this->getByCode('check-links');
	}
	
	/**
	 * @return projectcare_persistentdocument_reportfolder
	 */
	protected function getByCode($code)
	{
		return $this->createQuery()->add(Restrictions::eq('codeReference', $code))->findUnique();
	}		
}
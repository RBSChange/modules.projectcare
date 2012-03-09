<?php
/**
 * projectcare_ReportService
 * @package modules.projectcare
 */
class projectcare_ReportService extends f_persistentdocument_DocumentService
{
	/**
	 * @var projectcare_ReportService
	 */
	private static $instance;
	
	/**
	 * @return projectcare_ReportService
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
	 * @return projectcare_persistentdocument_report
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_projectcare/report');
	}
	
	/**
	 * Create a query based on 'modules_projectcare/report' model.
	 * Return document that are instance of modules_projectcare/report,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_projectcare/report');
	}
	
	/**
	 * Create a query based on 'modules_projectcare/report' model.
	 * Only documents that are strictly instance of modules_projectcare/report
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_projectcare/report', false);
	}
	
	/**
	 * @param projectcare_persistentdocument_report $document
	 * @param string $forModuleName
	 * @param array $allowedSections
	 * @return array
	 */
	public function getResume($document, $forModuleName, $allowedSections = null)
	{
		$resume = parent::getResume($document, $forModuleName, $allowedSections);
		
		$ls = LocaleService::getInstance();
		$link = LinkHelper::getUIActionLink('media', 'BoDisplay')->setQueryParameter('cmpref', $document->getCsvFile()->getId());
		$link->setQueryParameter('lang', $document->getLang())->setQueryParameter('forceDownload', 'true');
		$resume['properties']['downloadCSV'] = array('href' => $link->getUrl(), 
			'label' => $ls->transBO('m.projectcare.bo.general.download-file', array('ucf')));
		$resume['properties']['beginDate'] = date_Formatter::toDefaultDatetimeBO($document->getUICreationdate());
		if (!$document->isRunning())
		{
			$resume['properties']['isRunning'] = $ls->transBO('m.uixul.bo.general.no', array('ucf'));
			$resume['properties']['endDate'] = date_Formatter::toDefaultDatetimeBO($document->getUIEndDate());
		}
		else
		{
			$resume['properties']['isRunning'] = $ls->transBO('m.uixul.bo.general.yes', array('ucf'));
		}
		
		return $resume;
	}
	
	/**
	 * @param projectcare_persistentdocument_report $document
	 * @param string $moduleName
	 * @param string $treeType
	 * @param array<string, string> $nodeAttributes
	 */
	public function addTreeAttributes($document, $moduleName, $treeType, &$nodeAttributes)
	{
		$ls = LocaleService::getInstance();
		$nodeAttributes['mediaId'] = $document->getCsvFile()->getId();
		$nodeAttributes['beginDate'] = date_Formatter::toDefaultDatetimeBO($document->getUICreationdate());
		$nodeAttributes['isRunningBool'] = $document->isRunning();
		if (!$nodeAttributes['isRunningBool'])
		{
			$nodeAttributes['isRunning'] = $ls->transBO('m.uixul.bo.general.no', array('ucf'));
			$nodeAttributes['endDate'] = date_Formatter::toDefaultDatetimeBO($document->getUIEndDate());
		}
		else
		{
			$nodeAttributes['isRunning'] = $ls->transBO('m.uixul.bo.general.yes', array('ucf'));
		}
	}

}
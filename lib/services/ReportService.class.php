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
	 * @return projectcare_persistentdocument_report
	 */
	public function initializeCSVReport()
	{
		// Generate the media.
		$fileName = 'report_' . date_Formatter::format(date_Calendar::getInstance(), 'Y-m-d_H-i') . '.csv';
		$file = media_FileService::getInstance()->getNewDocumentInstance();
		$file->setLabel('CSV Report');
		$file->setNewFileName($fileName);
		$file->setFilename($fileName);
	
		// Generate the report.
		$parent = projectcare_ReportfolderService::getInstance()->createQuery()->findUnique();
		$report = projectcare_ReportService::getInstance()->getNewDocumentInstance();
		$report->setLabel('m.projectcare.bo.general.check-links-report');
		$report->setCsvFile($file);
		$report->save();
		TreeService::getInstance()->newFirstChild($parent->getId(), $report->getId());
	
		// Initialize the CSV file.
		$options = $this->getCSVExportOptions();
		$options->outputHeaders = true;
		$csvFragment = f_util_CSVUtils::export($this->getLinksFields(), array(), $options);
		$path = $report->getFilePath();
		f_util_FileUtils::mkdir(dirname($path));
		f_util_FileUtils::write($path, $csvFragment, f_util_FileUtils::OVERRIDE);
		
		return $report;
	}
	
	/**
	 * @param projectcare_persistentdocument_report $report
	 */
	public function finalizeReport($report)
	{
		$report->setEndDate(date_Calendar::getInstance());
		$report->save();
		
		// Send notification.
		$ps = f_permission_PermissionService::getInstance();
		$ns = notification_NotificationService::getInstance();
		$rootId = ModuleService::getInstance()->getRootFolderId('projectcare');
		$accessorIds = $ps->getAccessorIdsForRoleByDocumentId('modules_projectcare.Admin', $rootId);
		foreach (users_UserService::getInstance()->convertToPublishedUserIds($accessorIds) as $userId)
		{
			$user = users_persistentdocument_user::getInstanceById($userId);
			$notif = $ns->getConfiguredByCodeName('modules_projectcare/checklinksdone', null, $user->getLang());
			if ($notif instanceof notification_persistentdocument_notification)
			{
				$user->getDocumentService()->sendNotificationToUserCallback($notif, $user);
			}
		}
	}
	
	/**
	 * @param projectcare_persistentdocument_report $report
	 * @param array $links
	 */
	public function appendLinksToCsv($report, $links)
	{
		$csvFragment = f_util_CSVUtils::export($this->getLinksFields(), $links, $this->getCSVExportOptions());
		$report->appendFragmentToCsv($csvFragment);
	}
	
	/**
	 * @return array<string => string>
	 */
	protected function getLinksFields()
	{
		return array(
			'httpStatus' => 'httpStatus',
			'curlError' => 'curlError',
			'containerId' => 'containerId',
			'containerLabel' => 'containerLabel',
			'containerModel' => 'containerModel',
			'containerLang' => 'containerLang',
			'linkType' => 'linkType',
			'propertyName' => 'propertyName',
			'targetId' => 'targetId',
			'url' => 'url'
		);
	}
	
	/**
	 * @return array<string => string>
	 */
	protected function getCSVExportOptions()
	{
		$options = new f_util_CSVUtils_export_options();
		$options->outputHeaders = false;
		$options->separator = ';';
		return $options;
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
		$resume['properties']['downloadCSV'] = array('href' => $link->getUrl(), 'label' => $ls->transBO('m.projectcare.bo.general.download-file', array('ucf')));
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
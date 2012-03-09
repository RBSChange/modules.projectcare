<?php

abstract class projectcare_ReportGenerator
{
	
	private static $instance = array();
	
	public static function getInstance($className)
	{
		if (!isset(self::$instance[$className]))
		{
			self::$instance[$className] = new $className();
		}
		return self::$instance[$className];
	}
	
	/**
	 * Return the report name used to create the file name. Don't use special chars or space.
	 * Don't set the extension
	 * @return String
	 */
	protected abstract function getReportFileName();
	
	/**
	 * @return String
	 */
	protected abstract function getReportFileLabel();
	
	/**
	 * @return String
	 */
	protected abstract function getReportLabel();
	
	/**
	 * @return array<string => string>
	 */
	protected abstract function getRowsFields();
	
	/**
	 * @return String
	 */
	protected abstract function getNotificationCodeName();
	
	/**
	 * @return String
	 */
	protected abstract function getReportFolderCodeReference();
	
	/**
 	* @return projectcare_persistentdocument_report
 	*/
	public function initializeCSVReport()
	{
		// Generate the media.
		$fileName = $this->getReportFileName() . '.csv';
		$file = media_FileService::getInstance()->getNewDocumentInstance();
		$file->setLabel($this->getReportFileLabel());
		$file->setNewFileName($fileName);
		$file->setFilename($fileName);
		
		// Generate the report.
		$parent = projectcare_ReportfolderService::getInstance()->createQuery()->add(Restrictions::eq("codeReference", $this->getReportFolderCodeReference()))->findUnique();
		
		$report = projectcare_ReportService::getInstance()->getNewDocumentInstance();
		$report->setLabel($this->getReportLabel());
		$report->setCsvFile($file);
		$report->save();
		TreeService::getInstance()->newFirstChild($parent->getId(), $report->getId());
		
		// Initialize the CSV file.
		$options = $this->getCSVExportOptions();
		$options->outputHeaders = true;
		$csvFragment = f_util_CSVUtils::export($this->getRowsFields(), array(), $options);
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
			$notif = $ns->getConfiguredByCodeName($this->getNotificationCodeName(), null, $user->getLang());
			if ($notif instanceof notification_persistentdocument_notification)
			{
				$user->getDocumentService()->sendNotificationToUserCallback($notif, $user);
			}
		}
	}
	
	/**
	 * @param projectcare_persistentdocument_report rowsrt
	 * @param array rows
	 */
	public function appendRowsToCsv($report, $rows)
	{
		$csvFragment = f_util_CSVUtils::export($this->getRowsFields(), $rows, $this->getCSVExportOptions());
		$report->appendFragmentToCsv($csvFragment);
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
	
}

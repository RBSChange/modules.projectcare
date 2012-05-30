<?php
class projectcare_CheckLinksTask extends task_SimpleSystemTask
{
	/**
	 * @see task_SimpleSystemTask::execute()
	 */
	protected function execute()
	{
		$prs = projectcare_ReportGenerator::getInstance('projectcare_LinksReportGenerator');
		$chunkSize = Framework::getConfigurationValue('modules/projectcare/checkLinksChunkSize', '100');
		$report = $prs->initializeCSVReport();
		
		// Check links in page contents.
		$offset = 0;
		$end = false;
		$batchPath = f_util_FileUtils::buildRelativePath('modules', 'projectcare', 'lib', 'bin', 'batchCheckLinksInPageContents.php');
		while (!$end)
		{
			$this->plannedTask->ping();
			$result = f_util_System::execScript($batchPath, array($offset, $chunkSize, $report->getId()));
			if ($result === 'END')
			{
				$end = true;
			}
			elseif ($result === 'CONTINUE')
			{
				$offset += $chunkSize;
			}
			// Log fatal errors...
			else
			{
				Framework::error(__METHOD__ . ' ' . $batchPath . ' unexpected result on page contents at offset ' . $offset . ': "' . $result . '"');
				$end = true;
			}
		}
		
		// Check links in richtext properties.
		$batchPath = f_util_FileUtils::buildRelativePath('modules', 'projectcare', 'lib', 'bin', 'batchCheckLinksInXHTMLFragments.php');
		foreach (f_persistentdocument_PersistentDocumentModel::getDocumentModelNamesByModules() as $modelNames)
		{
			foreach ($modelNames as $modelName)
			{
				$offset = 0;
				$end = false;
				while (!$end)
				{
					$this->plannedTask->ping();
					$result = f_util_System::execScript($batchPath, array($modelName, $offset, $chunkSize, $report->getId()));
					if ($result === 'END')
					{
						$end = true;
					}
					elseif ($result === 'CONTINUE')
					{
						$offset += $chunkSize;
					}
					// Log fatal errors...
					else
					{
						Framework::error(__METHOD__ . ' ' . $batchPath . ' unexpected result on model ' . $modelName . ' at offset ' . $offset . ': "' . $result . '"');
						$end = true;
					}
				}
			}
		}
		
		// Check links in url properties.
		$batchPath = f_util_FileUtils::buildRelativePath('modules', 'projectcare', 'lib', 'bin', 'batchCheckLinksInUrlProperties.php');
		foreach (f_persistentdocument_PersistentDocumentModel::getDocumentModelNamesByModules() as $modelNames)
		{
			foreach ($modelNames as $modelName)
			{
				$offset = 0;
				$end = false;
				while (!$end)
				{
					$this->plannedTask->ping();
					$result = f_util_System::execScript($batchPath, array($modelName, $offset, $chunkSize, $report->getId()));
					if ($result === 'END')
					{
						$end = true;
					}
					elseif ($result === 'CONTINUE')
					{
						$offset += $chunkSize;
					}
					// Log fatal errors...
					else
					{
						Framework::error(__METHOD__ . ' ' . $batchPath . ' unexpected result on model ' . $modelName . ' at offset ' . $offset . ': "' . $result . '"');
						$end = true;
					}
				}
			}
		}
		
		// Check links in BBCode properties.
		$batchPath = f_util_FileUtils::buildRelativePath('modules', 'projectcare', 'lib', 'bin', 'batchCheckLinksInBbCodeProperties.php');
		foreach (f_persistentdocument_PersistentDocumentModel::getDocumentModelNamesByModules() as $modelNames)
		{
			foreach ($modelNames as $modelName)
			{
				$offset = 0;
				$end = false;
				while (!$end)
				{
					$this->plannedTask->ping();
					$result = f_util_System::execScript($batchPath, array($modelName, $offset, $chunkSize, $report->getId()));
					if ($result === 'END')
					{
						$end = true;
					}
					elseif ($result === 'CONTINUE')
					{
						$offset += $chunkSize;
					}
					// Log fatal errors...
					else
					{
						Framework::error(__METHOD__ . ' ' . $batchPath . ' unexpected result on model ' . $modelName . ' at offset ' . $offset . ': "' . $result . '"');
						$end = true;
					}
				}
			}
		}
		
		$prs->finalizeReport($report);
		$checkLinksFrequency = Framework::getConfigurationValue('modules/projectcare/checkLinksFrequency', '15');
		$this->plannedTask->reSchedule(date_Calendar::getInstance()->add(date_Calendar::DAY, $checkLinksFrequency));
	}
}
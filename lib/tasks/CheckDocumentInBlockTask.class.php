<?php
class projectcare_CheckDocumentInBlockTask extends task_SimpleSystemTask
{
	/**
	 * @see task_SimpleSystemTask::execute()
	 */
	protected function execute()
	{
		$prg = projectcare_ReportGenerator::getInstance('projectcare_DocumentInBlockReportGenerator');
		$chunkSize = Framework::getConfigurationValue('modules/projectcare/checkDocumentInBlockChunkSize', '100');
		$report = $prg->initializeCSVReport();
		
		// Check document in block in page contents.
		$offset = 0;
		$end = false;
		$batchPath = f_util_FileUtils::buildRelativePath('modules', 'projectcare', 'lib', 'bin', 'batchCheckDocumentInBlockInPageContents.php');
		while (!$end)
		{
			$this->plannedTask->ping();
			$result = f_util_System::execHTTPScript($batchPath, array($offset, $chunkSize, $report->getId()));
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
		
		// Check document in block in template of page.
		$offset = 0;
		$end = false;
		$batchPath = f_util_FileUtils::buildRelativePath('modules', 'projectcare', 'lib', 'bin', 'batchCheckDocumentInBlockInTemplate.php');
		while (!$end)
		{
			$this->plannedTask->ping();
			$result = f_util_System::execHTTPScript($batchPath, array($offset, $chunkSize, $report->getId()));
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
		
		// Check document in block in dashboard user.
		$offset = 0;
		$end = false;
		$batchPath = f_util_FileUtils::buildRelativePath('modules', 'projectcare', 'lib', 'bin', 'batchCheckDocumentInBlockInUserDashboard.php');
		while (!$end)
		{
			$this->plannedTask->ping();
			$result = f_util_System::execHTTPScript($batchPath, array($offset, $chunkSize, $report->getId()));
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
		
		// Check document in block in page template of themes.
		$offset = 0;
		$end = false;
		$batchPath = f_util_FileUtils::buildRelativePath('modules', 'projectcare', 'lib', 'bin', 'batchCheckDocumentInBlockInPageTemplate.php');
		while (!$end)
		{
			$this->plannedTask->ping();
			$result = f_util_System::execHTTPScript($batchPath, array($offset, $chunkSize, $report->getId()));
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
		
		$prg->finalizeReport($report);
		$checkLinksFrequency = Framework::getConfigurationValue('modules/projectcare/checkDocumentInBlockFrequency', '15');
		$this->plannedTask->reSchedule(date_Calendar::getInstance()->add(date_Calendar::DAY, $checkLinksFrequency));
	}
}
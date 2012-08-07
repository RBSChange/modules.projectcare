<?php
class projectcare_CheckOrphanPageReferenceTask extends task_SimpleSystemTask
{
	/**
	 * @see task_SimpleSystemTask::execute()
	 */
	protected function execute()
	{
		$prs = projectcare_ReportGenerator::getInstance('projectcare_OrphanPageReferenceReportGenerator');
		$chunkSize = Framework::getConfigurationValue('modules/projectcare/checkOrphanPageReferenceChunkSize', '100');
		$report = $prs->initializeCSVReport();
		
		$offset = 0;
		$end = false;
		$batchPath = f_util_FileUtils::buildRelativePath('modules', 'projectcare', 'lib', 'bin', 'batchCheckOrphanPageReference.php');
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
		
		$prs->finalizeReport($report);
		$checkLinksFrequency = Framework::getConfigurationValue('modules/projectcare/checkOrphanPageReferenceFrequency', '15');
		$this->plannedTask->reSchedule(date_Calendar::getInstance()->add(date_Calendar::DAY, $checkLinksFrequency));
	}
}
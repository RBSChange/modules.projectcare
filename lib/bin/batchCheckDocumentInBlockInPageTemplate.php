<?php
if (!defined("WEBEDIT_HOME"))
{
	define("WEBEDIT_HOME", realpath('.'));
	require_once WEBEDIT_HOME . "/framework/Framework.php";
	list($offset, $chunkSize, $reportId) = array_slice($_SERVER['argv'], 1);
}
else
{
	list($offset, $chunkSize, $reportId) = $_POST['argv'];
}

Controller::newInstance("controller_ChangeController");

$report = projectcare_persistentdocument_report::getInstanceById($reportId);
$end = projectcare_ModuleService::getInstance()->checkDocumentInBlockInPageTemplate($offset, $chunkSize, $report);

echo $end ? 'END' : 'CONTINUE';
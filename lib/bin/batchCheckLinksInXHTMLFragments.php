<?php
if (!defined("WEBEDIT_HOME"))
{
	define("WEBEDIT_HOME", realpath('.'));
	require_once WEBEDIT_HOME . "/framework/Framework.php";
	list($modelName, $offset, $chunkSize, $reportId) = array_slice($_SERVER['argv'], 1);
}
else
{
	list($modelName, $offset, $chunkSize, $reportId) = $_POST['argv'];
}

Controller::newInstance("controller_ChangeController");

$model = f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($modelName);
if ($model->getName() !== $modelName)
{
	if (Framework::isInfoEnabled())
	{
		Framework::info('[batchCheckLinksInXHTMLFragments] ignore model "' . $modelName . '" injecting "' . $model->getName() . '"');
	}
	$end = true;
}
else
{
	$propertyNames = projectcare_ModuleService::getInstance()->getRichtextPropertyNamesByModel($model);
	if (count($propertyNames) < 1)
	{
		if (Framework::isInfoEnabled())
		{
			Framework::info('[batchCheckLinksInXHTMLFragments] no richtext proprerty in model ' . $modelName);
		}
		$end = true;
	}
	else 
	{
		if (Framework::isInfoEnabled())
		{
			Framework::info('[batchCheckLinksInXHTMLFragments] check for model ' . $model->getName() . ' at offset ' . $offset . ' for properties ' . implode(',', $propertyNames));
		}
		$report = projectcare_persistentdocument_report::getInstanceById($reportId);
		$end = projectcare_ModuleService::getInstance()->checkLinksInXHTMLFragments($model, $propertyNames, $offset, $chunkSize, $report);
	}
}

echo $end ? 'END' : 'CONTINUE';
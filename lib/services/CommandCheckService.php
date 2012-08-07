<?php
/**
 * @package modules.projectcare.lib.services
 */
class projectcare_CommandCheckService extends ModuleBaseService
{
	
	private $endSeperator = '-------------------------------------------------------------------------';
	
	private $resultStrings = array('-------------------------------------------------------------------------');
	
	/**
	 * Singleton
	 * @var projectcare_CommandCheckService
	 */
	private static $instance = null;
	
	/**
	 * @return projectcare_CommandCheckService
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}
	
	public function execute()
	{
		$this->checkRequiredPanel();
		
		$this->checkPropertiesTabInResume();
		
		$this->checkRedirectTabInResume();
		
		$this->checkLocalizationTabInResume();
		
		$this->checkResumeOrderSection();
		
		$this->checkResumeLinkedTab();
		
		$this->checkRequiredRootFolderEditor();
		
		$this->findSpecificPanelsWithoutBindings();
		
		$this->findJS();
		
		$this->checkActivationIsAvailable();
		
		$this->checkSource();
		
		$this->checkDependencies();
		
		$this->checkToolbarActionOrder();
		
		echo implode(PHP_EOL, $this->resultStrings), PHP_EOL;
	}
	
	/**
	 * Check that module has at least Resume, Publication and History panels
	 */
	public function checkRequiredPanel()
	{
		$resultStrings = array('==> Check that the required panels are defined');
		
		$files = array();
		$files = glob(WEBEDIT_HOME . '/modules/*/forms/editor/*/panels.xml');
		
		$requiredPanels = array('resume', 'publication', 'history');
		$error = false;
		
		foreach ($files as $file)
		{
			
			$doc = f_util_DOMUtils::fromPath($file);
			$fileError = false;
			$fileResult = array();
			
			if (!$doc->documentElement->hasAttribute('hidden') && !$doc->documentElement->hasAttribute('use'))
			{
				foreach ($requiredPanels as $requiredPanel)
				{
					if ($doc->findUnique('//panel[@name="' . $requiredPanel . '"]') === null)
					{
						$fileError = true;
						$error = true;
						$fileResult[] = '-- Missing panel : ' . $requiredPanel;
					}
				}
			}
			
			if ($fileError)
			{
				$resultStrings[] = $file;
				$resultStrings = array_merge($resultStrings, $fileResult);
			}
		
		}
		
		$resultStrings[] = $this->endSeperator;
		
		if ($error)
		{
			$this->resultStrings = array_merge($this->resultStrings, $resultStrings);
		}
	}
	
	/**
	 * Check if all modules have rootfolder editor
	 */
	public function checkRequiredRootFolderEditor()
	{
		$resultStrings = array('==> Check that the rootfolder editor exist');
		$error = false;
		
		$packages = ModuleService::getInstance()->getPackageNames();
		foreach ($packages as $package)
		{
			$package = ModuleService::getInstance()->getShortModuleName($package);
			
			if ($package != 'updater' && $package != 'useractionlogger' && $package != 'dashboard')
			{
				if (ModuleService::getInstance()->getModule($package)->isVisible())
				{
					$path = WEBEDIT_HOME . '/modules/' . $package . '/forms/editor/rootfolder/';
					if (!file_exists($path))
					{
						$error = true;
						$resultStrings[] = 'Add file : ' . $path . 'empty.txt';
					}
				}
			
			}
		
		}
		
		$resultStrings[] = $this->endSeperator;
		
		if ($error)
		{
			$this->resultStrings = array_merge($this->resultStrings, $resultStrings);
		}
	
	}
	
	public function checkResumeLinkedTab()
	{
		$resultStrings = array('==> Check that all panels exists for the resume section with link');
		$error = false;
		
		$files = array();
		$files = glob(WEBEDIT_HOME . '/modules/*/forms/editor/*/resume.xml');
		
		foreach ($files as $file)
		{
			
			$doc = f_util_DOMUtils::fromPath($file);
			$sections = array();
			foreach ($doc->documentElement->childNodes as $node)
			{
				if ($node->localName == 'section')
				{
					if ($node->hasAttribute('linkedtab'))
					{
						$name = $node->getAttribute('linkedtab');
						$sections[] = $name;
					}
				}
			}
			
			$filePanel = str_replace('resume', 'panels', $file);
			
			try
			{
				$docPanel = f_util_DOMUtils::fromPath($filePanel);
				
				$panelHidden = $docPanel->documentElement->hasAttribute('hidden');
				
				if (!$panelHidden)
				{
					$panels = array();
					foreach ($sections as $section)
					{
						if ($docPanel->findUnique('//panel[@name="' . $section . '"]') !== null)
						{
							$panels[] = $section;
						}
						else
						{
							$jsContentNode = $docPanel->findUnique('//xul/javascript/constructor');
							if ($jsContentNode != null)
							{
								$jsContent = $jsContentNode->nodeValue;
								$panelSpe = array();
								if (preg_match('/.*addTab\([\'"]' . $section . '[\'"],[^,]+,[^,]+\);.*/', $jsContent) >= 1)
								{
									$panels[] = $section;
								}
							}
						}
					}
					
					if ($sections != $panels)
					{
						$error = true;
						$resultStrings[] = $file;
						$resultStrings[] = "Sections : ";
						foreach ($sections as $section)
						{
							$resultStrings[] = " -- " . $section;
						}
						$resultStrings[] = $filePanel;
						$resultStrings[] = "Panels : ";
						foreach ($panels as $panel)
						{
							$resultStrings[] = " -- " . $panel;
						}
					}
				
				}
			}
			catch (Exception $e)
			{
				// If no panel found, not need to ckeck, it will be ok
			}
		
		}
		
		$resultStrings[] = $this->endSeperator;
		
		if ($error)
		{
			$this->resultStrings = array_merge($this->resultStrings, $resultStrings);
		}
	
	}
	
	public function checkLocalizationTabInResume()
	{
		$resultStrings = array('==> Check that the localization section is in resume panel if document has localized properties');
		$error = false;
		
		$files = array();
		$files = glob(WEBEDIT_HOME . '/modules/*/forms/editor/*/resume.xml');
		
		foreach ($files as $file)
		{
			$tmp = explode('/', $file);
			$tmpCount = count($tmp);
			
			$moduleName = $tmp[$tmpCount - 5];
			$modelName = $tmp[$tmpCount - 2];
			
			if ($modelName == 'rewriterule')
			{
				$moduleName = 'website';
			}
			
			try
			{
				$model = f_persistentdocument_PersistentDocumentModel::getInstance($moduleName, $modelName);
				
				$doc = f_util_DOMUtils::fromPath($file);
				if (!$doc->documentElement->hasAttribute('use'))
				{
					$localizationNode = $doc->findUnique('//section[@name="localization"]');
					
					if ($localizationNode != null && !$model->isLocalized())
					{
						$error = true;
						$resultStrings[] = $model->getName() . ' not localized but section is present. Section must be deleted.';
					}
					else if ($localizationNode == null && $model->isLocalized())
					{
						$error = true;
						$resultStrings[] = $model->getName() . ' localized but section is missing. Section must be added.';
					}
				}
			
			}
			catch (Exception $e)
			{
				$error = true;
				$resultStrings[] = 'Model modules_' . $moduleName . '/' . $modelName . ' not exist';
			}
		
		}
		
		$resultStrings[] = $this->endSeperator;
		
		if ($error)
		{
			$this->resultStrings = array_merge($this->resultStrings, $resultStrings);
		}
	}
	
	public function checkPropertiesTabInResume()
	{
		
		$resultStrings = array('==> Check that the properties section is in resume panel');
		$error = false;
		
		$files = array();
		$files = glob(WEBEDIT_HOME . '/modules/*/forms/editor/*/resume.xml');
		
		foreach ($files as $file)
		{
			$tmp = explode('/', $file);
			$tmpCount = count($tmp);
			
			$doc = f_util_DOMUtils::fromPath($file);
			if (!$doc->documentElement->hasAttribute('use'))
			{
				$propertiesNode = $doc->findUnique('//section[@name="properties"]');
				
				if ($propertiesNode == null)
				{
					$error = true;
					$resultStrings[] = $file . ' has not properties section.';
				}
			}
		
		}
		
		$resultStrings[] = $this->endSeperator;
		
		if ($error)
		{
			$this->resultStrings = array_merge($this->resultStrings, $resultStrings);
		}
	}
	
	public function checkRedirectTabInResume()
	{
		$resultStrings = array('==> Check that the redirect section is in resume panel if document has an url and not in other case');
		$error = false;
		
		$files = array();
		$files = glob(WEBEDIT_HOME . '/modules/*/forms/editor/*/resume.xml');
		
		foreach ($files as $file)
		{
			$tmp = explode('/', $file);
			$tmpCount = count($tmp);
			
			$moduleName = $tmp[$tmpCount - 5];
			$modelName = $tmp[$tmpCount - 2];
			
			if ($modelName == 'rewriterule')
			{
				$moduleName = 'website';
			}
			
			try
			{
				$model = f_persistentdocument_PersistentDocumentModel::getInstance($moduleName, $modelName);
				
				$doc = f_util_DOMUtils::fromPath($file);
				if (!$doc->documentElement->hasAttribute('use'))
				{
					$redirectNode = $doc->findUnique('//section[@name="urlrewriting"]');
					
					if ($redirectNode != null && !$model->hasURL())
					{
						$error = true;
						$resultStrings[] = $model->getName() . ' has not url but section is present. Section must be deleted.';
					}
					else if ($redirectNode == null && $model->hasURL())
					{
						$error = true;
						$resultStrings[] = $model->getName() . ' has url but section is missing. Section must be added.';
					}
				}
			
			}
			catch (Exception $e)
			{
				$error = true;
				$resultStrings[] = 'Model modules_' . $moduleName . '/' . $modelName . ' not exist';
			}
		
		}
		
		$resultStrings[] = $this->endSeperator;
		
		if ($error)
		{
			$this->resultStrings = array_merge($this->resultStrings, $resultStrings);
		}
	
	}
	
	public function findSpecificPanelsWithoutBindings()
	{
		$resultStrings = array('==> Check the configuration of specifics panels. Attributes and bindings');
		$error = false;
		
		$files = array();
		$files = glob(WEBEDIT_HOME . '/modules/*/forms/editor/*/panels.xml');
		
		$default = array('resume', 'properties', 'localization', 'publication', 'redirect', 'permission', 'history', 'create');
		
		foreach ($files as $file)
		{
			
			$doc = f_util_DOMUtils::fromPath($file);
			
			$bindingBasePath = str_replace('forms', 'lib/bindings', $file);
			$bindingBasePath = str_replace('panels.xml', '', $bindingBasePath);
			
			$errorFile = false;
			$message = $file . PHP_EOL;
			foreach ($doc->documentElement->childNodes as $node)
			{
				if ($node->localName == 'panel')
				{
					$name = $node->getAttribute('name');
					
					if (array_search($name, $default) === false)
					{
						
						if (!$node->hasAttribute('icon'))
						{
							$errorFile = true;
							$message .= $name . ' has no defined icon' . PHP_EOL;
						}
						
						if (!$node->hasAttribute('labeli18n'))
						{
							$errorFile = true;
							$message .= $name . ' has no defined label' . PHP_EOL;
						}
						
						$bindingFile = $bindingBasePath . $name . '.xml';
						
						if (!file_exists($bindingFile))
						{
							$errorFile = true;
							$message .= $name . ' has no binding file : ' . $bindingFile . PHP_EOL;
						}
					
					}
				
				}
			}
			
			if ($errorFile)
			{
				$error = true;
				$resultStrings[] = $message;
			}
		
		}
		
		$resultStrings[] = $this->endSeperator;
		
		if ($error)
		{
			$this->resultStrings = array_merge($this->resultStrings, $resultStrings);
		}
	}
	
	public function findJS()
	{
		$resultStrings = array('==> Check JS code in panels');
		$error = false;
		
		$files = array();
		$files = glob(WEBEDIT_HOME . '/modules/*/forms/editor/*/panels.xml');
		
		foreach ($files as $file)
		{
			$errorFile = false;
			$fileResultStrings = array();
			
			$doc = f_util_DOMUtils::fromPath($file);
			
			$el = $doc->findUnique('//xul/javascript');
			
			if ($el !== null)
			{
				$fileResultStrings[] = 'Use JS : ' . $file;
				
				$jsContentNode = $doc->findUnique('//xul/javascript/constructor');
				$jsContent = $jsContentNode->nodeValue;
				
				if (preg_match('/.*addTab\([^,]+,[^,]+,[^,]+\);.*/', $jsContent) >= 1)
				{
					$errorFile = true;
					$fileResultStrings[] = 'Add tab without specific place. You may set the fourth argument';
				}
				
				if (preg_match('/.*checkModuleVersion.*/', $jsContent) >= 1)
				{
					$errorFile = true;
					$fileResultStrings[] = 'checkModuleVersion must be replaced by hasModule';
				}
			
			}
			
			if ($errorFile)
			{
				$error = true;
				$resultStrings = array_merge($resultStrings, $fileResultStrings);
			}
		
		}
		
		$resultStrings[] = $this->endSeperator;
		
		if ($error)
		{
			$this->resultStrings = array_merge($this->resultStrings, $resultStrings);
		}
	}
	
	public function checkResumeOrderSection()
	{
		$resultStrings = array('==> Check that the order of resume section is ok');
		$error = false;
		
		function cmp($a, $b)
		{
			$value = 0;
			switch ($a)
			{
				case 'properties' :
					$value = -1;
					break;
				case 'localization' :
					if ($b == 'properties')
					{
						$value = 1;
					}
					else
					{
						$value = -1;
					}
					break;
				case 'publication' :
					if ($b == 'properties' || $b == 'localization')
					{
						$value = 1;
					}
					else
					{
						$value = -1;
					}
					break;
				case 'urlrewriting' :
					if ($b == 'permission' || $b == 'history')
					{
						$value = -1;
					}
					else
					{
						$value = 1;
					}
					break;
				case 'permission' :
					if ($b == 'history')
					{
						$value = -1;
					}
					else
					{
						$value = 1;
					}
					break;
				case 'history' :
					$value = 1;
					break;
				default :
					$value = 0;
			}
			return $value;
		}
		
		$files = array();
		$files = glob(WEBEDIT_HOME . '/modules/*/forms/editor/*/resume.xml');
		
		$default = array('properties', 'localization', 'publication', 'urlrewriting', 'permission', 'history');
		
		foreach ($files as $file)
		{
			
			$doc = f_util_DOMUtils::fromPath($file);
			
			$originNodeNames = array();
			$currentNodeName = array();
			
			foreach ($doc->documentElement->childNodes as $node)
			{
				if ($node->localName == 'section')
				{
					$name = $node->getAttribute('name');
					if (in_array($name, $default))
					{
						$originNodeNames[] = $name;
						$currentNodeName[] = $name;
					}
				}
			}
			
			usort($currentNodeName, 'cmp');
			
			if ($originNodeNames != $currentNodeName)
			{
				$error = true;
				$resultStrings[] = 'Sections in ' . $file . ' must be sort';
			}
		
		}
		
		if ($error)
		{
			$resultStrings[] = "Order is : 'properties', 'localization', 'publication', 'urlrewriting', 'permission', 'history'";
		}
		
		$resultStrings[] = $this->endSeperator;
		
		if ($error)
		{
			$this->resultStrings = array_merge($this->resultStrings, $resultStrings);
		}
	}
	
	public function checkActivationIsAvailable()
	{
		$resultStrings = array('==> Check that document with default status DRAFT has action activate');
		$error = false;
		
		$excludeModel = array('modules_website/pagegroup', 'modules_order/orderpreparation', 'modules_order/bill', 'modules_mapping/area');
		
		$models = f_persistentdocument_PersistentDocumentModel::getDocumentModels();
		foreach ($models as $model)
		{
			/* @var $model f_persistentdocument_PersistentDocumentModel */
			if ($model->getDefaultNewInstanceStatus() == 'DRAFT' && !in_array($model->getName(), $excludeModel))
			{
				$actions = array();
				$paths = glob(WEBEDIT_HOME . '/modules/' . $model->getModuleName() . '/config/*perspective.xml');
				
				foreach ($paths as $path)
				{
					$doc = f_util_DOMUtils::fromPath($path);
					
					$nodeList = $doc->find('//model[@name="' . $model->getName() . '"]//contextaction');
					
					for ($i = 0; $i < $nodeList->length; $i++)
					{
						$node = $nodeList->item($i);
						$attributes = $node->attributes;
						$actions[] = $attributes->item(0)->nodeValue;
					}
				}
				
				$name = split('\/', $model->getName());
				$editorPath = WEBEDIT_HOME . '/modules/' . $model->getModuleName() . '/forms/editor/' . $name[1] . '/';
				
				if (is_dir($editorPath))
				{
					if (!in_array('activate', $actions))
					{
						$error = true;
						$resultStrings[] = 'Action activate missing on ' . $model->getName();
					}
					
					if (!in_array('deactivated', $actions) || !in_array('reactivate', $actions))
					{
						$error = true;
						$resultStrings[] = 'Action deactivated or reactivate missing on ' . $model->getName();
					}
				}
			
			}
		}
		
		$resultStrings[] = $this->endSeperator;
		
		if ($error)
		{
			$this->resultStrings = array_merge($this->resultStrings, $resultStrings);
		}
	}
	
	public function checkToolbarActionOrder()
	{
		$resultStrings = array('==> Check that action in toolbar still in same order');
		$error = false;
		
		$paths = glob(WEBEDIT_HOME . '/modules/*/config/perspective.xml');
		
		$genericAction = array('edit', 'activate', 'deactivated', 'reactivate', 'delete');
		
		foreach ($paths as $path)
		{
			$resultFile = array();
			$errorFile = false;
			
			$toolbarActionsNames = array();
			$contextActionsNames = array();
			
			$doc = f_util_DOMUtils::fromPath($path);
			
			$toolbarButtonNodeList = $doc->find('//toolbarbutton');
			$contextActionNodeList = $doc->find('//contextaction');
			
			for ($i = 0; $i < $toolbarButtonNodeList->length; $i++)
			{
				$node = $toolbarButtonNodeList->item($i);
				$attributes = $node->attributes;
				$toolbarActionsNames[] = $attributes->item(0)->nodeValue;
			}
			
			for ($i = 0; $i < $contextActionNodeList->length; $i++)
			{
				$node = $contextActionNodeList->item($i);
				$attributes = $node->attributes;
				$contextActionsNames[] = $attributes->item(0)->nodeValue;
			}
			
			// Test toolbarbutton are in contextaction
			foreach ($toolbarActionsNames as $toolbarActionName)
			{
				if (!in_array($toolbarActionName, $contextActionsNames))
				{
					$errorFile = true;
					$resultFile[] = '  -- ' . $toolbarActionName . ' not exists in contextAction';
				}
			}
			
			// Test if default action are displayed in toolbar
			foreach ($genericAction as $action)
			{
				if (in_array($action, $contextActionsNames) && !in_array($action, $toolbarActionsNames))
				{
					$errorFile = true;
					$resultFile[] = '  -- ' . $action . ' must be in toolbarbutton';
				}
			}
			
			// Test order of toolbar
			$arrayOrderOne = array_values(array_intersect($toolbarActionsNames, $genericAction));
			$arrayOrderTwo = array_values(array_intersect($genericAction, $toolbarActionsNames));
			
			if (count(array_diff_assoc($arrayOrderOne, $arrayOrderTwo)) > 0)
			{
				$errorFile = true;
				$resultFile[] = '  -- Bad toolbar button order';
			}
			
			// Test order for each contextactions
			$modelNodeList = $doc->find('//model');
			$models = array();
			for ($i = 0; $i < $modelNodeList->length; $i++)
			{
				$node = $modelNodeList->item($i);
				$attributes = $node->attributes;
				$models[] = $attributes->item(0)->nodeValue;
			}
			
			foreach ($models as $model)
			{
				$nodeList = $doc->find('//model[@name="' . $model . '"]//contextaction');
				$contextactionForModelOrder = array();
				for ($i = 0; $i < $nodeList->length; $i++)
				{
					$node = $nodeList->item($i);
					$attributes = $node->attributes;
					$contextactionForModelOrder[] = $attributes->item(0)->nodeValue;
				}
				
				$arrayContextActionOrderOne = array_values(array_intersect($contextactionForModelOrder, $genericAction));
				$arrayContextActionOrderTwo = array_values(array_intersect($genericAction, $contextactionForModelOrder));
				
				if (count(array_diff_assoc($arrayContextActionOrderOne, $arrayContextActionOrderTwo)) > 0)
				{
					$errorFile = true;
					$resultFile[] = '  -- Bad contextaction order for model ' . $model;
				}
			
			}
			
			if ($errorFile)
			{
				$error = true;
				array_unshift($resultFile, '-- ' . $path);
				$resultStrings = array_merge($resultStrings, $resultFile);
			}
		
		}
		
		if ($error)
		{
			$resultStrings[] = PHP_EOL;
			$resultStrings[] = 'Default action order is : edit, activate, deactivated, reactivate, delete';
			$resultStrings[] = $this->endSeperator;
			$this->resultStrings = array_merge($this->resultStrings, $resultStrings);
		}
	}
	
	public function checkDependencies()
	{
		$resultStrings = array('==> Check modules dependencies');
		$error = false;
		
		// Load Framework change.xml
		$path = WEBEDIT_HOME . '/framework/change.xml';
		$doc = f_util_DOMUtils::fromPath($path);
		$doc->registerNamespace('c', 'http://www.rbs.fr/schema/change-component/1.0');
		$nodeList = $doc->find('//c:name');
		$frameworkDependencies = array();
		
		// Extract change-module
		// Extract pear
		// Extract lib
		$changeModule = array();
		$pear = array();
		$lib = array();
		for ($i = 0; $i < $nodeList->length; $i++)
		{
			$node = $nodeList->item($i);
			$value = $node->nodeValue;
			$frameworkDependencies[] = $value;
		}
		
		// Get package
		$packages = ModuleService::getInstance()->getPackageNames();
		
		foreach ($packages as $package)
		{
			$path = WEBEDIT_HOME . '/' . str_replace('_', '/', $package) . '/change.xml';
			$doc = f_util_DOMUtils::fromPath($path);
			$doc->registerNamespace('c', 'http://www.rbs.fr/schema/change-component/1.0');
			$nodeList = $doc->find('//c:name');
			$packageDependencies = array();
			for ($i = 0; $i < $nodeList->length; $i++)
			{
				$node = $nodeList->item($i);
				$value = $node->nodeValue;
				$packageDependencies[] = $value;
			}
			
			$doubleDependencies = array_intersect($packageDependencies, $frameworkDependencies);
			$packageError = array();
			if (count($doubleDependencies) > 0)
			{
				$resultStrings[] = '  -- Package : ' . $package;
				foreach ($doubleDependencies as $doubleDependency)
				{
					$error = true;
					$resultStrings[] = '    -- Already in framework : ' . $doubleDependency;
				}
			}
		}
		
		$resultStrings[] = $this->endSeperator;
		
		if ($error)
		{
			$this->resultStrings = array_merge($this->resultStrings, $resultStrings);
		}
	
	}
	
	public function checkSource()
	{
		$resultStrings = array('==> Check PHP file');
		$error = false;
		
		$packageNames = ModuleService::getInstance()->getPackageNames();
		
		$path = WEBEDIT_HOME;
		
		$packageName[] = 'framework';
		foreach ($packageNames as $packageName)
		{
			$errorPackage = false;
			$packageResultString = array();
			$packagePath = str_replace('_', DIRECTORY_SEPARATOR, $packageName);
			
			$di = new RecursiveDirectoryIterator($path . DIRECTORY_SEPARATOR . $packagePath, RecursiveDirectoryIterator::KEY_AS_PATHNAME);
			
			projectcare_FileFilter::setFilters(true);
			
			$fi = new projectcare_FileFilter($di);
			$it = new RecursiveIteratorIterator($fi, RecursiveIteratorIterator::CHILD_FIRST);
			
			foreach ($it as $file => $info)
			{
				if ($info->isFile())
				{
					$fileResultString = array();
					$errorFile = false;
					$fileContainsCommentOrManyClassResult = $this->checkPhpFileContainsCommentOrManyClass($packageName, $file);
					if (count($fileContainsCommentOrManyClassResult) > 0)
					{
						$errorFile = true;
						$fileResultString = array_merge($fileResultString, $fileContainsCommentOrManyClassResult);
					}
					$fileContainsFatalOrDebugResult = $this->findFatalAndDebugTrace($packageName, $file);
					if (count($fileContainsFatalOrDebugResult) > 0)
					{
						$errorFile = true;
						$fileResultString = array_merge($fileResultString, $fileContainsFatalOrDebugResult);
					}
					
					if ($errorFile)
					{
						$errorPackage = true;
						array_unshift($fileResultString, '  -- ' . $file);
						$packageResultString = array_merge($packageResultString, $fileResultString);
					}
				}
			}
			
			if ($errorPackage)
			{
				$error = true;
				array_unshift($packageResultString, '-- ' . $packageName);
				$resultStrings = array_merge($resultStrings, $packageResultString);
			}
		
		}
		
		$resultStrings[] = $this->endSeperator;
		
		if ($error)
		{
			$this->resultStrings = array_merge($this->resultStrings, $resultStrings);
		}
	}
	
	private function findFatalAndDebugTrace($package, $path)
	{
		$error = false;
		$resultString = array();
		
		$content = f_util_FileUtils::read($path);
		$exp = '/Framework\s*::\s*fatal/m';
		if (preg_match($exp, $content))
		{
			$resultString[] = '    -- File contains Framework::fatal';
			$error = true;
		}
		$exp = '/Framework\s*::\s*debug/m';
		if (preg_match($exp, $content))
		{
			$resultString[] = '    -- File contains Framework::debug';
			$error = true;
		}
		
		if ($error)
		{
			return $resultString;
		}
		
		return array();
	}
	
	private function checkPhpFileContainsCommentOrManyClass($package, $path)
	{
		$error = false;
		$resultString = array();
		
		$content = f_util_FileUtils::read($path);
		$tokens = token_get_all($content);
		
		$exp = '/^\/\/\s*(public|protected|private|static|abstract)\s.*function.*\(/';
		$classCount = 0;
		$commentedMethod = false;
		
		foreach ($tokens as $token)
		{
			if (is_array($token))
			{
				if ($token[0] == T_CLASS)
				{
					++$classCount;
				}
				
				if ($token[0] == T_COMMENT)
				{
					if (preg_match($exp, $token[1]))
					{
						$commentedMethod = true;
					}
				}
			}
		}
		
		if ($classCount > 1)
		{
			$resultString[] = '    -- File contains more than 1 class';
			$error = true;
		}
		
		if ($commentedMethod)
		{
			$resultString[] = '    -- File contains commented method';
			$error = true;
		}
		
		if ($error)
		{
			// 			array_unshift($resultString, '  -- ' . $path);
			return $resultString;
		}
		
		return array();
	
	}

}
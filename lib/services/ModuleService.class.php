<?php
/**
 * @package modules.projectcare.lib.services
 */
class projectcare_ModuleService extends ModuleBaseService
{
	/**
	 * Singleton
	 * @var projectcare_ModuleService
	 */
	private static $instance = null;
	
	/**
	 * @return projectcare_ModuleService
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}
		
	/**
	 * @param integer $offset
	 * @param integer $chunkSize
	 * @param projectcare_persistentdocument_report $report
	 * @return boolean true if all links are checked, false if there are other links to check.
	 */
	public function checkLinksInPageContents($offset, $chunkSize, $report)
	{
		// Get all pages.
		$pages = website_PageService::getInstance()->createQuery()->add(Restrictions::ne('publicationstatus', 'DEPRECATED'))
			->setFirstResult($offset)->setMaxResults($chunkSize)->find();
		foreach ($pages as $page)
		{
			foreach ($page->getI18nInfo()->getLangs() as $lang)
			{
				$rc = RequestContext::getInstance();
				try 
				{
					$rc->beginI18nWork($lang);
					
					$doc = new DOMDocument('1.0', 'utf-8');
					if (!$page->isPublished() || $doc->loadXML($page->getContent()) === false)
					{
						continue;
					}
					
					$resultXPath = new DOMXPath($doc);
					$resultXPath->registerNamespace('change', 'http://www.rbs.fr/change/1.0/schema');
					$contentList = $resultXPath->query('//change:richtextcontent');
					foreach ($contentList as $content)
					{
						$links = $this->checkLinksInFragment($content->textContent, $page, $lang, 'content');
						if (count($links) > 0)
						{
							$report->getDocumentService()->appendLinksToCSV($report, $links);
						}
					}
					
					$rc->endI18nWork();
				}
				catch (Exception $e)
				{
					$rc->endI18nWork($e);
				}
			}
		}
		return count($pages) < $chunkSize;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocumentModel $model
	 * @param string[] $propertyNames
	 * @param integer $offset
	 * @param integer $chunkSize
	 * @param projectcare_persistentdocument_report $report
	 * @return boolean true if all links are checked, false if there are other links to check.
	 */
	public function checkLinksInXHTMLFragments($model, $propertyNames, $offset, $chunkSize, $report)
	{
		$documents = $this->getPersistentProvider()->createQuery($model->getName(), false)
			->add(Restrictions::ne('publicationstatus', 'DEPRECATED'))
			->setFirstResult($offset)->setMaxResults($chunkSize)->find();
		foreach ($documents as $document)
		{
			foreach ($document->getI18nInfo()->getLangs() as $lang)
			{
				$rc = RequestContext::getInstance();
				try
				{
					$rc->beginI18nWork($lang);
								
					foreach ($propertyNames as $propertyName)
					{
						if (!$document->isPublished())
						{
							continue;
						}
						$getter = 'get' . ucfirst($propertyName);
						$links = $this->checkLinksInFragment($document->$getter(), $document, $lang, $propertyName);
						if (count($links) > 0)
						{
							$report->getDocumentService()->appendLinksToCSV($report, $links);
						}
					}
											
					$rc->endI18nWork();
				}
				catch (Exception $e)
				{
					$rc->endI18nWork($e);
				}
			}
		}
		return count($documents) < $chunkSize;
	}
	
	/**
	 * @param string $fragment
	 * @param f_persistentdocument_PersistentDocument $container
	 * @param string $lang
	 */
	protected function checkLinksInFragment($fragment, $container, $lang, $propertyName)
	{
		$links = array();
		$externalBrokenLinks = array();
		$externalForbiddenLinks = array();
		$otherLinks = array();
		
		$newXml = '<?xml version="1.0" encoding="UTF-8" ?><root>' . $fragment . '</root>';
		$docContent = new DOMDocument('1.0', 'utf-8');
		if ($docContent->loadXML($newXml) === false)
		{
			Framework::error(__METHOD__ . ' Invalid XML in XHTMLFragment on document ' . $container->__toString() . ': ' . PHP_EOL . $newXml);
			return array();
		}
		
		$contentXPath = new DOMXPath($docContent);
		$aList = $contentXPath->query('//a');
		foreach ($aList as $a)
		{
			// Ignore anchors.
			if ($a->hasAttribute('name'))
			{
				continue;
			}
			
			// If link href != # call link and wait the response.
			if ($a->getAttribute('href') != '#' && f_util_StringUtils::beginsWith($a->getAttribute('href'), 'http'))
			{
				$httpClient = HTTPClientService::getInstance()->getNewHTTPClient();
				$httpClient->setOption(CURLOPT_NOBODY, true);
				$httpClient->setTimeOut(10);
				$httpClient->get($a->getAttribute('href'));				
				$returnCode = $httpClient->getHTTPReturnCode();
				$curlError = $httpClient->getCurlError();
				if ($returnCode != 200)
				{
					$links[] = array(
						'httpStatus' => $returnCode ? $returnCode : 'error',
						'curlError' => $curlError ? $curlError : 'no error',
						'containerId' => $container->getId(),
						'containerLabel' => $container->getLabel(),
						'containerModel' => $container->getDocumentModelName(),
						'containerLang' => $lang,
						'linkType' => 'external',
						'propertyName' => $propertyName,
						'targetId' => null,
						'url' => $a->getAttribute('href')
					);
				}
			}
			// If link href == # and rel cmpref exists, try to get the link.
			else if ($a->getAttribute('href') == '#' && $a->hasAttribute('rel'))
			{
				$documentId = null;
				foreach (explode(',', $a->getAttribute('rel')) as $rel)
				{
					if (strpos($rel, 'cmpref:') === 0)
					{
						$documentId = intval(substr($rel, 7));
						break;
					}
				}
				
				try
				{
					$document = DocumentHelper::getDocumentInstance($documentId);
					LinkHelper::getDocumentUrl($document);
				}
				catch (Exception $e)
				{
					$links[] = array(
						'httpStatus' => '404',
						'curlError' => 'no error',
						'containerId' => $container->getId(),
						'containerLabel' => $container->getLabel(),
						'containerModel' => $container->getDocumentModelName(),
						'containerLang' => $lang,
						'linkType' => 'internal',
						'propertyName' => $propertyName,
						'targetId' => $documentId,
						'url' => null
					);
				}
			}
			// Else return link.
			else
			{
				$links[] = array(
					'httpStatus' => 'not tested',
					'curlError' => 'not tested',
					'containerId' => $container->getId(),
					'containerLabel' => $container->getLabel(),
					'containerModel' => $container->getDocumentModelName(),
					'containerLang' => $lang,
					'linkType' => 'special',
					'propertyName' => $propertyName,
					'targetId' => null,
					'url' => $a->getAttribute('href')
				);
			}
		}		
		return $links;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocumentModel $model
	 * @return string[]
	 */
	public function getRichtextPropertyNamesByModel($model)
	{
		$propertyNames = array();
		
		foreach ($model->getPropertiesInfos() as $property)
		{
			/* @var $property PropertyInfo */
			if ($property->getType() == f_persistentdocument_PersistentDocument::PROPERTYTYPE_XHTMLFRAGMENT)
			{
				$propertyNames[] = $property->getName();
			}
		}
		foreach ($model->getSerializedPropertiesInfos() as $property)
		{
			/* @var $property PropertyInfo */
			if ($property->getType() == f_persistentdocument_PersistentDocument::PROPERTYTYPE_XHTMLFRAGMENT)
			{
				$propertyNames[] = $property->getName();
			}
		}
		
		$moduleKey = $model->getModuleName() . '_' . $model->getDocumentName();
		$excludes = Framework::getConfigurationValue('modules/projectcare/xhtmlfragmentsExcludes/' . $moduleKey);
		if (is_array($excludes))
		{
			$propertyNames = array_diff($propertyNames, $excludes);
		}
		
		return $propertyNames;
	}
}
<?php
/**
 * Created by PhpStorm.
 * User: nihau
 * Date: 11/02/14
 * Time: 17:28
 */

namespace BackBuilder\Bundle\GSABundle\Listener;

use BackBuilder\Bundle\GSABundle\Model\LinkBuilder;
use BackBuilder\Bundle\GSABundle\Model\Response\ParserFactory,
    BackBuilder\Event\Event,
    BackBuilder\Bundle\GSABundle\Model\Pager,
    BackBuilder\Bundle\GSABundle\Model\Filter,
    BackBuilder\Renderer\Renderer;

class SearchResultsListener
{

    /**
     * @var $bbapp BackBuilder\BBApplication
     */
    private static $bbapp;
    private static $target;
    private static $renderer;
    private static $container;
    private static $gsaRequest;
    private static $gsaResponse;
    private static $query;
    private static $parser;
    private static $generalGsaResponse;


    private static function initOnPrerenderSearch(Event $event)
    {
        if (self::init($event)) {
            if (false === is_a(self::$target, '\BackBuilder\ClassContent\Block\search_results')) return false;
        } else {
            return false;
        }

        return true;
    }

    private static function init(Event $event)
    {
        self::reset();
        if (null === self::$bbapp = $event->getDispatcher()->getApplication()) return false;
        if (null === self::$target = $event->getTarget()) return false;
        if (null === self::$renderer = $event->getEventArgs()) return false;
        if (null === self::$container = self::$bbapp->getContainer()) return false;
        if (null === self::$query = self::$bbapp->getRequest()->query) return false;
        if (null === self::$gsaRequest = self::$container->get('gsa.request')) return false;
        if (null === self::$parser = ParserFactory::getParser('xml')) return false;

        if ( null !== self::$query->get('type', null))
        {
            $requiredFields = self::$query->get('type');
            self::$query->remove('type');
            self::$query->set('requiredfields', $requiredFields);
        }

        return true;
    }

    private static function reset()
    {
        self::$bbapp = null;
        self::$target = null;
        self::$renderer = null;
        self::$container = null;
        self::$gsaRequest = null;
        self::$gsaResponse = null;
    }

    /**
     * Utility function to get value from complex block parameter
     * waiting for bb5 to implement its own one.
     * @param $parameterName
     * @param $valueName
     * @return string
     */
    private static function getBlockParameterValue($parameterName,$valueName)
    {
        $array = self::$target->getParam($parameterName,'array');
        return isset($array[$valueName])?$array[$valueName]:null;
    }

    private static function getForcedParameterValue($parameterName)
    {
        //allow to force a certain value
        //for instance: the general search result template
        //wants to call a partial "videoinsertSearch"
        //and set requiredfields to typology:video
        return self::$target->getParam($parameterName,'force');
    }


    public static function getGsaRequestParameters()
    {

        $gsaParameters = array();
        $gsaParameters['renderMode'] = !is_null(self::$renderer->getMode())? self::$renderer->getMode() : self::getBlockParameterValue('mode','selected');
        $gsaParameters['sourceType']= self::getBlockParameterValue('source_type','selected');
        $gsaParameters['sourceValue'] = self::getBlockParameterValue('source_value','value');

        switch($gsaParameters['sourceType'])
        {
            case 'query':
                $gsaParameters['searchString'] = self::$query->get($gsaParameters['sourceValue']);
                break;

            case 'fixed':
                $gsaParameters['searchString'] = $gsaParameters['sourceValue'];
                break;

            case 'metatag':
                $gsaParameters['searchString'] = '';//throw new \Exception('source_type "metatag" for search should only be used when previous search has been done');
                if (!is_null(self::$generalGsaResponse)) {
                    $metatag = self::$generalGsaResponse->getMetaTags(self::getBlockParameterValue('source_value','value'));
                    if (!empty($metatag)) {
                        $resultsCount = 0;
                        foreach($metatag as $metatagValue) {
                            if ($metatagValue['count'] > $resultsCount) {
                                $resultsCount = $metatagValue['count'];
                                $gsaParameters['searchString'] = $metatagValue['value'];
                            }
                        }
                    }
                }
                break;
        }

        //1.forced in object
        //2.block param (forced)
        //3.query
        //4.block param (default)
        foreach(array('start','num','requiredfields','inmeta') as $paramName) {

            $gsaParameters[$paramName] = self::getForcedParameterValue($paramName);

            if(is_null($gsaParameters[$paramName]) && self::getBlockParameterValue('force_'.$paramName,'checked')) {
                $gsaParameters[$paramName] = self::getBlockParameterValue($paramName,'value');
            }
            if (is_null($gsaParameters[$paramName])) {
                $gsaParameters[$paramName] = self::$query->get($paramName);
            }
            if (is_null($gsaParameters[$paramName])) {
                $gsaParameters[$paramName] = self::getBlockParameterValue($paramName,'value');
            }
        }

        return $gsaParameters;
    }

    public static function addClusterScripts()
    {
        foreach (self::$container->getParameter('gsa.js_cluster_scripts') as $script) {
            self::$renderer->addFooterScript($script);
        }
    }

    public static function doRequest($gsaRequest)
    {
        $rawResponse = $gsaRequest->send();
        $gsaResponse = self::$parser->parse($rawResponse);

        return $gsaResponse;
    }

    public static function doGeneralGsaRequest()
    {
        $generalGsaRequest = clone self::$gsaRequest;
        $generalGsaRequest->setRequiredFields('');
        $generalGsaRequest->setAsQ('');
        return self::doRequest($generalGsaRequest);
    }

    public static function onPrerenderSearch(Event $event)
    {
        if (!self::initOnPrerenderSearch($event)) {
            return;
        }

        try
        {
            /**
             * @var $renderMode string
             * @var $searchString string
             * @var $start string
             * @var $num string
             * @var $requiredfields string
             * @var $inmeta string
             */

            extract(self::getGsaRequestParameters());
            self::$renderer->setMode($renderMode);
            self::$gsaRequest
                ->setSearchString($searchString)
                ->limit($start,$num)
                ->setRequiredFields($requiredfields)
                ->setAsQ($inmeta);

            $method = 'defaultSearchMode';
            if (!is_null($renderMode) && method_exists(__CLASS__,$renderMode.'SearchMode')) {
                $method = $renderMode.'SearchMode';
            }


            call_user_func(array(__CLASS__,$method));

        } catch (\Exception $e) {
            self::$target
                ->setParam('noQuery',true);
            return;
        }
    }

    public static function fullSearchMode()
    {
        $requiredFields = self::$gsaRequest->getParameters('requiredfields');
        $as_q = self::$gsaRequest->getParameters('as_q');

        self::$gsaResponse = self::doRequest(self::$gsaRequest);

        self::$generalGsaResponse = self::$gsaResponse;

        if (!empty($requiredFields) || !empty($as_q)) {
            self::$generalGsaResponse = self::doGeneralGsaRequest();
        }

        $linkBuilder = new LinkBuilder(self::$gsaRequest, self::$bbapp->getRequest()->getPathInfo());
        $pager = new Pager(self::$gsaRequest, self::$gsaResponse,$linkBuilder);
        //we pass the general response to the filter in ordre to have the right numbers by meta.
        $filter = new Filter(self::$generalGsaResponse,$linkBuilder);

        //force to publish the search_box block
        if (true === is_a(self::$target, 'BackBuilder\ClassContent\Block\search_results')) {
            $searchTextBlock = self::$target->recherche_bloc;
            $searchTextBlock->setState(\BackBuilder\ClassContent\AClassContent::STATE_NORMAL);
            $params = $searchTextBlock->getParam('search_results_page:array');
            $params['value'] = self::$bbapp->getRequest()->getPathInfo();

            $searchTextBlock->setParam('search_results_page', $params, 'array');
        }

        if (null !== $typology = self::$bbapp->getRequest()->query->get('requiredfields', null)) {
            $typology = str_replace('typology:', '', strtolower($typology));
            self::$renderer->assign('active_tab', $typology);
        }

        $threeFirstVideos = array();


        // Create the search_results container and set needed parameters
        $request = self::$bbapp->getRequest();
        $query = $request->get('q');
        self::$target
            ->setParam('response', self::$gsaResponse)
            ->setParam('pager', $pager)
            ->setParam('filter', $filter)
            ->setParam('linkbuilder', $linkBuilder)
            ->setParam('query', $query);


        self::$gsaRequest->setParameters(array('requiredfields'=> 'typology:video', 'searchstring' => self::$gsaRequest->getParameters('searchstring')));
        self::$gsaRequest->limit(0,3);

        $videoList =  self::doRequest(self::$gsaRequest);
        foreach ($videoList->getResults() as $result) {
            if (count($threeFirstVideos) < 3) {
                $threeFirstVideos[] = $result;
            }
        }
        self::$target
            ->setParam('three_first_videos', $threeFirstVideos);
         self::$renderer->addFooterScript(self::$renderer->getUriJs('/ressources/js/gsa_search_results.js'));
        if (null == $typology) {
            self::$gsaRequest->setParameters(array('filter' => 0, 'type'=> '','searchstring' => self::$gsaRequest->getParameters('searchstring')));
        } else {
            self::$gsaRequest->setParameters(array('filter' => 0, 'type'=> 'typology:'.$typology, 'searchstring' => self::$gsaRequest->getParameters('searchstring')));
        }
        self::addClusterScripts();
    }

    public static function defaultSearchMode()
    {
        self::$gsaResponse = self::doRequest(self::$gsaRequest);
        self::$target->setParam('response', self::$gsaResponse);
    }

    private static function getKeywordParams($q = "")
    {
        $parameters = array('video' =>
                array('params' =>
                    array('q' =>  $q,
                        'requiredFields' => 'typology:video',
                        'limit' => 3
                    )
                ),
                'article' =>
                array('params' =>
                    array('q' =>  $q,
                        'requiredFields' => 'typology:article',
                        'limit' => 3
                    )
                ),
                'dossier' =>
                array('params' =>
                    array('q' =>  $q,
                        'requiredFields' => 'typology:dossier',
                        'limit' => 3
                    )
                ),
                'diaporama' =>
                array('params' =>
                    array('q' =>  $q,
                        'requiredFields' => 'typology:diaporama',
                        'limit' => 3
                    )
                ),
            );
        return $parameters;
    }

}
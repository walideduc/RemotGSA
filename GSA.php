<?php
namespace BackBee\Bundle\GSABundle;

use BackBee\Bundle\AbstractBundle;
use BackBee\Bundle\GSABundle\Model\Response\ParserFactory;
use BackBee\Bundle\GSABundle\Model\Response\XmlParser;
use BackBee\ClassContent\AbstractClassContent;
use BackBee\NestedNode\Page;
use BackBee\ClassContent\Block\SearchResults;
use BackBee\ClassContent\Block\Recherche;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Response;
use BackBee\Bundle\GSABundle\Model\Filter;
use BackBee\Bundle\GSABundle\Model\LinkBuilder;
use BackBee\Bundle\GSABundle\Model\Pager;
use Symfony\Component\Yaml\Parser;

use Symfony\Component\HttpFoundation\Request;

class GSA extends AbstractBundle
{
    /**
     * Start the bundle
     * @return \BackBee\Bundle\GSABundle\GSA
     */
    public function start()
    {
        return $this;
    }

    /**
     * Stop the bundle
     * @return \BackBee\Bundle\GSABundle\GSA
     */
    public function stop()
    {
        return $this;
    }

    /**
     *
     * @param Request
     */


    public function searchAction(Request $request)
    {
        $response = new Response();
        $response->headers->set('content-type','text/html');

        $bbapp = $this->getApplication();
        $em = $bbapp->getEntityManager();
        $query = $bbapp->getRequest()->query;
        $searchObj = $query->all();
        if (empty($searchObj)) {
            header("Location: /");
            exit;
        }
        $searchString = strip_tags(html_entity_decode($searchObj['q']));
        if ($searchString === '') {
            header("Location: /");
            exit;
        }
        $query->set('q', $searchString);
        $gsaRequest = $bbapp->getContainer()->get('gsa.request');
        $submittedQuery =  $query->all();



        $gsaRequest->setParameters($submittedQuery);

        $result = $gsaRequest->send();
        $parser = ParserFactory::getParser('xml');
        $gsaResponse = $parser->parse($result);

//        debug($submittedQuery['q']);
        if(isset($submittedQuery['dnavs']) || strpos('inmeta',$submittedQuery['q']) ){
            $explode = explode('inmeta:', $submittedQuery['q']);
            //debug($explode);
            foreach ($explode as $value){
                //debug($value);
                if (strpos($value,'=')){
                    $explode2 = explode('=', $value);
                    $submittedQuery['tracking'][$explode2[0]] = $explode2[1];
                    //dd($explode2);
                }
                if (strpos($value,':')){
                    $explode2 = explode(':', $value);
                    $submittedQuery['tracking'][$explode2[0]] = $explode2[1];
                    //dd($explode2);
                }
            }
            $inmeta_removed = str_replace('inmeta:','',$submittedQuery['dnavs']);
            $explode = explode('=',trim($inmeta_removed));
        }
        //debug($submittedQuery);
//        debug($submittedQuery);
        // Create the search_results container and set needed parameters
        $searchResultsBlock = new Recherche();
        $searchResultsBlock->setState(AbstractClassContent::STATE_NORMAL);
        $searchResultsBlock->recherche_results_bloc
            ->setState(AbstractClassContent::STATE_NORMAL)
            ->setParam('submitted_query', $submittedQuery);


        // right block search
        if(null !== ($gsaResponse->getTopKeyword())) {
            foreach( $this->getKeywordParams($gsaResponse->getTopKeyword()) as $key => $param) {
                $gsaRequestRight = $bbapp->getContainer()->get('gsa.request');
                $gsaRequestRight->setRequiredFields($param['params']['requiredFields']);
                $gsaRequestRight->limit(0,$param['params']['limit']);

                $resultRight = $gsaRequestRight->send($gsaResponse->getTopKeyword());

                $parserRight = ParserFactory::getParser('xml');
                $gsaResponseRight = $parserRight->parse($resultRight);

                $searchResultsBlock->recherche_right_bloc
                    ->setState(AbstractClassContent::STATE_NORMAL)
                    ->setParam('response_'.strtolower($key), [
                        'parameters' => $gsaResponseRight->getParameters(),
                        'results'    => $gsaResponseRight->getResults()
                    ]);
            }
        }

        // Create page with right layout
        $site = $this->getApplication()->getSite();
        $root = $em->getRepository('BackBee\NestedNode\Page')->getRoot($site);
        $layout = $em->find('BackBee\Site\Layout', md5('searchlayout-' . $site->getLabel()));

        $renderMode = null ;
        if ($site->getLabel() ==  '01net'){
            $renderMode = '_01net' ;
        }

        $pagebuilder = $bbapp->getContainer()->get('pagebuilder');

        $pagebuilder->setRoot($root)
            ->setTitle('Résultat recherche')
            ->putOnlineAndHidden()
            ->setSite($site)
            ->setParent($root)
            ->setLayout($layout)
            ->pushElement($searchResultsBlock);

        $renderer = $this->getApplication()->getRenderer();

        $response->setContent($renderer->render($pagebuilder->getPage(),$renderMode));

        $response->send();
    }


    public static function getKeywordParams($q = "")
    {
        $parameters = [
            'video' => [
                'params' => [
                    'q' =>  $q,
                    'requiredFields' => 'typology:video',
                    'limit' => 3
                ]],
            'article' => [
                'params' => [
                    'q' =>  $q,
                    'requiredFields' => 'typology:article',
                    'limit' => 3
                ]],
            'dossier' => [
                'params' => [
                    'q' =>  $q,
                    'requiredFields' => 'typology:dossier',
                    'limit' => 3
                ]],
            'diaporama' => [
                'params' => [
                    'q' =>  $q,
                    'requiredFields' => 'typology:diaporama',
                    'limit' => 3
                ]],
            ];

        return $parameters;
    }

    /**
     * This action is used to get related researches
     * base on the initial query.
     * The name of the route has to be /cluster because
     * google javascripts has it hardcoded
     * @see gsa1.01net.com/cluster.js
     */
    public function clusterAction()
    {
        $bbapp = $this->getApplication();
        $query = $bbapp->getRequest()->query;
        $container = $bbapp->getContainer();

        if($query->get('q') != '' && $query->get('q') != null) {

            $response = new Response();
            $response->headers->set('content-type','text/javascript');
            $serverAddress = $container->getParameter('gsa.server_address');
            $client = $container->get('guzzle.http_client');
            $url = 'http://'.$serverAddress.'/cluster?coutput=json&q='.$query->get('q').'&client=default_frontend&output=xml_no_dtd&proxystylesheet=default_frontend&entqr=3&oe=UTF-8&ie=UTF-8&ud=1&site='.$query->get('s').'&access=p';
            $request = $request = $client->post($url);
            $response->setContent((string)$request->send()->getBody());
            $response->send();
        }
    }


    public function getTypologyParams($q = "")
    {
        $parameters = [
            'video' => [
                'params' => [
                    'partialFields' => 'title:'. $q .'.chapoarticle:'. $q .'.thumbnailpublicurl.typology:video',
                    'limit' => 2
                ]],
            'article' => [
                'params' => [
                    'partialFields' => 'title:'. $q .'.chapoarticle:'. $q .'.thumbnailpublicurl.typology:article',
                    'limit' => 1
                ]],
            'logiciel' => [
                'params' => [
                    'partialFields' => 'editeur:'. $q .'.title:'. $q .'.thumbnailpublicurl.typology:logiciel',
                    'limit' => 1
                ]],
            'produit' => [
                'params' => [
                    'partialFields' => 'title:'. $q .'.fabriquant:'. $q .'.thumbnailpublicurl.typology:produit',
                    'limit' => 1
                ]],
            ];

        return $parameters;
    }

    public function suggestAction()
    {
        $bbapp = $this->getApplication();
        $query = $bbapp->getRequest()->query;
        $container = $bbapp->getContainer();
        $renderer = $bbapp->getRenderer();

        if($query->get('q') != '' && $query->get('q') != null) {



            $response = new Response();
            $response->headers->set('content-type','text/html');

            $serverAddress = $container->getParameter('gsa.server_address');
            $client = $container->get('guzzle.http_client');

            // Suggest search
            $urlSuggest = 'http://'.$serverAddress . '/suggest?token='.$query->get('q').'&max_matches=5';

            $requestSuggest = $client->get($urlSuggest);


            $suggestResult = json_decode((string)$requestSuggest->getBody());

            $firstSuggest = "";

            if ( !empty($suggestResult) )
            {
                $firstSuggest = $suggestResult[0];
            }
            $routing = $this->getConfig()->getSection('route');
            $path = $routing['gsa.bundle.search']['pattern'];

            $render = $renderer->partial('Result/ResultSuggestSearchTextbox.twig', array(
                'suggestResult' => $suggestResult,
                'page' => $query->get('page'),
                'firstSuggest' => $firstSuggest,
                'path' => $path));
            $response->setContent($render);
            $response->send();
        }

    }

    public function typologyAction()
    {
        $bbapp = $this->getApplication();
        $query = $bbapp->getRequest()->query;
        $renderer = $bbapp->getRenderer();

        $suggest = $query->get('firstSuggest');
        if($suggest != '' && $suggest != null) {

            $response = new Response();
            $response->headers->set('content-type','text/html');

            $parameters = $this->getTypologyParams($suggest);

            $responses = array();

            // Typology search
            $gsaRequest = $bbapp->getContainer()->get('gsa.request');
            $xmlParser = ParserFactory::getParser('xml');

            $gsaRequest->setSearchString($suggest);
            $gsaRequest->setResultCountPrecision(0);

            foreach( $parameters as $key => $param) {
                $gsaRequest->limit($param['params']['limit'],1);
                $gsaRequest->setPartialFields($param['params']['partialFields']);
                $responses[$key] = $xmlParser->parse($gsaRequest->send());
            }

            $render = $renderer->partial('Result/ResultTypologySearchTextbox.twig', [
                    'page' => $query->get('page'),
                    'typologyResult' => $responses
                ]);
            $response->setContent($render);
            $response->send();
        }
    }
}

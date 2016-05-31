<?php
namespace BackBee\Renderer\Helper;

class FormGSA extends AbstractHelper
{
    public function __invoke($query)
    {

        $request = $this->_renderer->getApplication()->getController()->getRequest();

        $sheme = $request->getScheme();
        $host = $request->getHost();
        $page = $request->getPathInfo();
        $query = $request->query;
        $queryString = $request->getQueryString();

        $filter = null;
        $Selectfacet = '';
        $q = null;
        foreach ($query as $key => $value) {
            if ($key == 'q') {
                $q = $key . "=" . $value;
                $filter = explode('inmeta', $q);
                $q = str_replace('q=','', trim($filter[0]));
                /*if(count($filter) > 1)
                { $q = str_replace('q=','', trim($filter[0]));}*/


                foreach($filter as $key => $value) {
                    if($key > 0) {
                        $Selectfacet .= 'inmeta'.$value;
                    }
                }

            }



        }

        $tab = array($q, $Selectfacet );
//        debug($tab);
        return $tab;
    }
}

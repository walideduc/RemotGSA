<?php
namespace BackBee\Renderer\Helper;

class UrlGSA extends AbstractHelper
{
    /**
     * @param $inmeta
     * @param null $inmetaDelete
     * @param bool|false $range
     */
    public function __invoke($inmeta, $inmetaDelete = null, $range = false, $min = null, $max= null, $NameFacet = null)
    {
        if($range == false) {
           return $this->generateInmeta($inmeta, $inmetaDelete);
        } else {

            return $this->generateInmetaRange($min, $max, $NameFacet);
        }
    }

    /**
     * @param $inmeta
     * @param null $inmetaDelete
     * @return array
     */
    private function generateInmeta($inmeta, $inmetaDelete = null) {

        $request = $this->_renderer->getApplication()->getController()->getRequest();

        $sheme = $request->getScheme();
        $host = $request->getHost();
        $page = $request->getPathInfo();
        $query = $request->query;

        $queryString = $request->getQueryString();


        $bool = false;


        $inmetaTraitement = str_replace(' ', '%2520', $inmeta);
        $inmetaTraitement = str_replace('.', '%252E', $inmeta);
        $inmetaTraitement = str_replace('%20', '%2520', $inmetaTraitement);
        $inmetaTraitement = urlencode($inmetaTraitement);
        $inmetaTraitement = str_replace('%252520', '%2520', $inmetaTraitement);

        $pos = strpos($queryString, $inmetaTraitement);

        $inmeta = str_replace(' ', '%2520', $inmeta);
        $inmeta = str_replace('%20', '%2520', $inmeta);
        $inmeta = str_replace('.', '%252E', $inmeta);


        if($pos) {
            $bool = true;
        }

        $createQuery = null;
        $dnavs = false;

        foreach ($query as $key => $value) {
            if ($key == 'q') {
                $createQuery = $key . "=" . $value . "+" . $inmeta;
            } else if ($key == 'dnavs') {
                $dnavs = true;
                $createQuery .= "&" . $key . "=" . $value . "+" . $inmeta;
            } else {
                $createQuery .= "&" . $key . "=" . $value;
            }

        }

        $createQuery = str_replace('%20', '%2520', $createQuery);
        if ($inmetaDelete == 'true') {
            $createQuery = str_ireplace($inmeta, '', $createQuery);
            $createQuery = str_ireplace('  ', ' ', $createQuery);
        }

        if ($dnavs == false) {
            $url = $sheme . '://' . $host . $page . '?' . $createQuery . '&dnavs=' . $inmeta;
        } else {
            $url = $sheme . '://' . $host . $page . '?' . $createQuery;
        }



        $tab = array($url, $bool );

        return $tab;

    }

    private function generateInmetaRange($min, $max, $NameFacet) {

        $request = $this->_renderer->getApplication()->getController()->getRequest();

        $sheme = $request->getScheme();
        $host = $request->getHost();
        $page = $request->getPathInfo();
        $query = $Q = $request->query;

        $queryString = html_entity_decode($request->getQueryString());

        $createQuery = null;
        $Query= null;
        $String = null;
        $SelectValue = array();
        foreach ($query as $key => $value) {
            $tab = explode("inmeta:", html_entity_decode($value));
            if($key == 'q') {
                foreach ($tab as $k => $v) {
                    if($k == 0) {
                        $Query = $key."=".$v;
                    } else {
                        $ExplodeFacet = explode($NameFacet.":", html_entity_decode($v));
                        if(count($ExplodeFacet) == 1) {
                            $String = $String.'+inmeta:'.$ExplodeFacet[0];
                        } else {
                            $SelectValue[] = str_replace("..","",trim($ExplodeFacet[1]));
                        }
                    }
                }
                $Query = trim($Query).$String;
            } else {
                $Query = trim($Query).'&'.$key."=".$value;
            }

        }
        parse_str($Query,$query);

        //$query = str_replace('  ', ' ', $query);
        $query = str_replace('%20', '%2520', $query);


        $NameFacet = str_replace(' ', '%2520', $NameFacet);
        $NameFacet = str_replace('%20', '%2520', $NameFacet);
        $inmeta = "inmeta:".$NameFacet.":".$min."..+inmeta:".$NameFacet.":..".$max;

        $dnavs = false;
        foreach ($query as $key => $value) {
            if ($key == 'q') {
                $createQuery = $key . "=" . $value . "+" . $inmeta;
            } else if ($key == 'dnavs') {
                $dnavs = true;
                $createQuery .= "&" . $key . "=" . $value . "+" . $inmeta;
            } else {
                $createQuery .= "&" . $key . "=" . $value;
            }

        }


        if ($dnavs == false) {
            $url = $sheme . '://' . $host . $page . '?' . $createQuery . '&dnavs=' . $inmeta;
        } else {
            $url = $sheme . '://' . $host . $page . '?' . $createQuery;
        }


        $tab = array(htmlspecialchars_decode($createQuery), $SelectValue);

        return $tab;
    }

}

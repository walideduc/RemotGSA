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

        $pos = strpos(rawurldecode(urldecode($queryString)), rawurldecode(urldecode($inmeta)));


        $createQuery = null;
        $dnavs = false;

        if($pos) {
            $bool = true;
        }

        foreach ($query as $key => $value) {
            if ($key == 'q') {
                $value = str_replace("%","%25",$value);
                $createQuery = $key . "=" . $value . "+" . $inmeta;
            }

            else if ($key == 'dnavs' ) {
                $dnavs = true;
                if($bool == false) {
                    $createQuery .= "&" . $key . "=" . $value;
                }
            }
           else {
                $createQuery .= "&" . $key . "=" . $value;
            }

        }

        if ($inmetaDelete == 'true') {

            if(strpos($inmeta,'inmeta:typology') !== false){
//                debug($createQuery);
                $tab = explode(' ',$createQuery);
                $createQuery = $tab[0].'&filter=0';
            }else{
                $createQuery = str_ireplace($inmeta, '', $createQuery);
                $createQuery = str_ireplace('  ', ' ', $createQuery);
            }

        }

        if ($dnavs == false ) {
            if($bool == false) {
                $url = $sheme . '://' . $host . $page . '?' . $createQuery . '&dnavs=' . $inmeta;
            } else {
                $url = $sheme . '://' . $host . $page . '?' . $createQuery;
            }

        } else {
            $url = $sheme . '://' . $host . $page . '?' . $createQuery;
        }

        $tab = array($url, $bool );

        return $tab;

    }

    private function generateInmetaRange($min, $max, $NameFacet) {

        $request = $this->_renderer->getApplication()->getController()->getRequest();
        $query  = $request->query;
        $createQuery = null;
        $Query= null;
        $String = null;
        $SelectValue = array();
        $inmeta = "inmeta:".$NameFacet.":".$min."..+inmeta:".$NameFacet.":..".$max;

        foreach ($query as $key => $value) {
            $tab = explode("inmeta:", $value);
            if($key == 'q') {
                foreach ($tab as $k => $v) {
                    if($k == 0) {
                        $Query = $key."=".$v;
                    } else {
                        $ExplodeFacet = explode($NameFacet.":", $v);
                        if(count($ExplodeFacet) == 1) {
                            $String = $String.'+inmeta:'.$ExplodeFacet[0];
                        } else {
                            $SelectValue[] = str_replace("..","",trim($ExplodeFacet[1]));
                        }
                    }
                }
                $Query = trim($Query).trim($String)." ".$inmeta;
            } else {
                $Query = trim($Query).'&'.$key."=".$value;
            }

        }

        $createQuery = $Query;
        parse_str($Query,$query);
        $createQuery = str_replace("%","%25",$createQuery);

        $tab = array($createQuery, $SelectValue);

        return $tab;
    }


    private function CleanString($text) {

        $tab =  array("%" => "%25", " " => "%20", "!" => "%21", "\""=> "%22", "." => "%2E",
            "&" => "%26", "(" => "%28", ")" => "%29", "," => "%2C",
            "'" => "%27",  "/" => "%2F", "\\" => "%5C",
            "-" => "%2D", "_" => "%5F", "[" => "%5B", "]" => "%5D",
            "*" => "%2A",
        );

        foreach ($tab as $key => $str) {
            $text = str_ireplace($key, $str, $text);
        }

        $text = str_replace("%","%25",$text);
        return $text;
    }

}

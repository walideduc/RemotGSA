<?php
namespace BackBee\Renderer\Helper;

class UrlGSA extends AbstractHelper
{
    public function __invoke($inmeta, $inmetaDelete = null)
    {

        //$pos = strpos($mystring, $findme);



        $request = $this->_renderer->getApplication()->getController()->getRequest();

        $sheme = $request->getScheme();
        $host = $request->getHost();
        $page = $request->getPathInfo();
        $query = $request->query;

        $queryString = $request->getQueryString();

        $bool = false;


        $inmetaTraitement = str_replace(' ', '%2520', $inmeta);
        $inmetaTraitement = str_replace('%20', '%2520', $inmetaTraitement);
        $inmetaTraitement = urlencode($inmetaTraitement);
        $inmetaTraitement = str_replace('%252520', '%2520', $inmetaTraitement);

        $pos = strpos($queryString, $inmetaTraitement);

        $inmeta = str_replace(' ', '%2520', $inmeta);
        $inmeta = str_replace('%20', '%2520', $inmeta);


        if($pos) {
            $bool = true;
        }
        /* ******************************* */



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

        //$result = StringUtils::urlize($str);
        if ($dnavs == false) {
            $url = $sheme . '://' . $host . $page . '?' . $createQuery . '&dnavs=' . $inmeta;
        } else {
            $url = $sheme . '://' . $host . $page . '?' . $createQuery;
        }


        $tab = array($url, $bool );
        return $tab;
    }
}

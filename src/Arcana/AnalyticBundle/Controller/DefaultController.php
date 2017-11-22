<?php

namespace Arcana\AnalyticBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

class DefaultController extends Controller
{
    /**
     * @param Request $request
     * @param null $startDate
     * @param string $domain
     * @return mixed
     */
    public function indexAction(Request $request, $startDate = null, $domain = 'default')
    {

        if(!empty($startDate)){
            //if selected by date - domain comes from ajax
            $domain = $request->request->get('domain');
        }

        try {
            $aaParameters = $this->container->getParameter('aa_'.$domain);
        } catch(InvalidArgumentException $e) {

            try {
                $this->container->getParameter('aa_domain_code');
            } catch(InvalidArgumentException $e){

            }

            $aaParameters = array(
                'aa_usermail'    => $this->container->getParameter('aa_usermail'),
                'aa_userpass'    => $this->container->getParameter('aa_userpass'),
                'aa_token'       => $this->container->getParameter('aa_token'),
                'aa_domain_code' => $this->container->getParameter('aa_domain_code')
            );
        }

        $api = $this->getApi($domain);

        $api->requestAccountData();
        $apiResult = $api->getResults();

        $webPropertyId = $api->getWebPropertyId();

        foreach($apiResult AS $result){
            if($result->getWebPropertyId() == $webPropertyId){
                $profileId = $result->getProfileId();
                break;
            }
        }

        $visitsChartData    = $api->getVisitsGraphData($profileId,$startDate);
        $visitsSummaryData  = $api->getVisitsSummaryData($profileId,$startDate);
        $visitsLanguageData = $api->getVisitsLanguageData($profileId,$startDate);

        if(!$startDate){
            //if no ajax request for data
            $view = 'ArcanaAnalyticBundle:Default:index.html.twig';
        }else{
            //if requested by ajax
            $view = 'ArcanaAnalyticBundle:Default:visitors.html.twig';
        }

        return $this->render($view, array(
            'visitChartData'  => $visitsChartData,
            'startDate'       => $startDate,
            'summary'         => $visitsSummaryData,
            'languageData'    => $visitsLanguageData,
            'domain'          => $domain
        ));

    }

    /**
     * Gets seperate information for tabs
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function requestAction(Request $request)
    {
        $startDate = $request->request->get('startDate');
        $domain    = $request->request->get('domain');
        $request   = $request->request->get('request');

        $api = $this->getApi($domain);
        $api->requestAccountData();
        $apiResult = $api->getResults();

        $webPropertyId = $api->getWebPropertyId();

        foreach($apiResult AS $result){
            if($result->getWebPropertyId() == $webPropertyId){
                $profileId = $result->getProfileId();
                break;
            }
        }

        switch($request) {
            case 'location':
                $data = array(
                    'geoData' => $api->getGeoData($profileId,$startDate)
                );
                $view = 'ArcanaAnalyticBundle:Default:table_location.html.twig';
                break;
            case 'system':
                $data = array(
                    'technicalData' => $api->getTechnicalData($profileId,$startDate),
                );
                $view = 'ArcanaAnalyticBundle:Default:table_system.html.twig';
                break;
            case 'traffic':
                $data = array(
                    'trafficData' => $api->getTrafficData($profileId,$startDate),
                );
                $view = 'ArcanaAnalyticBundle:Default:table_traffic.html.twig';
                break;
            case 'tracking':
                $data = array(
                    'pageTrackingData' => $api->getPageTrackingData($profileId,$startDate),
                );
                $view = 'ArcanaAnalyticBundle:Default:table_tracking.html.twig';
                break;
            default:
                $data = array();
                $view = 'ArcanaAnalyticBundle:Default:no_data.html.twig';
        }

        return $this->render($view, $data);

    }

    private function getApi($domain)
    {
        try {
            $aaParameters = $this->container->getParameter('aa_'.$domain);
        } catch(InvalidArgumentException $e) {

            try {
                $this->container->getParameter('aa_domain_code');
            } catch(InvalidArgumentException $e){

            }

            $aaParameters = array(
                'aa_usermail'    => $this->container->getParameter('aa_usermail'),
                'aa_userpass'    => $this->container->getParameter('aa_userpass'),
                'aa_token'       => $this->container->getParameter('aa_token'),
                'aa_domain_code' => $this->container->getParameter('aa_domain_code')
            );
        }

        $api = $this->get('arcana.google.api');
        $api->init(
            $aaParameters['aa_usermail'],
            $aaParameters['aa_userpass'],
            $aaParameters['aa_token'],
            $aaParameters['aa_domain_code']
        );
        return $api;
    }

}

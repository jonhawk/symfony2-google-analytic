<?php

namespace Arcana\AnalyticBundle\Service;

use Arcana\AnalyticBundle\Utils\GoogleUtils;
use \Exception;
use Arcana\AnalyticBundle\Google\GoogleAccountEntry;
use Arcana\AnalyticBundle\Google\GoogleReportEntry;

class GoogleAPI
{
    const http_interface = 'auto'; //'auto': autodetect, 'curl' or 'fopen'

    const client_login_url = 'https://www.google.com/accounts/ClientLogin';
    const account_data_url = 'https://www.googleapis.com/analytics/v2.4/management/accounts/~all/webproperties/~all/profiles'; //https://www.google.com/analytics/feeds/accounts/default
    const report_data_url  = 'https://www.googleapis.com/analytics/v2.4/data';//'https://www.google.com/analytics/feeds/data';
    const interface_name = 'GAPI-1.3';
    const dev_mode = false;

    private $auth_token = null;
    private $account_entries = array();
    private $account_root_parameters = array();
    private $report_aggregate_metrics = array();
    private $report_root_parameters = array();
    private $results = array();
    private $webPropertyId = null;

    /**
     * Constructor function for all new gapi instances
     */
    public function __construct()
    {

    }

    /**
     * Initializes everything with given parameters
     * @param $email
     * @param $password
     * @param null $token
     * @param null $webPropertyId
     */
    public function init($email, $password, $token=null, $webPropertyId = null)
    {
        if(!empty($token)) {
            $this->auth_token = $token;
        } else {
            $this->authenticateUser($email,$password);
        }
        $this->webPropertyId = $webPropertyId;
    }

    /**
     * Return the project id
     *
     * @return String
     */
    public function getWebPropertyId()
    {
        return $this->webPropertyId;
    }

    /**
     * Return the auth token, used for storing the auth token in the user session
     *
     * @return String
     */
    public function getAuthToken()
    {
        return $this->auth_token;
    }

    /**
     * Request account data from Google Analytics
     *
     * @param Int $start_index OPTIONAL: Start index of results
     * @param Int $max_results OPTIONAL: Max results returned
     * @throws Exception
     * @return Array
     */
    public function requestAccountData($start_index = 1, $max_results = 20)
    {
        $response = $this->httpRequest(GoogleAPI::account_data_url, array('start-index'=>$start_index,'max-results'=>$max_results), null, $this->generateAuthHeader());

        if(substr($response['code'],0,1) == '2') {
            return $this->accountObjectMapper($response['body']);
        }

        throw new Exception('GAPI: Failed to request account data. Error: "' . strip_tags($response['body']) . '"');
    }

    /**
     * Gets data for visitors graphic charts
     * @param $profileId string
     * @param $startDate string 'yyyy-mm-dd'
     * @return array
     */
    public function getVisitsGraphData($profileId,$startDate)
    {
        $visitChartData = array();

        $this->requestReportData(
            $profileId,                  //profile id
            array('day','month','year'), //dimensions
            array('visits'),             //metrics
            array('year','month','day'), //sort metrics
            null,                        //filter
            $startDate,                  //start date
            null                         //end date
        );

        foreach($this->getResults() as $result)
        {
            $date = $result->getYear() . '-' . $result->getMonth() . '-' . $result->getDay();
            $visitChartData[$date] = $result->getVisits();
        }

        return $visitChartData;
    }

    /**
     * Gets summary data for visits - visits, unique visitors, pageviews, average visit duration, bounde rate, new visits
     * @param $profileId
     * @param $startDate
     * @return array
     */
    public function getVisitsSummaryData($profileId,$startDate)
    {
        $visits           = 0;
        $visitors         = 0;
        $newVisits        = 0;
        $pageviews        = 0;
        $percentNewVisits = 0;
        $visitBounceRate  = 0;
        $avgVisitTime     = 0;


        $this->requestReportData(
            $profileId,
            null,
            array('visits','visitors','pageviews','newVisits','percentNewVisits','visitBounceRate','avgTimeOnSite'),
            null,
            null,
            $startDate,
            null
        );

        foreach($this->getResults() as $result)
        {
            $visits           = $result->getVisits();
            $visitors         = $result->getVisitors();
            $newVisits        = $result->getNewVisits();
            $pageviews        = $result->getPageviews();
            $percentNewVisits = $result->getPercentNewVisits();
            $visitBounceRate  = $result->getVisitBounceRate();
            $avgVisitTime    = $result->getAvgTimeOnSite();
        }

        return array(
            'visits'           => $visits,
            'visitors'         => $visitors,
            'newVisits'        => $newVisits,
            'pageviews'        => $pageviews,
            'percentNewVisits' => $percentNewVisits,
            'visitBounceRate'  => $visitBounceRate,
            'avgVisitTime'     => GoogleUtils::sec2hms($avgVisitTime),
        );
    }

    public function getVisitsLanguageData($profileId,$startDate)
    {
        $languageData = array(
            'totalVisits' => 0,
            'data'        => array(),
        );

        $result = $this->getSmallData($profileId, $startDate, array('language'));

        //lets return only 12 top results
        if(count($result) > 12){
            $limit = 12;
        }else{
            $limit = count($result);
        }

        for($i = 0; $i < $limit; $i++)
        {
            $languageData['totalVisits'] = $languageData['totalVisits'] + $result[$i]->getVisits();
            $languageData['data'][] = array(
                'language' => $result[$i]->getLanguage(),
                'visits' => $result[$i]->getVisits()
            );
        }

        return $languageData;
    }

    /**
     * Gets geographical data - continent, country
     * @param $profileId string
     * @param $startDate string 'yyyy-mm-dd'
     * @return array
     */
    public function getGeoData($profileId,$startDate)
    {
        $geoData = array(
            'totalVisits' => 0,
            'data' => array(),
        );

        $gapiResponse = $this->getSmallData($profileId, $startDate, array('continent','country'));

        foreach($gapiResponse as $result)
        {
            $geoData['totalVisits'] = $geoData['totalVisits'] + $result->getVisits();
            if(!array_key_exists($result->getContinent(),$geoData['data'])){
                $geoData['data'][$result->getContinent()] = array(
                    'visits' => $result->getVisits(),
                    'countryData' => array(
                        array(
                            'country' => $result->getCountry(),
                            'visits'  => $result->getVisits()
                        ),
                    ),
                );
            }else{
                $geoData['data'][$result->getContinent()]['visits'] = $geoData['data'][$result->getContinent()]['visits'] + $result->getVisits();
                $geoData['data'][$result->getContinent()]['countryData'][] = array(
                    'country' => $result->getCountry(),
                    'visits'  => $result->getVisits()
                );
            }
        }

        return $geoData;
    }

    /**
     * Gets technical data - browser, OS
     * @param $profileId string
     * @param $startDate string 'yyyy-mm-dd'
     * @return array
     */
    public function getTechnicalData($profileId,$startDate)
    {
        $technicalData = array();

        $gapiResponse = $this->getSmallData($profileId, $startDate, array('browser','operatingSystem'));

        //make an array from all data
        foreach($gapiResponse as $result)
        {
            if(!array_key_exists($result->getOperatingSystem(),$technicalData)){
                $technicalData[$result->getOperatingSystem()] = array(
                    'visits' => $result->getVisits(),
                    'browserData' => array(
                        array(
                            'browser' => $result->getBrowser(),
                            'visits'  => $result->getVisits()
                        ),
                    ),
                );
            }else{
                $technicalData[$result->getOperatingSystem()]['visits'] = $technicalData[$result->getOperatingSystem()]['visits'] + $result->getVisits();
                $technicalData[$result->getOperatingSystem()]['browserData'][] = array(
                    'browser' => $result->getBrowser(),
                    'visits'  => $result->getVisits()
                );
            }
        }

        $result = array(
            'totalVisits' => 0,
            'data' => array(),
        );
        $osCounter    = 0;
        $osLimit      = 5;
        if(count($technicalData) < $osLimit){
            $osLimit = count($technicalData);
        }

        //make limited array
        foreach($technicalData AS $os => $data){
            if($osCounter == $osLimit){
                break;
            }
            $result['totalVisits'] = $result['totalVisits'] + $data['visits'];
            $browserLimit = 3;
            $browserCounter = 0;
            if(count($data['browserData']) < $browserLimit){
                $browserLimit = count($data['browserData']);
            }

            $result['data'][$os] = array(
                'visits' => 0,
                'browserData' => array(),
            );

            foreach($data['browserData'] AS $bData){
                if($browserCounter == $browserLimit){
                    break;
                }

                $result['data'][$os]['visits'] = $result['data'][$os]['visits'] + $bData['visits'];

                $result['data'][$os]['browserData'][] = array(
                    'browser' => $bData['browser'],
                    'visits'  => $bData['visits']
                );
                $browserCounter++;
            }

            $osCounter++;
        }

        return $result;
    }

    /**
     * Gets data about traffic - referal, keywords
     * @param $profileId string
     * @param $startDate string 'yyyy-mm-dd'
     * @return array
     */
    public function getTrafficData($profileId,$startDate)
    {
        $result = array(
            'totalVisits' => 0,
            'data' => array(),
        );

        $trafficResult = $this->getSmallData($profileId, $startDate, array('fullReferrer'));

        $limit = 10;
        if(count($trafficResult) < $limit){
            $limit = count($trafficResult);
        }
        $i = 0;

        foreach($trafficResult as $data)
        {
            if($i == $limit){
                break;
            }
            $result['totalVisits'] = $result['totalVisits'] + $data->getVisits();
            $result['data'][] = array(
                'visits' => $data->getVisits(),
                'fullReferrer' => $data->getFullReferrer()
            );
            $i++;
        }

        return $result;
    }

    /**
     * Gets page tracking data
     * @param $profileId string
     * @param $startDate string 'yyyy-mm-dd'
     * @return array
     */
    public function getPageTrackingData($profileId,$startDate)
    {
        $landingPageResult = $this->getTrackinData($profileId, $startDate, 'landingPagePath');

        $secondPageResult = $this->getTrackinData($profileId, $startDate, 'secondPagePath');

        $exitPageResult = $this->getTrackinData($profileId, $startDate, 'exitPagePath');

        $returnData = array(
            'landingData' => $landingPageResult,
            'secondData'  => $secondPageResult,
            'exitData'    => $exitPageResult
        );

        return $returnData;
    }

    /**
     * Gets data from GAPI
     * @param $profileId
     * @param $startDate
     * @param $dimensions
     * @return Array
     */
    private function getSmallData($profileId, $startDate, $dimensions)
    {
        $this->requestReportData(
            $profileId,
            $dimensions,
            array('visits'),
            array('-visits'),
            null,
            $startDate,
            null
        );

        return $this->getResults();
    }

    /**
     * Gets information for page tracking - landing page, second page, exit page
     * @param $profileId
     * @param $startDate
     * @param $dimension
     * @return array
     */
    private function getTrackinData($profileId, $startDate, $dimension)
    {
        $pageResult = $this->getSmallData($profileId, $startDate, array($dimension));

        $result = array(
            'totalVisits' => 0,
            'data' => array(),
        );

        $limit = 5;
        if(count($pageResult) < $limit){
            $limit = count($pageResult);
        }
        $i = 0;

        foreach($pageResult as $data)
        {
            if($i == $limit){
                break;
            }

            $result['totalVisits'] = $result['totalVisits'] + $data->getVisits();

            switch($dimension){
                case 'landingPagePath':
                    $page = $data->getLandingPagePath();
                    break;
                case 'secondPagePath':
                    $page = $data->getSecondPagePath();
                    break;
                case 'exitPagePath':
                    $page = $data->getExitPagePath();
                    break;
            }

            $result['data'][] = array(
                'visits' => $data->getVisits(),
                'page' => $page,

            );
            $i++;
        }

        return $result;
    }

    /**
     * Request report data from Google Analytics
     *
     * $report_id is the Google report ID for the selected account
     *
     * $parameters should be in key => value format
     *
     * @param String $report_id
     * @param Array $dimensions Google Analytics dimensions e.g. array('browser')
     * @param Array $metrics Google Analytics metrics e.g. array('pageviews')
     * @param Array $sort_metric OPTIONAL: Dimension or dimensions to sort by e.g.('-visits')
     * @param String $filter OPTIONAL: Filter logic for filtering results
     * @param String $start_date OPTIONAL: Start of reporting period
     * @param String $end_date OPTIONAL: End of reporting period
     * @param Int $start_index OPTIONAL: Start index of results
     * @param Int $max_results OPTIONAL: Max results returned
     * @throws \Exception
     * @return Array
     */
    public function requestReportData(
        $report_id,
        $dimensions,
        $metrics,
        $sort_metric = null,
        $filter = null,
        $start_date = null,
        $end_date = null,
        $start_index = 1,
        $max_results = 30
    ) {
        $parameters = array('ids'=>'ga:' . $report_id);

        if(is_array($dimensions)) {
            $dimensions_string = '';
            foreach($dimensions as $dimesion) {
                $dimensions_string .= ',ga:' . $dimesion;
            }
            $parameters['dimensions'] = substr($dimensions_string,1);
        }elseif(is_string($dimensions)){
            $parameters['dimensions'] = 'ga:'.$dimensions;
        }

        if(is_array($metrics)) {
            $metrics_string = '';
            foreach($metrics as $metric) {
                $metrics_string .= ',ga:' . $metric;
            }
            $parameters['metrics'] = substr($metrics_string,1);
        }elseif(is_string($metrics)){
            $parameters['metrics'] = 'ga:'.$metrics;
        }

        if($sort_metric == null && isset($parameters['metrics'])) {
            $parameters['sort'] = $parameters['metrics'];
        } elseif(is_array($sort_metric)) {
            $sort_metric_string = '';

            foreach($sort_metric as $sort_metric_value) {
                if (substr($sort_metric_value, 0, 1) == "-") {
                    $sort_metric_string .= ',-ga:' . substr($sort_metric_value, 1); // Descending
                } else {
                    $sort_metric_string .= ',ga:' . $sort_metric_value; // Ascending
                }
            }

            $parameters['sort'] = substr($sort_metric_string, 1);
        } else {
            if (substr($sort_metric, 0, 1) == "-") {
                $parameters['sort'] = '-ga:' . substr($sort_metric, 1);
            } else {
                $parameters['sort'] = 'ga:' . $sort_metric;
            }
        }

        if($filter!=null) {
            $filter = $this->processFilter($filter);
            if($filter!==false) {
                $parameters['filters'] = $filter;
            }
        }

        if($start_date==null) {
            $start_date=date('Y-m-d', strtotime('1 month ago'));
        }

        $parameters['start-date'] = $start_date;

        if($end_date==null) {
            $end_date=date('Y-m-d');
        }

        $parameters['end-date'] = $end_date;

        $parameters['start-index'] = $start_index;
        $parameters['max-results'] = $max_results;

        $parameters['prettyprint'] = GoogleAPI::dev_mode ? 'true' : 'false';

        $response = $this->httpRequest(GoogleAPI::report_data_url, $parameters, null, $this->generateAuthHeader());

        if(substr($response['code'],0,1) == '2') {
            return $this->reportObjectMapper($response['body']);
        }

        throw new Exception('GAPI: Failed to request report data. Error: "' . strip_tags($response['body']) . '"');
    }

    /**
     * Process filter string, clean parameters and convert to Google Analytics
     * compatible format
     *
     * @param String $filter
     * @return String Compatible filter string
     */
    protected function processFilter($filter)
    {
        $valid_operators = '(!~|=~|==|!=|>|<|>=|<=|=@|!@)';

        $filter = preg_replace('/\s\s+/',' ',trim($filter)); //Clean duplicate whitespace
        $filter = str_replace(array(',',';'),array('\,','\;'),$filter); //Escape Google Analytics reserved characters
        $filter = preg_replace('/(&&\s*|\|\|\s*|^)([a-z]+)(\s*' . $valid_operators . ')/i','$1ga:$2$3',$filter); //Prefix ga: to metrics and dimensions
        $filter = preg_replace('/[\'\"]/i','',$filter); //Clear invalid quote characters
        $filter = preg_replace(array('/\s*&&\s*/','/\s*\|\|\s*/','/\s*' . $valid_operators . '\s*/'),array(';',',','$1'),$filter); //Clean up operators

        if(strlen($filter)>0) {
            return urlencode($filter);
        }

        return false;
    }

    /**
     * Report Account Mapper to convert the XML to array of useful PHP objects
     *
     * @param String $xml_string
     * @return Array of gapiAccountEntry objects
     */
    protected function accountObjectMapper($xml_string)
    {
        $xml = simplexml_load_string($xml_string);

        $this->results = null;

        $results = array();
        $account_root_parameters = array();

        //Load root parameters

        $account_root_parameters['updated'] = strval($xml->updated);
        $account_root_parameters['generator'] = strval($xml->generator);
        $account_root_parameters['generatorVersion'] = strval($xml->generator->attributes());

        $open_search_results = $xml->children('http://a9.com/-/spec/opensearchrss/1.0/');

        foreach($open_search_results as $key => $open_search_result) {
            $report_root_parameters[$key] = intval($open_search_result);
        }

        $account_root_parameters['startDate'] = strval($xml->startDate);
        $account_root_parameters['endDate'] = strval($xml->endDate);

        //Load result entries

        foreach($xml->entry as $entry) {
            $properties = array();
            foreach($entry->children('http://schemas.google.com/analytics/2009')->property as $property) {
                $properties[str_replace('ga:','',$property->attributes()->name)] = strval($property->attributes()->value);
            }

            $properties['title'] = strval($entry->title);
            $properties['updated'] = strval($entry->updated);

            $results[] = new GoogleAccountEntry($properties);
        }

        $this->account_root_parameters = $account_root_parameters;
        $this->results = $results;

        return $results;
    }


    /**
     * Report Object Mapper to convert the XML to array of useful PHP objects
     *
     * @param String $xml_string
     * @return Array of gapiReportEntry objects
     */
    protected function reportObjectMapper($xml_string)
    {
        $xml = simplexml_load_string($xml_string);

        $this->results = null;
        $results = array();

        $report_root_parameters = array();
        $report_aggregate_metrics = array();

        //Load root parameters

        $report_root_parameters['updated'] = strval($xml->updated);
        $report_root_parameters['generator'] = strval($xml->generator);
        $report_root_parameters['generatorVersion'] = strval($xml->generator->attributes());

        $open_search_results = $xml->children('http://a9.com/-/spec/opensearchrss/1.0/');

        foreach($open_search_results as $key => $open_search_result) {
            $report_root_parameters[$key] = intval($open_search_result);
        }

        $google_results = $xml->children('http://schemas.google.com/analytics/2009');

        foreach($google_results->dataSource->property as $property_attributes) {
            $report_root_parameters[str_replace('ga:','',$property_attributes->attributes()->name)] = strval($property_attributes->attributes()->value);
        }

        $report_root_parameters['startDate'] = strval($google_results->startDate);
        $report_root_parameters['endDate'] = strval($google_results->endDate);

        //Load result aggregate metrics

        foreach($google_results->aggregates->metric as $aggregate_metric) {
            $metric_value = strval($aggregate_metric->attributes()->value);

            //Check for float, or value with scientific notation
            if(preg_match('/^(\d+\.\d+)|(\d+E\d+)|(\d+.\d+E\d+)$/',$metric_value)) {
                $report_aggregate_metrics[str_replace('ga:','',$aggregate_metric->attributes()->name)] = floatval($metric_value);
            } else {
                $report_aggregate_metrics[str_replace('ga:','',$aggregate_metric->attributes()->name)] = intval($metric_value);
            }
        }

        foreach($xml->entry as $entry) {
            $metrics = array();
            foreach($entry->children('http://schemas.google.com/analytics/2009')->metric as $metric) {
                $metric_value = strval($metric->attributes()->value);

                //Check for float, or value with scientific notation
                if(preg_match('/^(\d+\.\d+)|(\d+E\d+)|(\d+.\d+E\d+)$/',$metric_value)) {
                    $metrics[str_replace('ga:','',$metric->attributes()->name)] = floatval($metric_value);
                } else {
                    $metrics[str_replace('ga:','',$metric->attributes()->name)] = intval($metric_value);
                }
            }

            $dimensions = array();
            foreach($entry->children('http://schemas.google.com/analytics/2009')->dimension as $dimension) {
                $dimensions[str_replace('ga:','',$dimension->attributes()->name)] = strval($dimension->attributes()->value);
            }

            $results[] = new GoogleReportEntry($metrics,$dimensions);
        }

        $this->report_root_parameters = $report_root_parameters;
        $this->report_aggregate_metrics = $report_aggregate_metrics;
        $this->results = $results;

        return $results;
    }

    /**
     * Authenticate Google Account with Google
     *
     * @param String $email
     * @param String $password
     * @throws \Exception
     */
    protected function authenticateUser($email, $password)
    {
        $post_variables = array(
            'accountType' => 'GOOGLE',
            'Email' => $email,
            'Passwd' => $password,
            'source' => GoogleAPI::interface_name,
            'service' => 'analytics'
        );

        $response = $this->httpRequest(GoogleAPI::client_login_url,null,$post_variables);

        //Convert newline delimited variables into url format then import to array
        parse_str(str_replace(array("\n","\r\n"),'&',$response['body']),$auth_token);

        if(substr($response['code'],0,1) != '2' || !is_array($auth_token) || empty($auth_token['Auth'])) {
            throw new Exception('GAPI: Failed to authenticate user. Error: "' . strip_tags($response['body']) . '"');
        }

        $this->auth_token = $auth_token['Auth'];
    }

    /**
     * Generate authentication token header for all requests
     *
     * @return Array
     */
    protected function generateAuthHeader()
    {
        return array('Authorization: GoogleLogin auth=' . $this->auth_token);
    }

    /**
     * Perform http request
     *
     *
     * @param $url
     * @param Array $get_variables
     * @param Array $post_variables
     * @param Array $headers
     * @throws \Exception
     * @return array
     */
    protected function httpRequest($url, $get_variables = null, $post_variables = null, $headers = null)
    {
        $interface = GoogleAPI::http_interface;

        if(GoogleAPI::http_interface =='auto') {
            if(function_exists('curl_exec')) {
                $interface = 'curl';
            } else {
                $interface = 'fopen';
            }
        }

        if($interface == 'curl') {
            return $this->curlRequest($url, $get_variables, $post_variables, $headers);
        } elseif($interface == 'fopen') {
            return $this->fopenRequest($url, $get_variables, $post_variables, $headers);
        }

        throw new Exception('Invalid http interface defined. No such interface "' . GoogleAPI::http_interface . '"');
    }

    /**
     * HTTP request using PHP CURL functions
     * Requires curl library installed and configured for PHP
     *
     * @param $url
     * @param Array $get_variables
     * @param Array $post_variables
     * @param Array $headers
     * @return array
     */
    private function curlRequest($url, $get_variables = null, $post_variables = null, $headers = null)
    {
        $ch = curl_init();

        if(is_array($get_variables)) {
            $get_variables = '?' . str_replace('&amp;','&',urldecode(http_build_query($get_variables)));
        }

        curl_setopt($ch, CURLOPT_URL, $url . $get_variables);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //CURL doesn't like google's cert

        if(is_array($post_variables)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_variables);
        }

        if(is_array($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
        }

        $response = curl_exec($ch);
        $code = curl_getinfo($ch,CURLINFO_HTTP_CODE);

        curl_close($ch);

        return array('body'=>$response,'code'=>$code);
    }

    /**
     * HTTP request using native PHP fopen function
     * Requires PHP openSSL
     *
     * @param $url
     * @param Array $get_variables
     * @param Array $post_variables
     * @param Array $headers
     * @return array
     */
    private function fopenRequest($url, $get_variables = null, $post_variables = null, $headers = null)
    {
        $http_options = array('method'=>'GET','timeout'=>3);

        if(is_array($headers)) {
            $headers = implode("\r\n",$headers) . "\r\n";
        } else {
            $headers = '';
        }

        if(is_array($get_variables)) {
            $get_variables = '?' . str_replace('&amp;','&',urldecode(http_build_query($get_variables)));
        } else {
            $get_variables = null;
        }

        if(is_array($post_variables)) {
            $post_variables = str_replace('&amp;','&',urldecode(http_build_query($post_variables)));
            $http_options['method'] = 'POST';
            $headers = "Content-type: application/x-www-form-urlencoded\r\n" .
                "Content-Length: " . strlen($post_variables) . "\r\n" . $headers;
            $http_options['header'] = $headers;
            $http_options['content'] = $post_variables;
        } else {
            $post_variables = '';
            $http_options['header'] = $headers;
        }

        $context = stream_context_create(array('http'=>$http_options));
        $response = @file_get_contents($url . $get_variables, null, $context);

        return array(
            'body' => $response!==false
                    ? $response
                    : 'Request failed, fopen provides no further information','code'=>$response!==false?'200':'400'
        );
    }



    /**
     * Get Results
     *
     * @return Array
     */
    public function getResults()
    {
        $response = array();
        if(is_array($this->results)) {
            $response = $this->results;
        }

        return $response;
    }


    /**
     * Get an array of the metrics and the matchning
     * aggregate values for the current result
     *
     * @return Array
     */
    public function getMetrics()
    {
        return $this->report_aggregate_metrics;
    }

    /**
     * Call method to find a matching root parameter or
     * aggregate metric to return
     *
     * @param $name String name of function called
     * @param $parameters
     * @throws \Exception if not a valid parameter or aggregate
     * metric, or not a 'get' function
     * @return String
     */
    public function __call($name,$parameters)
    {
        if(!preg_match('/^get/',$name)) {
            throw new Exception('No such function "' . $name . '"');
        }

        $name = preg_replace('/^get/','',$name);

        $parameter_key = GoogleAPI::array_key_exists_nc($name,$this->report_root_parameters);

        if($parameter_key) {
            return $this->report_root_parameters[$parameter_key];
        }

        $aggregate_metric_key = GoogleAPI::array_key_exists_nc($name,$this->report_aggregate_metrics);

        if($aggregate_metric_key) {
            return $this->report_aggregate_metrics[$aggregate_metric_key];
        }

        throw new Exception('No valid root parameter or aggregate metric called "' . $name . '"');
    }
}

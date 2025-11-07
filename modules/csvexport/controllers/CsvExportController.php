<?php

namespace csvexport\controllers;

use Craft;
use craft\web\Controller;

use Typesense\Client;

use yii\web\Response;

class CsvExportController extends Controller
{
    protected array|int|bool $allowAnonymous = true;


    public function actionIndex() {
        if ($this->request->getQueryParams()) {
            $params = $this->request->getQueryParams();
            //return json_encode($params);
            $indexName = 'archived_performances';
            $query = array_key_exists('query', $params[$indexName]) ? $params[$indexName]['query'] : null;
            $searchParams = [];
            $searchParams['query_by'] = 'work, season, orchestra, venue, event_types, notes, event_title';
            $filterArray = [];
            if ($query) {
                $searchParams['q'] = $query;
                $filterArray[] = $query;
            }
            $refinementList = array_key_exists('refinementList', $params[$indexName]) ? $params[$indexName]['refinementList'] : null;
            //return json_encode($refinementList);
            $range = array_key_exists('range', $params[$indexName]) ? $params[$indexName]['range'] : null;
            if ($refinementList || $range) {
                $searchParams['filter_by'] = "";
                if ($refinementList) {
                    foreach ($refinementList as $key => $value) {
                        foreach ($value as $refinement) {
                            if ($searchParams['filter_by'] != "") {
                                $searchParams['filter_by'] .= " && ";
                            }
                            $searchParams['filter_by'] = $key . ":=" . $refinement;
                            $filterArray[] = $refinement;
                        }                    
                    }
                }
                if ($range) {
                    foreach ($range as $key => $value) {
                        $values = explode(":", $value);
                        //return json_encode($values);
                        $rangeFilter = null;
                        if ($values[1] == "") {
                            $rangeFilter = $key . ":>=" . $values[0];
                        } elseif ($values[0] == "") {
                            $rangeFilter = $key . ":<=" . $values[1];
                        } elseif (count($values) == 2) { 
                            $rangeFilter = $key . ":[" . $values[0] . ".." . $values[1] . "]";
                        } 
                        if ($searchParams['filter_by'] != "") {
                            $searchParams['filter_by'] .= " && ";
                        }
                        $searchParams['filter_by'] .= $rangeFilter;
                    }
                }
            }
            //return json_encode($filterArray);
            

       
            $client = new Client(
                [
                    'api_key'         => 'qoWHCTjesGfIaxdXbw9vOgod1VToEXNI',
                    'nodes'           => [
                    [
                        'host' => 'go8f04wi19tuvlyrp-1.a1.typesense.net',
                        'port' => '443',      
                        'protocol' => 'https' 
                    ],
                    ],
                    'connection_timeout_seconds' => 2,
                ]
            );


            $result = $client->collections[$indexName]->documents->search($searchParams);

            $hits = array_key_exists('hits', $result) ? $result['hits'] : null;
            $shownWorks = [];
            if ($hits) {
                $filename = "Performance";
                if ($query) {
                    $filename .= strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $query)));
                } elseif ($refinementList) {
                    $filename .= strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', array_values($refinementList)[0][0])));
                }
                $filename .= "-" . date('Y-m-d') . ".csv";

                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename=' . $filename);
                $file = fopen('php://output', 'w');
                $headers = ['Date/Season/Title', 'Venue', 'Orchestra', 'Conductor', 'Composer/Work', 'Artist/Role'];
                fputcsv($file, $headers);

                foreach ($hits as $hit) {
                    if (array_key_exists('document', $hit)) {
                        $event = $hit['document'];
                        $row = [];
                        if (array_key_exists('performance_date', $event)) {
                            $row[0] = gmdate("Y-m-d", $event['performance_date']);
                        }
                        if (array_key_exists('season', $event)) {
                            $row[0] .= " / " . $event['season'];
                        }
                        $row[0] .= array_key_exists('event_title', $event) ? " / " . $event['event_title'] : "";

                        $row[1] = $event['venue'] . " " . $event['location']['city'] . ", " . $event['location']['state'] . ", " . $event['location']['country'];
                        
                        $works = array_key_exists('work', $event) ? $event['work'] : null;

                        if (array_key_exists('orchestra', $event) && $event['orchestra']) {
                            $row[2] = implode("; ", $event['orchestra']);
                        }

                        $row[3] = "";
                        $row[4] = "";
                        $row[5] = "";
                        
                        if ($works && $refinementList) {
                            foreach ($works as $work) {
                                if ($work && is_array($work)) {
                                    $flatArray = $this->flatten($work, "work");
                                    $workAdded = false;
                                    if ($refinementList) {
                                        $flatRefinement = $this->flatten($refinementList, '');
                                        $intersect = array_intersect_assoc($flatArray, $flatRefinement);
                                        if (count($intersect)) {
                                            $shownWorks[] = $work;
                                            $workAdded = true;
                                        }
                                    }
                                    if (!$workAdded && $query && str_contains(strtolower(json_encode($flatArray)), $query)) {
                                        $shownWorks[] = $work;
                                    }
                                }
                            }
                        } elseif ($works) {
                            $shownWorks = $works;
                        }
                        foreach ($shownWorks as $work) {
                            
                            if (array_key_exists('artist', $work)) {
                                foreach ($work['artist'] as $artist) {
                                    if ($artist['artist_role'] == "Conductor") {
                                        $row[3] = $artist['artist_name'];
                                    } else {
                                        if ($row[5] != "") {
                                            $row[5] .= "; ";
                                        }
                                        $row[5] .= $artist['artist_name'] . " / " . $artist['artist_role'];
                                    }
                                }
                                if (array_key_exists('composer', $work)) {
                                    $row[4] = $work['composer'];
                                }
                                if (array_key_exists('title', $work)) {
                                    if ($row[4] != "") {
                                        $row[4] .= " / ";
                                    }
                                    $row[4] .= $work['title'];
                                }
                            }
                        }
                       
                        if ($row[3] == "" && array_key_exists('conductor', $event) && $event['conductor']) {
                            $row[3] = implode("; ", $event['conductor']);
                        }
                        
                        fputcsv($file, $row);
                    }
                    
                }
            }
  
            fclose($file);

            exit;




            //return json_encode($result);
        }
        return "Error";


    }

    function flatten(array $arr, $prefix = '')
    {
        $out = array();
        $key = $prefix;
        foreach ($arr as $k => $v) {
            $key = (!strlen($prefix)) ? $k : "{$prefix}.{$k}";          
            if (is_array($v)) {
                if (count($v) == 1) {
                    $out[$key] = $v[0];
                } else {
                    $out += $this->flatten($v, $key);
                }
            } else {
                $out[$key] = $v;
            }
        }
        return $out;
    }
}
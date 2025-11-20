<?php

namespace csvexport\controllers;

use Craft;
use craft\helpers\App;
use craft\web\Controller;

use Typesense\Client;

use yii\web\Response;

class CsvExportController extends Controller
{
    protected array|int|bool $allowAnonymous = true;

    private $client = null;

    public function actionIndex() {
        $perfArchive = App::parseEnv('$PERFORMANCE_ARCHIVE') ?? 'performances';
        $artistArchive = App::parseEnv('$ARTIST_ARCHIVE') ?? 'artists';
        $workArchive = App::parseEnv('$WORK_ARCHIVE') ?? 'works';
        $this->client = new Client(
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

       

        if ($this->request->getQueryParams()) {
            $params = $this->request->getQueryParams();
            if (array_key_exists($perfArchive, $params)) {
                //return "Perf archive found";
                return $this->exportPerformances($params, $perfArchive);
            } elseif (array_key_exists($artistArchive, $params)) {
                return $this->exportArtists($params, $artistArchive);
            } elseif (array_key_exists($workArchive, $params)) {
                return $this->exportWorks($params, $workArchive);
            }
            return json_encode($params) . "<br/><br/>" . $perfArchive;
        } 

        return "Houston we have a problem";

    }

    function exportArtists($params, $artistArchive) {

        $query = array_key_exists('query', $params[$artistArchive]) ? $params[$artistArchive]['query'] : null;
        $refinementList = array_key_exists('refinementList', $params[$artistArchive]) ? $params[$artistArchive]['refinementList'] : null;
        $searchParams = $this->getSearchParams($params, $artistArchive, 'artist_name, artist_role, work_title', $query, $refinementList);
        $result = $this->client->collections[$artistArchive]->documents->search($searchParams);
        $hits = array_key_exists('hits', $result) ? $result['hits'] : null;

        $returnInfo = "";

        if ($hits) {
            $filename = $this->getFileName("Artists", $query, $refinementList);
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=' . $filename);
            $file = fopen('php://output', 'w');
            $headers = ['Artist', 'Instrument/Role', 'Composer/Work', '# of Performances'];
            fputcsv($file, $headers);
            foreach ($hits as $hit) {
                if (array_key_exists('document', $hit)) {
                    $artist = $hit['document'];
                    $row = array_fill(0, 4, "");
                    if (array_key_exists("artist_name", $artist)) {
                        $row[0] = $artist["artist_name"];
                    }
                    if (array_key_exists("artist_role", $artist)) {
                        $row[1] = $artist["artist_role"];
                    }
                    if (array_key_exists("composer", $artist)) {
                        $row[2] = $artist["composer"];
                    }
                    if (array_key_exists("work_title", $artist)) {
                        if ($row[2] != "") {
                            $row[2] .= "/";
                        }
                        $row[2] .= $artist["work_title"];
                    }
                    if (array_key_exists("num_performances", $artist)) {
                        $row[3] = $artist["num_performances"];
                    }
                    fputcsv($file, $row);
                    $returnInfo .= json_encode($row) . "<br/><br/>";
                }
            }
        }
            
        //return $returnInfo;
        fclose($file);
        exit;
    }

    function exportPerformances($params, $perfArchive) {
        
        $query = array_key_exists('query', $params[$perfArchive]) ? $params[$perfArchive]['query'] : null;
        $refinementList = array_key_exists('refinementList', $params[$perfArchive]) ? $params[$perfArchive]['refinementList'] : null;
        $searchParams = $this->getSearchParams($params, $perfArchive, 'works, season, ensembles, venue, event_types, notes, event_title', $query, $refinementList);
       
        $result = $this->client->collections[$perfArchive]->documents->search($searchParams);
        $hits = array_key_exists('hits', $result) ? $result['hits'] : [];
        $pages = 1;

        $filename = $this->getFileName("Performance", $query, $refinementList);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        $file = fopen('php://output', 'w');
        $headers = ['Date/Season/Title', 'Venue', 'Ensemble', 'Conductor', 'Composer/Work', 'Artist/Role'];
        fputcsv($file, $headers);

        while ($pages * 250 < $result["found"]) {
            $shownWorks = [];
            if (count($hits)) {
                $returnInfo = "INFO";
                foreach ($hits as $hit) {
                    if (array_key_exists('document', $hit)) {
                        $event = $hit['document'];
                        $row = array_fill(0, 6, "");
                        if (array_key_exists('performance_date', $event)) {
                            $row[0] = gmdate("Y-m-d", $event['performance_date']);
                        }
                        if (array_key_exists('season', $event)) {
                            $row[0] .= " / " . $event['season'];
                        }
                        $row[0] .= array_key_exists('event_title', $event) ? " / " . $event['event_title'] : "";

                        $row[1] = $event['venue'];
                        if ($event["location"]) {
                            $row[1] .= array_key_exists("city", $event['location']) && $event['location']['city'] ? ", " . $event['location']['city'] : "";
                            $row[1] .= array_key_exists('state', $event['location']) && $event['location']['state'] ? ", " . $event['location']['state'] : "";
                            $row[1] .= array_key_exists('country', $event['location']) && $event['location']['country'] ? ", " . $event['location']['country'] : "";
                        }
                        
                        $works = array_key_exists('works', $event) ? $event['works'] : null;

                        if (array_key_exists('ensembles', $event) && $event['ensembles']) {
                            $row[2] = implode("; ", $event['ensembles']);
                        }

                        //filter works as necessary if there are works filters
                        $workFilters = $this->getWorkFilters($refinementList);
                        $returnInfo = "<h2>Work Filters</h2>" . json_encode($workFilters) . "<br/><br/>";

                        if ($works && is_array($workFilters) && count($workFilters)) {
                            foreach ($works as $work) {
                                if ($work && is_array($work)) {
                                    $workAdded = false;
                                    $returnInfo .= json_encode($work) . "<br/><br/><b>" . json_encode($workFilters) . "</b><br/><br/>";
                                    $intersectKeys = array_intersect_key($work, $workFilters);
                                    foreach ($intersectKeys as $key => $value) {
                                        $tempValue = is_array($value) ? $value : [$value];
                                        if (count(array_intersect($tempValue, $workFilters[$key]))) {
                                                $shownWorks[] = $work;
                                                $workAdded = true;
                                        }
                                        if (!$workAdded && $query && str_contains(strtolower(json_encode($work)), $query)) {
                                            $shownWorks[] = $work;
                                        }
                                    }
                                    
                                }
                            }
                        } elseif ($works) {
                            $shownWorks = $works;
                        }
                        foreach ($shownWorks as $work) {
                            if (array_key_exists('conductors', $work)) {
                                $row[3] = implode("; ", $work['conductors']);
                            } 
                            if (array_key_exists('composers', $work)) {
                                $row[4] = implode("; ", $work['composers']);
                            }
                            if (array_key_exists('title', $work)) {
                                if ($row[4] != "") {
                                    $row[4] .= " / ";
                                }
                                $row[4] .= $work['title'];
                            }
                            
                            if (array_key_exists('artists', $work)) {
                                foreach ($work['artists'] as $artist) {
                                    if ($row[5] != "") {
                                        $row[5] .= "; ";
                                    }
                                    $row[5] = $artist['name'] . " / " . $artist['role'];
                                }                                
                            }
                        }
                        
                        if ($row[3] == "" && array_key_exists('conductor', $event) && $event['conductor']) {
                            $row[3] = implode("; ", $event['conductor']);
                        }
                        $returnInfo .= json_encode($row) . "<br/>";
                        fputcsv($file, $row);
                    }
                    
                }
                //return $returnInfo;
            }
            $searchParams["offset"] = $pages * 250;
            $result = $this->client->collections[$perfArchive]->documents->search($searchParams);
            $hits = array_key_exists('hits', $result) ? $result['hits'] : [];
            $pages++;

        }

        fclose($file);

        exit;
        
    } 
    
    function exportWorks($params, $workArchive) {
        $query = array_key_exists('query', $params[$workArchive]) ? $params[$workArchive]['query'] : null;
        $refinementList = array_key_exists('refinementList', $params[$workArchive]) ? $params[$workArchive]['refinementList'] : null;
        $searchParams = $this->getSearchParams($params, $workArchive, 'commission, composers, title', $query, $refinementList);
        $result = $this->client->collections[$workArchive]->documents->search($searchParams);
        $hits = array_key_exists('hits', $result) ? $result['hits'] : null;

        $returnInfo = "";

        if ($hits) {
            $filename = $this->getFileName("Works", $query, $refinementList);
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=' . $filename);
            $file = fopen('php://output', 'w');
            $headers = ['Artist', 'Instrument/Role', 'Composer/Work', '# of Performances'];
            fputcsv($file, $headers);
            foreach ($hits as $hit) {
                 if (array_key_exists('document', $hit)) {
                    $work = $hit['document'];
                    $row = array_fill(0, 4, "");
                    if (array_key_exists("composers", $work)) {
                        $row[0] = implode("; ", $work["composers"]);
                    }
                    if (array_key_exists("title", $work)) {
                        $row[1] = implode("; ", $work["title"]);
                    }
                    if (array_key_exists("creators", $work) && count($work["creators"])) {
                        foreach ($work["creators"] as $creator) {
                            if (array_key_exists("name", $creator) && array_key_exists("role", $creator)) {
                                if ($row[2] != "") {
                                    $row[2] .= "; ";
                                }
                                $row[2] .= $creator["name"] . "/" . $creator["role"];
                            }
                        }
                    }
                    if (array_key_exists("num_performances", $work)) {
                        $row[3] = $work["num_performances"];
                    }
                    fputcsv($file, $row);
                    $returnInfo .= json_encode($row) . "<br/><br/>";
                }
            }
        }

        //return $returnInfo;
        fclose($file);
        exit;

    }

    function getFilename(string $searchType, $query, $refinementList) {
        $filename = $searchType . "_";
        if ($query) {
            $filename .= strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $query)));
        } elseif ($refinementList) {
            $filename .= strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', array_values($refinementList)[0][0])));
        }
        $filename .= "-" . date('Y-m-d') . ".csv";
        return $filename;
    
    }

    function getSearchParams(array $params, string $indexName, string $queryBy, string|null $query, array|null $refinementList) {
        $searchParams = [];
        $searchParams['query_by'] = $queryBy;
        if ($query) {
            $searchParams['q'] = $query;
        }
       
        $range = array_key_exists('range', $params[$indexName]) ? $params[$indexName]['range'] : null;
        if ($refinementList || $range) {
            $searchParams['filter_by'] = "";
            if ($refinementList) {
                foreach ($refinementList as $key => $value) {
                    foreach ($value as $refinement) {
                        if ($searchParams['filter_by'] != "") {
                            $searchParams['filter_by'] .= " && ";
                        }
                        $searchParams['filter_by'] .= $key . ":=" . $refinement;
                    }                    
                }
            }
            if ($range) {
                foreach ($range as $key => $value) {
                    $values = explode(":", $value);
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
        $searchParams['per_page'] = 250;
        return $searchParams;

    }

    function getWorkFilters($refinementList) {
        $returnFilters = [];
        foreach($refinementList as $key => $value) {
            if (str_contains($key, "works")) {
                $workAttribute = substr($key, strpos($key, "works.") + 6);
                $subFilter = [];
                if (str_contains($workAttribute, ".")) {
                    $workSubAttribute = substr($workAttribute, strpos($workAttribute, ".") + 1);
                    $subSubFilter = [];
                    $subSubFilter[$workSubAttribute] = $value;
                    $subFilter[$workAttribute] = $subSubFilter;
                } else {
                    $subFilter[$workAttribute] = $value;
                }
                $returnFilters = array_merge_recursive($returnFilters, $subFilter);
            }
        }
        return $returnFilters;
    }

    function intersect($work, $workFilters) {
        $intersectKeys = array_intersect_key($work, $workFilters);
        return $intersectKeys;
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
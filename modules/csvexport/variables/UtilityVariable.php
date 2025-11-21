<?php

namespace csvexport\variables;
use craft\elements\Entry;
use Craft;
use craft\helpers\App;

class UtilityVariable
{

    public function getPerformanceArchive() {
        return App::parseEnv('$PERFORMANCE_ARCHIVE') ?? 'performances';
    }

    public function getArtistArchive() {
        return App::parseEnv('$ARTIST_ARCHIVE') ?? 'performances';
    }
    
    public function getWorkArchive() {
        return App::parseEnv('$WORK_ARCHIVE') ?? 'performances';
    }

    public function getTypesenseHost() {
        return App::parseEnv('$TYPESENSE_HOST') ?? '';
    }

    public function getSearchKey() {
        return App::parseEnv('$TYPESENSE_SEARCH_KEY') ?? '';
    }

    public function getDocKey() {
        return App::parseEnv('$TYPESENSE_DOC_KEY') ?? '';
    }

}
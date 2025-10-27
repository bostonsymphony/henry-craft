<?php

namespace csvexport\controllers;

use Craft;
use craft\web\Controller;

use yii\web\Response;

class CsvExportController extends Controller
{
    protected array|int|bool $allowAnonymous = true;
    public function actionIndex() {
        return "action index";
    }
}
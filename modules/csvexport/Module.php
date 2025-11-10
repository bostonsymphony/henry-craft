<?php

namespace csvexport;

use Craft;
use yii\base\Module as BaseModule;
use craft\web\twig\variables\CraftVariable;

use yii\base\Event;

use csvexport\variables\UtilityVariable;
/**
 * csvexport module
 *
 * @method static Module getInstance()
 */
class Module extends BaseModule
{
    public function init(): void
    {
        Craft::setAlias('@modules', __DIR__);

        // Set the controllerNamespace based on whether this is a console or web request
        if (Craft::$app->request->isConsoleRequest) {
            $this->controllerNamespace = 'csvexport\\console\\controllers';
        } else {
            $this->controllerNamespace = 'csvexport\\controllers';
        }

        parent::init();

        $this->attachEventHandlers();

        // Any code that creates an element query or loads Twig should be deferred until
        // after Craft is fully initialized, to avoid conflicts with other plugins/modules
        Craft::$app->onInit(function() {
            // ...
        });
    }

    private function attachEventHandlers(): void
    {
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('utility', UtilityVariable::class);
            }
        );

    }
}

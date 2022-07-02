<?php



namespace SixF\TUS\Admin;

use SilverStripe\Admin\ModelAdmin;
use SixF\TUS\Model\Application;

class TusModelAdmin extends ModelAdmin {


    private static $managed_models = [
        Application::class,
    ];

    private static $menu_icon_class = "font-icon-sync";

    private static $url_segment = 'update-server';

    private static $menu_title = 'Update Server';
}

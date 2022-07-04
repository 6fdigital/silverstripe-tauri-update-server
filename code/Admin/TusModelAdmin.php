<?php


namespace SixF\TUS\Admin;

use SilverStripe\Admin\ModelAdmin;
use SixF\TUS\Model\Application;

class TusModelAdmin extends ModelAdmin
{
  /**
   * @var string[]
   */
  private static $managed_models = [
    Application::class,
  ];

  /**
   * @var string
   */
  private static $menu_icon_class = "font-icon-sync";

  /**
   * @var string
   */
  private static $url_segment = 'update-server';

  /**
   * @var string
   */
  private static $menu_title = 'Update Server';
}

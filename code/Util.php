<?php

namespace SixF\TUS;

use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\ArrayData;

class Util
{
  /**
   * Backslashes in fully qualified class names (e.g. NameSpaced\ClassName)
   * kills both requests (i.e. URIs) and XML (invalid character in a tag name)
   * So we'll replace them with a hyphen (-), as it's also unambiguious
   * in both cases (invalid in a php class name, and safe in an xml tag name)
   *
   * @param string $className
   * @return string 'escaped' class name
   */
  public static function sanitise_classname(string $className): string
  {
    return str_replace('\\', '-', $className ?? '');
  }

  /**
   * @param string $className
   * @return string
   */
  public static function unsanitise_classname(string $className): string
  {
    return str_replace('-', '\\', $className ?? '');
  }

  /**
   * @param DataList $list
   * @return array
   */
  public static function convertDataList(DataList  $list) {
    $res = [];

    foreach ($list as $obj) {
      $res[] = self::convertDataObject($obj);
    }

    return $res;
  }

  /**
   * @param DataObject $obj
   * @return array
   */
  public static function convertDataObject(DataObject  $obj) {
    $res = self::dataobject_to_array($obj);

    return $res;
  }

  /**
   * @param DataObject $obj
   * @return array
   */
  public static function dataobject_to_array(DataObject $obj) {
    $res = [];

    foreach (DataObject::getSchema()->fieldSpecs(get_class($obj)) as $fieldName => $fieldType) {
      // lower fieldname for json output
      $fn = strtolower($fieldName);

      //
      $res[$fn] = $obj->getField($fieldName);
    }

    return $res;
  }
}

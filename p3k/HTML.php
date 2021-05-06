<?php
namespace p3k;

use HTMLPurifier, HTMLPurifier_Config;

class HTML {

  public static function sanitize($html, $opts=[]) {
    $opts['allowImg'] = $opts['allowImg'] ?? true;
    $opts['allowMf2'] = $opts['allowMf2'] ?? true;
    $opts['allowTables'] = $opts['allowTables'] ?? false;
    $opts['baseURL'] = $opts['baseURL'] ?? false;

    $allowed = [
      '*[class]',
      'a[href]',
      'abbr',
      'b',
      'br',
      'code',
      'del',
      'em',
      'i',
      'q',
      'strike',
      'strong',
      'time[datetime]',
      'blockquote',
      'pre',
      'p',
      'h1',
      'h2',
      'h3',
      'h4',
      'h5',
      'h6',
      'ul',
      'li',
      'ol',
      'span',
      'sub',
      'sup',
      'caption',
      'figure',
      'figcaption',
    ];

    if ($opts['allowImg']) {
      $allowed[] = 'img[src|alt]';
    }

    if($opts['allowTables']) {
      $allowed[] = 'table';
      $allowed[] = 'thead';
      $allowed[] = 'tbody';
      $allowed[] = 'tfoot';
      $allowed[] = 'tr';
      $allowed[] = 'th[colspan|rowspan]';
      $allowed[] = 'td[colspan|rowspan]';
    }

    $config = HTMLPurifier_Config::createDefault();
    $config->set('Cache.DefinitionImpl', null);
    // $config->set('HTML.AllowedElements', $allowed);
    $config->set('HTML.Allowed', implode(',', $allowed));
    // $config->set('AutoFormat.RemoveEmpty', false); // Do not remove empty (e.g., `td`) elements.

    if($opts['baseURL']) {
      $config->set('URI.MakeAbsolute', true);
      $config->set('URI.Base', $opts['baseURL']);
    }

    if(!$opts['allowMf2']) {
      $config->set('HTML.ForbiddenAttributes', "*@class,*@style");
    } else {
      $config->set('HTML.ForbiddenAttributes', "*@style");
    }

    // Allow the datetime attribute on `<time>` elements
    $def = $config->getHTMLDefinition(true);
    $def->addElement(
      'time',
      'Inline',
      'Inline',
      'Common',
      [
        'datetime' => 'Text'
      ]
    );

    $def->addElement('figcaption', 'Block', 'Flow', 'Common');
    $def->addElement('figure', 'Block', 'Optional: (figcaption, Flow) | (Flow, figcaption) | Flow', 'Common');

    if($opts['allowMf2']) {
      // Strip all class attribute values that aren't an mf2 class
      $def->manager->attrTypes->set('Class', new HTML\HTMLPurifier_AttrDef_HTML_Microformats2());
    }

    $purifier = new HTMLPurifier($config);
    $sanitized = $purifier->purify($html);
    $sanitized = str_replace("&#xD;","\r",$sanitized);
    return trim($sanitized);
  }

}

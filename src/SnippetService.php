<?php

declare(strict_types = 1);

namespace Drupal\koop_piwik_pro;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;

/**
 * Service for creating the Piwik PRO JavaScript snippets.
 */
class SnippetService implements SnippetServiceInterface {

  /**
   * The Piwik PRO config.
   */
  private ImmutableConfig $config;

  /**
   * The dataLayer service.
   */
  private DataLayerServiceInterface $dataLayerService;

  /**
   * Constructs a SnippetService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   * @param \Drupal\koop_piwik_pro\DataLayerServiceInterface $dataLayerService
   *   The dataLayer service.
   */
  public function __construct(ConfigFactoryInterface $configFactory, DataLayerServiceInterface $dataLayerService) {
    $this->config = $configFactory->get('koop_piwik_pro.settings');
    $this->dataLayerService = $dataLayerService;
  }

  /**
   * {@inheritdoc}
   */
  public function getBodyScript(): string {
    return sprintf(
      '(function(window, document, dataLayerName, id) {window[dataLayerName]=window[dataLayerName]||[],window[dataLayerName].push({start:(new Date).getTime(),event:"stg.start"});var scripts=document.getElementsByTagName(\'script\')[0],tags=document.createElement(\'script\'); function stgCreateCookie(a,b,c){var d="";if(c){var e=new Date;e.setTime(e.getTime()+24*c*60*60*1e3),d="; expires="+e.toUTCString()}document.cookie=a+"="+b+d+"; path=/; Secure"} var isStgDebug=(window.location.href.match("stg_debug")||document.cookie.match("stg_debug"))&&!window.location.href.match("stg_disable_debug");stgCreateCookie("stg_debug",isStgDebug?1:"",isStgDebug?14:-1); var qP=[];dataLayerName!=="dataLayer"&&qP.push("data_layer_name="+dataLayerName),qP.push("use_secure_cookies"),isStgDebug&&qP.push("stg_debug");var qPString=qP.length>0?("?"+qP.join("&")):""; tags.async=!0,tags.src="%s"+id+".js"+qPString,scripts.parentNode.insertBefore(tags,scripts); !function(a,n,i){a[n]=a[n]||{};for(var c=0;c<i.length;c++)!function(i){a[n][i]=a[n][i]||{},a[n][i].api=a[n][i].api||function(){var a=[].slice.call(arguments,0);"string"==typeof a[0]&&window[dataLayerName].push({event:n+"."+i+":"+a[0],parameters:[].slice.call(arguments,1)})}}(i[c])}(window,"ppms",["tm","cm"]);})(window, document, \'%s\', \'%s\');',
      $this->config->get('domain'),
      $this->config->get('dataLayerName'),
      $this->config->get('id'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDataLayerScript(): string {
    return sprintf(
      'window.%s = window.%s || [];window.%s.push(%s);',
      $this->config->get('dataLayerName'),
      $this->config->get('dataLayerName'),
      $this->config->get('dataLayerName'),
      json_encode($this->dataLayerService->getValues()),
    );
  }

}

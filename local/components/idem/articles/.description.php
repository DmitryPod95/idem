<?
use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = array(
    'NAME' => Loc::getMessage("COMPONENT_NAME"),
    'DESCRIPTION' => Loc::getMessage("COMPONENT_DESCRIPTION"),
    'CACHE_PATH' => 'Y',
    'PATH' => array(                                     
        'ID' => 'idem',                              
        'NAME' => Loc::getMessage("COMPONENT_SECTION_NAME"),
    )
);

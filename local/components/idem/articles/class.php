<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Main\Loader;
use Bitrix\Iblock\Component\Tools;
use Bitrix\Iblock\Iblock;
use Bitrix\Main\Entity\ReferenceField;

class CIblocList extends CBitrixComponent
{

    public function executeComponent()
    {
        try {
            
            $this->checkModules();

            if(self::checkCodeApiInIblock($this->arParams['IBLOCK_ID'])) {
                $this->getResult();
            } else {
                throw new SystemException(Loc::getMessage("UNINSTALLED_API_CODE"));
            }

        } catch (SystemException $e) {
            ShowError($e->getMessage());
        }
    }

    public function onIncludeComponentLang()
    {
        Loc::loadMessages(__FILE__);
    }

    protected function checkModules()
    {
        
        if (!Loader::includeModule('iblock'))
        
            throw new SystemException(Loc::getMessage('IBLOCK_MODULE_NOT_INSTALLED'));
    }

    
    public function onPrepareComponentParams($arParams)
    {
        
        if (!isset($arParams['CACHE_TIME'])) {
            $arParams['CACHE_TIME'] = 3600;
        } else {
            $arParams['CACHE_TIME'] = intval($arParams['CACHE_TIME']);
        }

        $arParams['IBLOCK_ID'] = (int)($arParams['IBLOCK_ID'] ?? 0);
        $arParams['LIST_PROPERTY_CODE'] = is_array($arParams['LIST_PROPERTY_CODE']) ? $arParams['LIST_PROPERTY_CODE'] : [];
        $arParams['SORT_BY1'] = trim((string)($arParams['SORT_BY1'] ?? ''));
        $arParams['SORT_ORDER1'] = trim((string)($arParams['SORT_ORDER1'] ?? ''));
        $arParams['SORT_BY2'] = trim((string)($arParams['SORT_BY2'] ?? ''));
        $arParams['SORT_ORDER2'] = trim((string)($arParams['SORT_ORDER2'] ?? ''));
            
        return $arParams;
    }


    protected function getResult() {

        global $CACHE_MANAGER;

        if ($this->startResultCache($this->arParams["CACHE_TIME"])) {

            $CACHE_MANAGER->RegisterTag("iblock_id_" . $this->arParams["IBLOCK_ID"]);

            $this->arResult["INFO"] = $this->resultInfo();

            $this->IncludeComponentTemplate();

        } else { 

            $this->AbortResultCache();
                Tools::process404(
                    Loc::getMessage('PAGE_NOT_FOUND'),
                    true,
                    true
                );
        }
    }


    /**
     * Сборка массива по полученым данныи из Инфоблока
     * @return array
     */
    protected function resultInfo(): array {

        $arSections = [];
        $arElements = [];
        $arElementProperty = [];

        $resInfoSections = $this->getInfo();

        if($resInfoSections) {
            $arSections         = $resInfoSections['SECTIONIS'];
            $arElements         = $resInfoSections['ELEMENTS'];
            $arElementProperty  = $resInfoSections['PROPERTY'];
        }
        

        foreach($arElements as $idElements => &$element) {
            foreach($arElementProperty as $idProp => $property) { 
                if(strcasecmp($idElements,$idProp) == 0) {
                    $arElements[$idProp]["PROPERTY"] = $property["PROPERTY"];
                }
            }  
        }

        foreach($arSections as $idSection => &$section) {
            foreach($arElements as $elem) {
                if(strcasecmp($idSection,$elem["IBLOCK_SECTION_ID"]) == 0) {
                    $arSections[$idSection]["ELEMENTS"][] = $elem;
                }
            }   
        }
        
        return $arSections ?? [];

    }

    /**
     * Получение данных из Инфоблока
     * @return array[]
     * @throws SystemException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     */
    protected function getInfo(): array {

        $arSelect = ['ID', 'NAME', 'IBLOCK_SECTION_ID', 'SECTIONS_' => 'SECTIONS'];

        foreach($this->arParams["LIST_PROPERTY_CODE"] as $property) {
            if(!empty($property)) {
                $arSelect["PROPERTY_" . $property] = $property . ".VALUE";
            }
        }       

        $arSort = [
            $this->arParams["SORT_BY1"]=>$this->arParams["SORT_ORDER1"],
            $this->arParams["SORT_BY2"]=>$this->arParams["SORT_ORDER2"],
        ];

        $arFilter = [
            'ACTIVE' => 'Y',
        ];

        $iblockClass = Iblock::wakeUp($this->arParams['IBLOCK_ID'])->getEntityDataClass();     

        $dateElements = $iblockClass::getList([
            'select'    => $arSelect,
            'filter'    => $arFilter,
            'order'     => $arSort,
            'runtime'   => [
                new ReferenceField(
                    'SECTIONS',
                    'Bitrix\Iblock\SectionTable',
                    ['=this.IBLOCK_SECTION_ID' => 'ref.ID'],
                ),
            ],
        ]);

        $arResult = [];

        $arSections = [];
        $arElements = [];
        $arElementProperty = [];

        while($rElements = $dateElements->fetch()) {
            
            if(strcasecmp($rElements["SECTIONS_ACTIVE"],"Y") == 0) {
                $arSections[$rElements["SECTIONS_ID"]] = ['NAME' => $rElements["SECTIONS_NAME"]];
            }
            
            $arElementProperty[$rElements["ID"]]["PROPERTY"][] = $rElements["PROPERTY_TAG"];

            $arElements[$rElements["ID"]] = [
                'NAME'              => $rElements['NAME'],
                'IBLOCK_SECTION_ID' => $rElements['IBLOCK_SECTION_ID'],
            ]; 
        }

        return [
            'SECTIONIS' => $arSections,
            'ELEMENTS'  => $arElements,
            'PROPERTY'  => $arElementProperty,
        ];   
    }

    /**
     * Проверка сущестования Символьного кода API у инфоблока
     * @param $idIblock - ID Инфоблока из настроек компонента
     * @return bool
     */
    protected static function checkCodeApiInIblock($idIblock): bool {
        
        $dbResult = CIBlock::GetByID($idIblock)->Fetch()["API_CODE"];

        if(!$dbResult) {
            return false;
        }

        return true;
    }
    

}

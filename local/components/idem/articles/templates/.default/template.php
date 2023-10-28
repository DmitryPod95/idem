<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>

<?if($arResult["INFO"]):?>
    <?foreach($arResult["INFO"] as $section):?>
        <b><?=$section["NAME"];?></b>
            <?if($section["ELEMENTS"]):?>
                <ul>
                    <?foreach($section["ELEMENTS"] as $item):?>
                        <li>
                            <?=$item["NAME"];?>
                            <?if($item["PROPERTY"]):?>
                                (<?=implode(", ",$item["PROPERTY"]);?>)
                            <?endif;?>
                        </li>
                    <?endforeach;?>
                </ul>
            <?endif?>
    <?endforeach?>
<?endif?>

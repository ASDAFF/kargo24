<?php

$APPLICATION->IncludeComponent("kargo:ads.type", "ads.type", Array(
    "IBLOCK_ID" => $arParams["IBLOCK_ID"],	// ��������
    "IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],	// ��� ���������
    "SORT" => "NAME",	// ������� ���������� �����
    "TYPE" => $_REQUEST['TYPE']
),
    false
);
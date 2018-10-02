<?php
$arUrlRewrite=array (
  3 => 
  array (
    'CONDITION' => '#^/equipment-rent/#',
    'RULE' => 'SECTION_CODE=$1',
    'ID' => 'bitrix:catalog',
    'PATH' => '/equipment-rent/index.php',
    'SORT' => 100,
  ),
  2 => 
  array (
    'CONDITION' => '#^/transportation/#',
    'RULE' => 'SECTION_CODE=$1',
    'ID' => 'bitrix:catalog',
    'PATH' => '/transportation/index.php',
    'SORT' => 100,
  ),
  1 => 
  array (
    'CONDITION' => '#^/equipment/#',
    'RULE' => 'SECTION_CODE=$1',
    'ID' => 'bitrix:catalog',
    'PATH' => '/equipment/index.php',
    'SORT' => 100,
  ),
  0 => 
  array (
    'CONDITION' => '#^/rest/#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/bitrix/services/rest/index.php',
    'SORT' => 100,
  ),
  4 => 
  array (
    'CONDITION' => '#^/news/#',
    'RULE' => '',
    'ID' => 'bitrix:news',
    'PATH' => '/news/index.php',
    'SORT' => 100,
  ),
);

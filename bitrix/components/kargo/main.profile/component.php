<?
/**
 * @global CMain $APPLICATION
 * @global CUser $USER
 * @global CUserTypeManager $USER_FIELD_MANAGER
 * @var array $arParams
 * @var CBitrixComponent $this
 */
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

$this->setFrameMode(false);

global $USER_FIELD_MANAGER;

$arResult["ID"] = intval($USER->GetID());
$arResult["GROUP_POLICY"] = CUser::GetGroupPolicy($arResult["ID"]);

$arParams['SEND_INFO'] = $arParams['SEND_INFO'] == 'Y' ? 'Y' : 'N';
$arParams['CHECK_RIGHTS'] = $arParams['CHECK_RIGHTS'] == 'Y' ? 'Y' : 'N';

if(!($arParams['CHECK_RIGHTS'] == 'N' || $USER->CanDoOperation('edit_own_profile')) || $arResult["ID"]<=0)
{
	LocalRedirect("/register/");
	return;
}

$strError = '';

if($_SERVER["REQUEST_METHOD"]=="POST" && !$_REQUEST["support_edit"] && ($_REQUEST["save"] <> '' || $_REQUEST["apply"] <> '') && check_bitrix_sessid())
{
	if(COption::GetOptionString('main', 'use_encrypted_auth', 'N') == 'Y')
	{
		//possible encrypted user password
		$sec = new CRsaSecurity();
		if(($arKeys = $sec->LoadKeys()))
		{
			$sec->SetKeys($arKeys);
			$errno = $sec->AcceptFromForm(array('NEW_PASSWORD', 'NEW_PASSWORD_CONFIRM'));
			if($errno == CRsaSecurity::ERROR_SESS_CHECK)
				$strError .= GetMessage("main_profile_sess_expired").'<br />';
			elseif($errno < 0)
				$strError .= GetMessage("main_profile_decode_err", array("#ERRCODE#"=>$errno)).'<br />';
		}
	}

	if($strError == '')
	{
		$bOk = false;
		$obUser = new CUser;

		$arPERSONAL_PHOTO = $_FILES["PERSONAL_PHOTO"];
		$arWORK_LOGO = $_FILES["WORK_LOGO"];

		$rsUser = CUser::GetByID($arResult["ID"]);
		$arUser = $rsUser->Fetch();
		if($arUser)
		{
			$arPERSONAL_PHOTO["old_file"] = $arUser["PERSONAL_PHOTO"];
			$arPERSONAL_PHOTO["del"] = $_REQUEST["PERSONAL_PHOTO_del"];

			$arWORK_LOGO["old_file"] = $arUser["WORK_LOGO"];
			$arWORK_LOGO["del"] = $_REQUEST["WORK_LOGO_del"];
		}

		$arEditFields = array(
			"TITLE",
			"NAME",
			"LAST_NAME",
			"SECOND_NAME",
			"EMAIL",
			"LOGIN",
			"PERSONAL_PROFESSION",
			"PERSONAL_WWW",
			"PERSONAL_ICQ",
			"PERSONAL_GENDER",
			"PERSONAL_BIRTHDAY",
			"PERSONAL_PHONE",
			"PERSONAL_FAX",
			"PERSONAL_MOBILE",
			"PERSONAL_PAGER",
			"PERSONAL_STREET",
			"PERSONAL_MAILBOX",
			"PERSONAL_CITY",
			"PERSONAL_STATE",
			"PERSONAL_ZIP",
			"PERSONAL_COUNTRY",
			"PERSONAL_NOTES",
			"WORK_COMPANY",
			"WORK_DEPARTMENT",
			"WORK_POSITION",
			"WORK_WWW",
			"WORK_PHONE",
			"WORK_FAX",
			"WORK_PAGER",
			"WORK_STREET",
			"WORK_MAILBOX",
			"WORK_CITY",
			"WORK_STATE",
			"WORK_ZIP",
			"WORK_COUNTRY",
			"WORK_PROFILE",
			"WORK_NOTES",
			"TIME_ZONE",
		);

		$arFields = array();
		foreach($arEditFields as $field)
		{
			if(isset($_REQUEST[$field]))
			{
				$arFields[$field] = $_REQUEST[$field];
			}
		}

		if(isset($_REQUEST["AUTO_TIME_ZONE"]))
		{
			$arFields["AUTO_TIME_ZONE"] = ($_REQUEST["AUTO_TIME_ZONE"] == "Y" || $_REQUEST["AUTO_TIME_ZONE"] == "N"? $_REQUEST["AUTO_TIME_ZONE"] : "");
		}

		if($USER->IsAdmin() && isset($_REQUEST["ADMIN_NOTES"]))
		{
			$arFields["ADMIN_NOTES"] = $_REQUEST["ADMIN_NOTES"];
		}

		if($_REQUEST["NEW_PASSWORD"] <> '' && $arUser['EXTERNAL_AUTH_ID'] == '')
		{
			$arFields["PASSWORD"] = $_REQUEST["NEW_PASSWORD"];
			$arFields["CONFIRM_PASSWORD"] = $_REQUEST["NEW_PASSWORD_CONFIRM"];
		}

		$arFields["PERSONAL_PHOTO"] = $arPERSONAL_PHOTO;
		$arFields["WORK_LOGO"] = $arWORK_LOGO;

		if($arUser)
		{
			if($arUser['EXTERNAL_AUTH_ID'] <> '')
			{
				$arFields['EXTERNAL_AUTH_ID'] = $arUser['EXTERNAL_AUTH_ID'];
			}
		}

		$USER_FIELD_MANAGER->EditFormAddFields("USER", $arFields);

		if(!$obUser->Update($arResult["ID"], $arFields))
			$strError .= $obUser->LAST_ERROR;
	}

	if($strError == '')
	{
		if (CModule::IncludeModule("forum"))
		{
			$APPLICATION->ResetException();

			$arforumEditFields = array(
				"DESCRIPTION",
				"INTERESTS",
				"SIGNATURE",
			);

			$arforumFields = array();
			foreach($arforumEditFields as $field)
			{
				if(isset($_REQUEST["forum_".$field]))
				{
					$arforumFields[$field] = $_REQUEST["forum_".$field];
				}
			}

			if(isset($_REQUEST["forum_SHOW_NAME"]))
			{
				$arforumFields["SHOW_NAME"] = ($_REQUEST["forum_SHOW_NAME"] == "Y"? "Y" : "N");
			}

			$arforumFields["AVATAR"] = $_FILES["forum_AVATAR"];
			$arforumFields["AVATAR"]["del"] = $_REQUEST["forum_AVATAR_del"];

			$ar_res = CForumUser::GetByUSER_ID($arResult["ID"]);
			if ($ar_res)
			{
				$arforumFields["AVATAR"]["old_file"] = $ar_res["AVATAR"];
				$FORUM_USER_ID = intval($ar_res["ID"]);
				$FORUM_USER_ID1 = CForumUser::Update($FORUM_USER_ID, $arforumFields);
				$forum_res = (intval($FORUM_USER_ID1)>0);
			}
			else
			{
				$arforumFields["USER_ID"] = $arResult["ID"];
				$FORUM_USER_ID = CForumUser::Add($arforumFields);
				$forum_res = (intval($FORUM_USER_ID)>0);
			}

			if($ex = $APPLICATION->GetException())
				$strError = $ex->GetString();
		}
	}

	if($strError == '')
	{
		if (CModule::IncludeModule("blog"))
		{
			$APPLICATION->ResetException();

			$arblogEditFields = array(
				"ALIAS",
				"DESCRIPTION",
				"INTERESTS",
			);

			$arblogFields = array();
			foreach($arblogEditFields as $field)
			{
				if(isset($_REQUEST["blog_".$field]))
				{
					$arblogFields[$field] = $_REQUEST["blog_".$field];
				}
			}

			$arblogFields["AVATAR"] = $_FILES["blog_AVATAR"];
			$arblogFields["AVATAR"]["del"] = $_REQUEST["blog_AVATAR_del"];

			$ar_res = CBlogUser::GetByID($arResult["ID"], BLOG_BY_USER_ID);
			if ($ar_res)
			{
				$arblogFields["AVATAR"]["old_file"] = $ar_res["AVATAR"];
				$BLOG_USER_ID = intval($ar_res["ID"]);

				$BLOG_USER_ID1 = CBlogUser::Update($BLOG_USER_ID, $arblogFields);
				$blog_res = (intval($BLOG_USER_ID1)>0);
			}
			else
			{
				$arblogFields["USER_ID"] = $arResult["ID"];
				$arblogFields["~DATE_REG"] = CDatabase::CurrentTimeFunction();

				$BLOG_USER_ID = CBlogUser::Add($arblogFields);
				$blog_res = (intval($BLOG_USER_ID)>0);
			}

			if($ex = $APPLICATION->GetException())
				$strError = $ex->GetString();
		}
	}

	if($strError == '' && CModule::IncludeModule("learning"))
	{
		$arStudentFields = array();
		if(isset($_REQUEST["student_RESUME"]))
		{
			$arStudentFields["RESUME"] = $_REQUEST["student_RESUME"];
		}
		if(isset($_REQUEST["student_PUBLIC_PROFILE"]))
		{
			$arStudentFields["PUBLIC_PROFILE"] = ($_REQUEST["student_PUBLIC_PROFILE"] == "Y"? "Y" : "N");
		}

		if(!empty($arStudentFields))
		{
			$ar_res = CStudent::GetList(array(), array("USER_ID" => $arResult["ID"]));

			if ($arStudent = $ar_res->Fetch())
			{
				$learning_res = CStudent::Update($arResult["ID"], $arStudentFields);
			}
			else
			{
				$arStudentFields["USER_ID"] = $arResult["ID"];
				$STUDENT_USER_ID = CStudent::Add($arStudentFields);
				$learning_res = (intval($STUDENT_USER_ID)>0);
			}
		}
	}

	if($strError == '')
	{
		if($arParams['SEND_INFO'] == 'Y')
			$obUser->SendUserInfo($arResult["ID"], SITE_ID, GetMessage("main_profile_update"), true);

		$bOk = true;
	}
}

$rsUser = CUser::GetByID($arResult["ID"]);
if(!$arResult["arUser"] = $rsUser->GetNext(false))
{
	$arResult["ID"] = 0;
}

if (CModule::IncludeModule("blog"))
{
	$arResult["INCLUDE_BLOG"] = "Y";

	$arResult["arBlogUser"] = array();
	$arBlg = CBlogUser::GetByID($arResult["ID"], BLOG_BY_USER_ID);
	if(is_array($arBlg))
	{
		foreach($arBlg as $key => $val)
		{
			$arResult["arBlogUser"]["~".$key] = $val;
			$arResult["arBlogUser"][$key] = htmlspecialcharsbx($val);
		}
	}

	if (!isset($arResult["arBlogUser"]["ALLOW_POST"]) || ($arResult["arBlogUser"]["ALLOW_POST"]!="Y" && $arResult["arBlogUser"]["ALLOW_POST"]!="N"))
		$arResult["arBlogUser"]["ALLOW_POST"] = "Y";
}

if (CModule::IncludeModule("forum"))
{
	$arResult["INCLUDE_FORUM"] = "Y";

	$rsForumUser = CForumUser::GetList(array(), array("USER_ID" => $arResult["ID"]));
	$arResult["arForumUser"] = $rsForumUser->GetNext(false);
	if (!isset($arResult["arForumUser"]["ALLOW_POST"]) || ($arResult["arForumUser"]["ALLOW_POST"]!="Y" && $arResult["arForumUser"]["ALLOW_POST"]!="N"))
		$arResult["arForumUser"]["ALLOW_POST"] = "Y";
}

if (CModule::IncludeModule("learning"))
{
	$arResult["INCLUDE_LEARNING"] = "Y";

	$dbStudent = CStudent::GetList(array(), array("USER_ID" => $arResult["ID"]));
	$arResult["arStudent"] = $dbStudent->GetNext();
	if (!isset($arResult["arStudent"]["PUBLIC_PROFILE"]) || ($arResult["arStudent"]["PUBLIC_PROFILE"]!="Y" && $arResult["arStudent"]["PUBLIC_PROFILE"]!="N"))
		$arResult["arStudent"]["PUBLIC_PROFILE"] = "N";
}

if($strError <> '')
{
	static $skip = array("PERSONAL_PHOTO"=>1, "WORK_LOGO"=>1, "forum_AVATAR"=>1, "blog_AVATAR"=>1);
	foreach($_POST as $k => $val)
	{
		if(!isset($skip[$k]))
		{
			if(!is_array($val))
			{
				$val = htmlspecialcharsex($val);
			}
			if(strpos($k, "forum_") === 0)
			{
				$arResult["arForumUser"][substr($k, 6)] = $val;
			}
			elseif(strpos($k, "blog_") === 0)
			{
				$arResult["arBlogUser"][substr($k, 5)] = $val;
			}
			elseif(strpos($k, "student_") === 0)
			{
				$arResult["arStudent"][substr($k, 8)] = $val;
			}
			else
			{
				$arResult["arUser"][$k] = $val;
			}
		}
	}
}

$arResult["FORM_TARGET"] = $APPLICATION->GetCurPage();

$arResult["arUser"]["PERSONAL_PHOTO_INPUT"] = CFile::InputFile("PERSONAL_PHOTO", 20, $arResult["arUser"]["PERSONAL_PHOTO"], false, 0, "IMAGE");
if (strlen($arResult["arUser"]["PERSONAL_PHOTO"])>0)
	$arResult["arUser"]["PERSONAL_PHOTO_HTML"] = CFile::ShowImage($arResult["arUser"]["PERSONAL_PHOTO"], 150, 150, "border=0", "", true);

$arResult["arUser"]["WORK_LOGO_INPUT"] = CFile::InputFile("WORK_LOGO", 20, $arResult["arUser"]["WORK_LOGO"], false, 0, "IMAGE");
if (strlen($arResult["arUser"]["WORK_LOGO"])>0)
	$arResult["arUser"]["WORK_LOGO_HTML"] = CFile::ShowImage($arResult["arUser"]["WORK_LOGO"], 150, 150, "border=0", "", true);

$arResult["arForumUser"]["AVATAR_INPUT"] = CFile::InputFile("forum_AVATAR", 20, $arResult["arForumUser"]["AVATAR"], false, 0, "IMAGE");
if (strlen($arResult["arForumUser"]["AVATAR"])>0)
	$arResult["arForumUser"]["AVATAR_HTML"] = CFile::ShowImage($arResult["arForumUser"]["AVATAR"], 150, 150, "border=0", "", true);

$arResult["arBlogUser"]["AVATAR_INPUT"] = CFile::InputFile("blog_AVATAR", 20, $arResult["arBlogUser"]["AVATAR"], false, 0, "IMAGE");
if (strlen($arResult["arBlogUser"]["AVATAR"])>0)
	$arResult["arBlogUser"]["AVATAR_HTML"] = CFile::ShowImage($arResult["arBlogUser"]["AVATAR"], 150, 150, "border=0", "", true);

$arResult["IS_ADMIN"] = $USER->IsAdmin();

$arCountries = GetCountryArray();
$arResult["COUNTRY_SELECT"] = SelectBoxFromArray("PERSONAL_COUNTRY", $arCountries, $arResult["arUser"]["PERSONAL_COUNTRY"], GetMessage("USER_DONT_KNOW"));
$arResult["COUNTRY_SELECT_WORK"] = SelectBoxFromArray("WORK_COUNTRY", $arCountries, $arResult["arUser"]["WORK_COUNTRY"], GetMessage("USER_DONT_KNOW"));

$arResult["strProfileError"] = $strError;
$arResult["BX_SESSION_CHECK"] = bitrix_sessid_post();

$arResult["DATE_FORMAT"] = CLang::GetDateFormat("SHORT");

$arResult["COOKIE_PREFIX"] = COption::GetOptionString("main", "cookie_name", "BITRIX_SM");
if (strlen($arResult["COOKIE_PREFIX"]) <= 0)
	$arResult["COOKIE_PREFIX"] = "BX";

// ********************* User properties ***************************************************
$arResult["USER_PROPERTIES"] = array("SHOW" => "N");
if (!empty($arParams["USER_PROPERTY"]))
{
	$arUserFields = $USER_FIELD_MANAGER->GetUserFields("USER", $arResult["ID"], LANGUAGE_ID);
	if (count($arParams["USER_PROPERTY"]) > 0)
	{
		foreach ($arUserFields as $FIELD_NAME => $arUserField)
		{
			if (!in_array($FIELD_NAME, $arParams["USER_PROPERTY"]))
				continue;
			$arUserField["EDIT_FORM_LABEL"] = strLen($arUserField["EDIT_FORM_LABEL"]) > 0 ? $arUserField["EDIT_FORM_LABEL"] : $arUserField["FIELD_NAME"];
			$arUserField["EDIT_FORM_LABEL"] = htmlspecialcharsEx($arUserField["EDIT_FORM_LABEL"]);
			$arUserField["~EDIT_FORM_LABEL"] = $arUserField["EDIT_FORM_LABEL"];
			$arResult["USER_PROPERTIES"]["DATA"][$FIELD_NAME] = $arUserField;
		}
	}
	if (!empty($arResult["USER_PROPERTIES"]["DATA"]))
		$arResult["USER_PROPERTIES"]["SHOW"] = "Y";
	$arResult["bVarsFromForm"] = ($strError == ''? false : true);
}
// ******************** /User properties ***************************************************

if($arParams["SET_TITLE"] == "Y")
	$APPLICATION->SetTitle(GetMessage("PROFILE_DEFAULT_TITLE"));

if($bOk)
	$arResult['DATA_SAVED'] = 'Y';

//time zones
$arResult["TIME_ZONE_ENABLED"] = CTimeZone::Enabled();
if($arResult["TIME_ZONE_ENABLED"])
	$arResult["TIME_ZONE_LIST"] = CTimeZone::GetZones();

$arResult["EMAIL_REQUIRED"] = (COption::GetOptionString("main", "new_user_email_required", "Y") <> "N");


$arDefaultUrlTemplates404 = array(
	"personal" => "",
	"search" => "search/",
	"rss" => "rss/",
	"rss_section" => "#SECTION_ID#/rss/",
	"detail" => "#ELEMENT_ID#/",
	"section" => "",
);

$arVariables = array();
$arUrlTemplates = CComponentEngine::makeComponentUrlTemplates($arDefaultUrlTemplates404, array(
	"personal" => "",
	"section" => "#SECTION_CODE#/",
	"detail" => "#SECTION_CODE#/#ELEMENT_CODE#/",
));

$engine = new CComponentEngine($this);

$componentPage = $engine->guessComponentPath(
	'/personal/',
	$arUrlTemplates,
	$arVariables
);

$arResult['ELEMENT_CODE'] = $arVariables['ELEMENT_CODE'];
$arResult['SECTION_CODE'] = $arVariables['SECTION_CODE'];
if(empty($arResult['SECTION_CODE'])){
	$arResult['SECTION_CODE'] = "main";
}

//secure authorization
$arResult["SECURE_AUTH"] = false;
if(!CMain::IsHTTPS() && COption::GetOptionString('main', 'use_encrypted_auth', 'N') == 'Y')
{
	$sec = new CRsaSecurity();
	if(($arKeys = $sec->LoadKeys()))
	{
		$sec->SetKeys($arKeys);
		$sec->AddToForm('form1', array('NEW_PASSWORD', 'NEW_PASSWORD_CONFIRM'));
		$arResult["SECURE_AUTH"] = true;
	}
}

//socialservices
$arResult["SOCSERV_ENABLED"] = IsModuleInstalled("socialservices");


$arResult['IN_VOICE_ID'] = "a12fdaab-4942-4fe9-a6b0-3192332c5b24";
$arResult['LOGIN'] = "api@mail.ru";
$arResult['PASSWORD'] = "237e5aaddc582c6acfabca3d55372d23";

CModule::IncludeModule("iblock");
$arSelect = Array("ID", "IBLOCK_ID", "PROPERTY_*");
$arFilter = Array("IBLOCK_ID" => IntVal(19), "ACTIVE"=>"N" ,"MODIFIED_BY" => $USER->GetID());
$res = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
while($ob = $res->GetNextElement()){
	$arFields = $ob->GetFields();
	$arProps = $ob->GetProperties();
	$pay_number = $arProps[PAY_NUMBER][VALUE];
	$nonce = $arFields[ID].time();
	$arHash = array($arResult['LOGIN'],$arResult['PASSWORD'],$nonce,$pay_number,$arResult['IN_VOICE_ID']);
	$arResult['HASH'] = base64_encode(sha1(implode(";",$arHash), true));
	$paymaster = file_get_contents("https://paymaster.ru/partners/rest/getPaymentByInvoiceID?login=$arResult[LOGIN]&nonce=$nonce&hash=$arResult[HASH]&invoiceid=$pay_number&siteAlias=$arResult[IN_VOICE_ID]");
	$arPay = json_decode($paymaster);
	if(!$arPay->ErrorCode && $arPay->Payment->State == "COMPLETE"){

		$el = new CIBlockElement;
		$PROP = array();
		$PROP["PRICE"] = $arPay->Payment->PaymentAmount;
		$PROP["PAY_NUMBER"] = $pay_number;
		$arLoadProductArray = Array(
			"MODIFIED_BY"    => $USER->GetID(),
			"IBLOCK_SECTION" => false,
			"PROPERTY_VALUES"=> $PROP,
			"ACTIVE"         => "Y",
		);
		$el->Update($arFields['ID'], $arLoadProductArray);

		$user = new CUser;
		$rsUser = CUser::GetByID($USER->GetID());
		$arUser = $rsUser->Fetch();
		$arUser["UF_BALANCE"] += $PROP["PRICE"];
		$fields = Array(
			"UF_BALANCE" => $arUser["UF_BALANCE"],
		);
		$user->Update($USER->GetID(), $fields);

	}else{
		//CIBlockElement::Delete($arFields[ID]);
	}
}
$rsUser = CUser::GetByID($USER->GetID());
$arUser = $rsUser->Fetch();
$arResult["BALANCE"] = $arUser["UF_BALANCE"];

CModule::IncludeModule('support');
$by = "s_id";    // ����������� ���������� ����������,
$order = "asc"; // �.�. ��������� � ���������� �������� �� �����

$arFilter = array(
	"CLOSE" => "N", // ���������� ���������
);
$rs = CTicket::GetList($by, $order, $arFilter,$isFiltered);
while($ar = $rs->GetNext())
{
	if($ar)
		$arResult['TICKET'][$ar[ID]] = $ar;
	else
		$arResult['TICKET'] = false;
}

$this->IncludeComponentTemplate();

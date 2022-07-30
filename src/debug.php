<?php declare(strict_types=1);
use mrcore\services\WebEnvService;

/**
 * Включение режима разработчика.
 *
 * Для того чтобы включить debug режим на сайте необходимо:
 *   в адресной строке браузера указать: https://<domain-name>/debug/?_MRSID=<MRSID>
 *   где <MRSID> - имя файла состоящее из 64-х символов и цифр расположенного в директории $_ENV['MRAPP_DIR_DEVELOPERS']
 *   Формат файла <MRSID>: <developer-name:a-z+> <developer-email>
 *
 * @author  Andrey J. Nazarov
 * @uses       $_ENV['MRAPP_DIR_DEVELOPERS']
 * @uses       $_ENV['MRAPP_DIR_TEMPLATES']
 */

// первоначальная установка кодировки по умолчанию
header('Content-Type: text/html; charset=utf-8');

$developerName = '';
$developerEmail = '';

$envService = new WebEnvService();
$varService = $envService->varService;

if ('' !== ($mrsid = $varService->get('_MRSID')))
{
    $_COOKIE['_DBG_DEVELOPER'] = $mrsid;
}

if ('' !== ($developerToken = $varService->getCookie('_DBG_DEVELOPER')) &&
    preg_match('/^[0-9a-z]{64}$/', $developerToken) > 0 &&
    is_file($_ENV['MRAPP_DIR_DEVELOPERS'] . $developerToken))
{
    // [name, email]
    $developer = explode(' ', file_get_contents($_ENV['MRAPP_DIR_DEVELOPERS'] . $developerToken), 2);

    if (!empty($developer[0]) && !empty($developer[1]))
    {
        // установка cookies разработчика
        debugSetCookie('_DBG_DEVELOPER', $developerToken);

        $developerName = $developer[0];
        $developerEmail = $developer[1];
    }
}

##################################################################################

if ('' === $developerName)
{
    header('HTTP/1.0 404 Not Found');
    include($_ENV['MRAPP_DIR_TEMPLATES'] . 'shared/system/error.http.html.tpl.php');
    exit;
}

##################################################################################

$params = [];

foreach (['dbgMode'        => '_DBG_MODE', // on, off
          'dbgGroups'      => '_DBG_GROUPS', // comma-separated
          'dbgLevel'       => '_DBG_LEVEL', // int
          'dbgData'        => '_DBG_DATA_RAW', // on, off
          'dbgSQL'         => '_DBG_SQL', // comma-separated
          'dbgEmail'       => '_DBG_EMAIL', // on, off
          'dbgPagePreview' => '_DBG_PAGE_PREVIEW', // on, off
          'dbgCaptcha'     => '_DBG_CAPTCHA', // on, off
          'dbgCacheReset'  => '_DBG_CACHE_RESET' // on, off
          ] as $varName => $cookieName)
{
    if ('' === ($value = $varService->get($varName)))
    {
        $value = $varService->getCookie($cookieName);
    }

    $params[$varName] = $value;

    debugSetCookie($cookieName, $value);
}

##################################################################################

echo sprintf('<h1>Developer mode is ON for user %s</h1>', $developerName);

echo '<form method="POST" style="border: 1px solid #000000; padding: 5px;">
        <div>&amp;_DBG_MODE - отладочный режим: <input type="radio" name="dbgMode" value="1"' . ($params['dbgMode'] > 0 ? ' checked="checked"' : '') . ' />&nbsp;On&nbsp;&nbsp;<input type="radio" name="dbgMode" value="0"' . ($params['dbgMode'] > 0 ? '' : ' checked="checked"') . ' />&nbsp;Off</div>
        <div>&amp;_DBG_GROUPS - список групп, для которых разрешено отображать отладочную информацию: <input style="width: 300px;" type="text" name="dbgGroups" value="' . ('' === $params['dbgGroups'] ? '' : htmlspecialchars($params['dbgGroups'])) . '" maxlength="64" /> (all, ...)</div>
        <div>&amp;_DBG_LEVEL - текущий уровень отображения отладочной информации: <input style="width: 25px;" type="text" name="dbgLevel" value="' . ('' === $params['dbgLevel'] ? '' : (int)$params['dbgLevel']) . '" maxlength="1" /> (L_DBG: 0, L_FULL: 1, L_INFO: 2, L_HEAD: 3)</div>
        <div>&amp;_DBG_DATA_RAW - отображение данных передаваемых в шаблонизатор (Array, XML): <input type="radio" name="dbgData" value="1"' . ($params['dbgData'] > 0 ? ' checked="checked"' : '') . ' />&nbsp;On&nbsp;&nbsp;<input type="radio" name="dbgData" value="0"' . ($params['dbgData'] > 0 ? '' : ' checked="checked"') . ' />&nbsp;Off</div>
        <div>&amp;_DBG_SQL - расширенная информация о запросах: <input style="width: 300px;" type="text" name="dbgSQL" value="' . ('' === $params['dbgSQL'] ? '' : htmlspecialchars($params['dbgSQL'])) . '" maxlength="64" /> (ALL or CONNECT, QUERY, INSERT, UPDATE, DELETE, SELECT)</div>
        <div>&amp;_DBG_EMAIL - отправлять на <b>' . htmlspecialchars($developerEmail) . '</b> все письма: <input type="radio" name="dbgEmail" value="1"' . ($params['dbgEmail'] > 0 ? ' checked="checked"' : '') . ' />&nbsp;On&nbsp;&nbsp;<input type="radio" name="dbgEmail" value="0"' . ($params['dbgEmail'] > 0 ? '' : ' checked="checked"') . ' />&nbsp;Off</div>
        <!-- <div>&amp;_DBG_PAGE_PREVIEW - отображение страницы в режиме предварительного посмотра: <input type="radio" name="dbgPagePreview" value="1"' . ($params['dbgPagePreview'] > 0 ? ' checked="checked"' : '') . ' />&nbsp;On&nbsp;&nbsp;<input type="radio" name="dbgPagePreview" value="0"' . ($params['dbgPagePreview'] > 0 ? '' : ' checked="checked"') . ' />&nbsp;Off</div> -->
        <!-- <div>&amp;_DBG_CAPTCHA - игнорирование проверки правильного заполнения капчи: <input type="radio" name="dbgCaptcha" value="1"' . ($params['dbgCaptcha'] > 0 ? ' checked="checked"' : '') . ' />&nbsp;On&nbsp;&nbsp;<input type="radio" name="dbgCaptcha" value="0"' . ($params['dbgCaptcha'] > 0 ? '' : ' checked="checked"') . ' />&nbsp;Off</div> -->
        <!-- <div>&amp;_DBG_CACHE_RESET - в отладочном режиме сбрасывается кэш (при этом _DBG_PAGE_PREVIEW и _DBG_DATA_RAW отключаются): <input type="radio" name="dbgCacheReset" value="1"' . ($params['dbgCacheReset'] > 0 ? ' checked="checked"' : '') . ' />&nbsp;On&nbsp;&nbsp;<input type="radio" name="dbgCacheReset" value="0"' . ($params['dbgCacheReset'] > 0 ? '' : ' checked="checked"') . ' />&nbsp;Off</div> -->
        <div style="padding: 5px 15px;"><input type="submit" value="Apply" /></div>
      </form>';

phpinfo();

##################################################################################

function debugSetCookie(string $name, string $value)
{
    $options = array
    (
        'expires' => ('' === $value ? 315554400/* strtotime('1980-01-01') */ : time() + 7200/*2 часа*/),
        'path' => '/',
        'domain' => '',
        'secure' => false, // :TODO:
        'httponly' => true,
        'samesite' => 'Strict' // None || Lax  || Strict
    );

    setcookie($name, $value, $options);
    $_COOKIE[$name] = $value;
}
<?php declare(strict_types=1);
namespace mrcore\debug;

/**
 * Класс форматирует информацию об ошибке и связанную с ней
 * отладочную информацию виде HTML блока.
 *
 * @author  Andrey J. Nazarov
 */
class HtmlDebuggingData extends AbstractDebuggingData
{

    public function __construct(private HelperCodeFragment $codeFragment) { }

    /**
     * Приём информации об ошибке и форматирование её в виде HTML блока.
     * @inheritdoc
     */
    public function perform(int $errno, string $errstr, string $errfile, int $errline, array $backTrace): void
    {
        [$errstr, $extendedInfo] = $this->_parseError($errstr);
        $errorMessage = sprintf('%s: <i>%s</i> in <i>%s</i> on line <i>%s</i>' . PHP_EOL, $this->_getTypeError($errno), htmlspecialchars($errstr), $errfile, $errline);

        $debuggingInfo = '<font size="1"><table border="1" cellspacing="0">' . PHP_EOL .
                         '<tr><th style="background-color: #ee5555" colspan="3">' . nl2br($errorMessage) . '</th></tr>' . PHP_EOL;

        if ('' !== $extendedInfo)
        {
            $debuggingInfo .= '<tr><td style="background-color: #eeee55" colspan="3">' . nl2br(str_replace(' ', '&nbsp;', htmlspecialchars($extendedInfo, ENT_QUOTES))) . '</td></tr>' . PHP_EOL;
        }

        if ('' !== ($codeFragment = $this->codeFragment->getInfo($errfile, $errline)))
        {
            $debuggingInfo .= '<tr><td style="background-color: #55eeee" colspan="3">' . nl2br(str_replace(' ', '&nbsp;', htmlspecialchars($codeFragment, ENT_QUOTES))) . '</td></tr>' . PHP_EOL;
        }

        $debuggingInfo .= '<tr><th style="background-color: #7777dd" colspan="3">Call Stack</th></tr>' . PHP_EOL .
                          '<tr><th style="background-color: #9999ee">#</th><th style="background-color: #9999ee">Function</th><th style="background-color: #9999ee">Location</th></tr>' . PHP_EOL;

        ##################################################################################

        for ($i = 0, $cnt = count($backTrace); $i < $cnt; $i++)
        {
            $item = $backTrace[$i];

            $cellFunction = ($item['class'] ?? '') .
                            ($item['type'] ?? '') .
                            (isset($item['function']) ? $item['function'] . '(<span style="color:#000099">' . (isset($item['args']) ? htmlspecialchars(Tools::args2str($item['args']), ENT_QUOTES) : '') . '</span>)' : '');

            $cellLocation = (isset($item['file']) ? $item['file'] . '<b>:</b>' : '') .
                            ($item['line'] ?? '');

            $debuggingInfo .= '<tr><td style="background-color: #ddddff" align="center">' . ($cnt - $i - 1) . '</td>' . PHP_EOL .
                              '<td style="background-color: #ddddff">' . $cellFunction . '</td>' . PHP_EOL .
                              '<td style="background-color: #ddddff">' . $cellLocation . '</td></tr>' . PHP_EOL;

            if (isset($item['file'], $item['line']) && '' !== ($codeFragment = $this->codeFragment->getInfo($item['file'], $item['line'])))
            {
                $debuggingInfo .= '<tr><td style="background-color: #55eeee" colspan="3">' . nl2br(str_replace(' ', '&nbsp;', htmlspecialchars($codeFragment, ENT_QUOTES))) . '</td></tr>' . PHP_EOL;
            }
        }

        $debuggingInfo .= '</table></font>' . PHP_EOL;

        ##################################################################################

        //// формирование блока отображения дампа суперглобальных переменных
        ///*__debug_info__*/ static $_xdds = null;
        ///*__debug_info__*/
        ///*__debug_info__*/ if (null === $_xdds)
        ///*__debug_info__*/ {
        ///*__debug_info__*/     $_xdds = function_exists('xdebug_dump_superglobals');
        ///*__debug_info__*/ }
        ///*__debug_info__*/
        ///*__debug_info__*/ if ($_xdds)
        ///*__debug_info__*/ {
        ///*__debug_info__*/     // отображение дампа откладывается
        ///*__debug_info__*/     ob_start();
        ///*__debug_info__*/     xdebug_dump_superglobals();
        ///*__debug_info__*/     $debuggingInfo .= ob_get_contents();
        ///*__debug_info__*/     ob_end_clean();
        ///*__debug_info__*/
        ///*__debug_info__*/     $_xdds = !ini_get('xdebug.dump_once');
        ///*__debug_info__*/ }

        echo $debuggingInfo;
    }

}
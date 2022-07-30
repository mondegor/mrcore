<?php declare(strict_types=1);
namespace mrcore\services;
use mrcore\web\PathToAction;
use mrcore\base\TraitSingleton;

// :TODO: перенести 3-х символьное представление в 2-х символьное

/**
 * Сервис .
 *
 * @author  Andrey J. Nazarov
 *
 * @template  T_MRAPP_LANGUAGES
 */
class WebLangService implements ServiceInterface
{
    use TraitSingleton;

    /**
     * Поддерживаемые языки в рамках данного сервиса.
     *
     * @var  T_MRAPP_LANGUAGES
     */
    protected array $curLanguages = [];

    #################################### Methods #####################################

    /**
     * @param  string $lang // текущий язык сервиса
     * @param  T_MRAPP_LANGUAGES  $sysLanguages // все языки системы
     */
    public function __construct(private string $lang, private array $sysLanguages)
    {
        assert(isset($sysLanguages[$lang]));

        $this->_initSingleton();

        // по умолчанию сервисом поддерживаются все системные языки
        $this->curLanguages = $this->sysLanguages;
    }

    /**
     * Инициализация текущего языка, который мог поступить из запроса,
     * а также назначение поддерживаемых языков сервисом.
     *
     * @param  string[]  $supportedLanguages
     */
    function init(PathToAction $pathToActon, array $supportedLanguages): void
    {
        // если массив пустой, то считается, что все системные языки поддерживаются
        if (empty($supportedLanguages))
        {
            $this->curLanguages = $this->sysLanguages;
        }
        else
        {
            $this->curLanguages = [];

            foreach ($supportedLanguages as $name)
            {
                if (isset($this->sysLanguages[$name]))
                {
                    $result[$name] = $this->sysLanguages[$name];
                }
            }

            assert(!empty($this->curLanguages));
        }

        $this->lang = $pathToActon->fetchItem(array_keys($this->curLanguages));
    }

    /**
     * Возвращается текущий язык сервиса.
     */
    function getLanguage(): string
    {
        return $this->lang;
    }

    /**
     * Возвращается полное название текущего языка сервиса.
     */
    function getLangName(): string
    {
        return $this->curLanguages[$this->lang][1]; // 1 - название языка (English)
    }

    /**
     * Возвращается 2-х символьная форма названия текущего языка сервиса.
     */
    public function getLang2chars(): string
    {
        return $this->curLanguages[$this->lang][0]; // 0 - 2-х символьное сокращение
    }

    /**
     * Возвращается название текущего языка сервиса в стандарте Open Graf.
     */
    public function getLocale()
    {
        return $this->curLanguages[$this->lang][2]; // 2 - Open Graf Standard
    }

    /**
     * Возвращается кодировка текущего языка сервиса.
     */
    public function getCharset(): string
    {
        return $this->curLanguages[$this->lang][3] ?? 'UTF-8'; // 3 - charset
    }

    /**
     * Возвращается список полных названий поддерживаемых языком.
     *
     * @array  array<string, string>
     */
    function getLanguages(): array
    {
        return array_map(static fn ($value) => $value[1], $this->curLanguages);
    }

}
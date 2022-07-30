<?php declare(strict_types=1);
namespace mrcore\validators;

/*
    use mrcore\validators\Phone;

    'phone' => ['class' => Phone::class]

    // OR

    use mrcore\validators\Phone;

    ...

    'phone' => [
        'class' => Phone::class,
        'attrs' => [
            // 'multy' => false,
            // 'separator' => ',',
        ],
        'errors' => [
            Phone::INVALID_VALUE => __targs('Указанное значение не является номером телефона'),
            // Phone::INVALID_VALUES => __targs('Указанное значение должно содержать номера телефонов разделённые знаком "%s"', 'separator'),
        ],
    ],
*/

/**
 * Валидатор телефонных номеров.
 *
 * @author  Andrey J. Nazarov
 */
class Phone extends StringItems
{
    /**
     * @inheritdoc
     */
    public function __construct(array $attrs = [], array $errors = [])
    {
        $this->errors[self::INVALID_VALUE] = __targs('Указанное значение не является номером телефона');
        $this->errors[self::INVALID_VALUES] = __targs('Указанное значение должно содержать номера телефонов разделённые знаком "%s"', 'separator');

        parent::__construct($attrs, $errors);
    }

    /**
     * @inheritdoc
     */
    protected function _validateItem(string $item): bool
    {
        $item = trim(preg_replace('/[\t ()+-]+/i', '', $item));
        $length = strlen($item);

        // телефон не должен быть короче 8 символов и длинее 16 символов
        if (ctype_digit($item) && $length > 7 && $length < 17)
        {
            if (preg_match('/' . "^\\+?([87](?!95[5-79]|99[08]|907|94[^0]|336)([348]\\d|9[0-6789]|7[01247])\\d{8}|[1246]\\d{9,13}|68\\d{7}|5[1-46-9]\\d{8,12}|55[1-9]\\d{9}|55[138]\\d{10}|55[1256][14679]9\\d{8}|554399\\d{7}|500[56]\\d{4}|5016\\d{6}|5068\\d{7}|502[45]\\d{7}|5037\\d{7}|50[4567]\\d{8}|50855\\d{4}|509[34]\\d{7}|376\\d{6}|855\\d{8,9}|856\\d{10}|85[0-4789]\\d{8,10}|8[68]\\d{10,11}|8[14]\\d{10}|82\\d{9,10}|852\\d{8}|90\\d{10}|96(0[79]|17[0189]|181|13)\\d{6}|96[23]\\d{9}|964\\d{10}|96(5[569]|89)\\d{7}|96(65|77)\\d{8}|92[023]\\d{9}|91[1879]\\d{9}|9[34]7\\d{8}|959\\d{7,9}|989\\d{9}|971\\d{8,9}|97[02-9]\\d{7,11}|99[^4568]\\d{7, 11}|994\\d{9}|9955\\d{8}|996[2579]\\d{8}|9989\\d{8}|380[345679]\\d{8}|381\\d{9}|38[57]\\d{8,9}|375[234]\\d{8}|372\\d{7,8}|37[0-4]\\d{8}|37[6-9]\\d{7,11}|30[69]\\d{9}|34[67]\\d{8}|3459\\d{11}|3[12359]\\d{8,12}|36\\d{9}|38[169]\\d{8}|382\\d{8,9}|46719\\d{10})$" . '/', $item) > 0)
            {
                return true;
            }
        }

        return false;
    }

}
<?php declare(strict_types=1);
namespace mrcore\units;
use mrcore\http\ResponseInterface;

/**
 * Выбираются из пути параметры и устанавливаются в виде переменных,
 * названия которых должны быть переданы в контексте экшена.
 *
 * @author  Andrey J. Nazarov
 */
class ParsePathAction extends AbstractAction
{
    /**
     * @inheritdoc
     */
    public function run(): mixed
    {
        assert(!empty($this->context['requestNames']));

        $request = $this->serviceBag->getRequest();
        $pathToAction = $this->serviceBag->getPath();

        foreach ($this->context['requestNames'] as $name)
        {
            if (!isset($pathToAction->residuePath[0]))
            {
                throw $this->createNotFoundException();
            }

            $value = array_shift($pathToAction->residuePath);

            $pathToAction->rewritePath[] = $value;
            $pathToAction->rewriteFullPath[] = $value;

            $request->set($name, $value);
        }

        return null;
    }

}
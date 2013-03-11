<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 1.12.12
 * Time: 18:58
 * To change this template use File | Settings | File Templates.
 */


namespace SRS\Form\CMS;

use Nette\Application\UI,
    Nette\Diagnostics\Debugger,
    Nette\Application\UI\Form,
    Nette\ComponentModel\IContainer;

/**
 *
 */
class HeaderFooterForm extends Form
{

    protected $dbsettings;

    public function __construct(IContainer $parent = NULL, $name = NULL, $dbsettings)
    {
        $this->dbsettings = $dbsettings;
        parent::__construct($parent, $name);
        $this->addUpload('logo', 'Logo:')
            ->addCondition(\Nette\Application\UI\Form::FILLED)
            ->addRule(\Nette\Application\UI\Form::IMAGE, 'Obrázek musí být JPEG, PNG nebo GIF.');
        $this->addText('footer', 'Patička:')->setDefaultValue($this->dbsettings->get('footer'));
        $this->addSubmit('submit','Uložit')->getControlPrototype()->class('btn');
        $this->onSuccess[] = callback($this, 'formSubmitted');
    }

    public function formSubmitted()
    {
        $values = $this->getValues();
        $image = $values['logo'];
        if ($image->size > 0) {
            $imagePath = '/files/logo/'. \Nette\Utils\Strings::random(5) . '_' . \Nette\Utils\Strings::webalize($image->getName(), '.');
            $image->move( WWW_DIR . $imagePath);
            $this->dbsettings->set('logo', $imagePath);
        }
        else {
          //do nothing
        }

        $this->dbsettings->set('footer', $values['footer']);
        $this->presenter->flashMessage('Úspěšně uloženo', 'success');
        $this->presenter->redirect('this');
    }

}
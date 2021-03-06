<?php

namespace App\Commands\Fixtures;

use App\Model\ACL\Role;
use App\Model\CMS\Content\Content;
use App\Model\CMS\Content\TextContent;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use App\Model\CMS\Page;
use Kdyby\Translation\Translator;

class CMSFixture extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var Translator
     */
    protected $translator;

    /**
     * RoleFixture constructor.
     * @param Translator $translator
     */
    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    public function load(ObjectManager $manager)
    {
        $homepage = new Page($this->translator->translate('common.cms.default.homepage_name'), '/');
        $homepage->setPosition(1);
        $homepage->setPublic(true);

        foreach (Role::$roles as $role) {
            $homepage->addRole($this->getReference($role));
        }

        $manager->persist($homepage);
        $this->addReference('homepage', $homepage);

        $textContent = new TextContent($this->getReference('homepage'), Content::MAIN);
        $textContent->setPosition(1);
        $textContent->setHeading($this->translator->translate('common.cms.default.homepage_heading'));
        $textContent->setText($this->translator->translate('common.cms.default.homepage_text'));

        $manager->persist($textContent);
        $manager->flush();
    }

    /**
     * @return array
     */
    function getDependencies()
    {
        return array('App\Commands\Fixtures\RoleFixture');
    }
}
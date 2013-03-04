<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 4.3.13
 * Time: 21:45
 * To change this template use File | Settings | File Templates.
 */
namespace SRS\Test;
require_once 'BaseSeleniumTest.php';


class AdministrationTest extends BaseSeleniumTest
{
    /**
    * @group smoke
    */
    public function testAllModules()
    {
        $this->login();
        $this->click("link=Administrace");
        $this->waitForPageToLoad(self::WAIT);
        $this->click("link=Práva a Role");
        $this->waitForPageToLoad(self::WAIT);
        $this->click("link=CMS");
        $this->waitForPageToLoad(self::WAIT);
        $this->click("link=Program semináře");
        $this->waitForPageToLoad(self::WAIT);
        $this->click("link=Konfigurace");
        $this->waitForPageToLoad(self::WAIT);
        $this->click("link=Evidence účastníků");
        $this->waitForPageToLoad(self::WAIT);

    }





}

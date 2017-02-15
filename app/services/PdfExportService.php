<?php

namespace App\Services;


use App\Model\Enums\PaymentType;
use App\Model\Settings\SettingsRepository;
use App\Model\User\User;
use fpdi\FPDI;
use Nette;

class PdfExportService extends Nette\Object
{
    private $dir;

    /** @var \fpdi\FPDI */
    private $fpdi;

    private $template;

    /** @var SettingsRepository */
    private $settingsRepository;

    /**
     * PrintService constructor.
     * @param $dir
     */
    public function __construct($dir, SettingsRepository $settingsRepository)
    {
        $this->dir = $dir;

        $this->settingsRepository = $settingsRepository;

        $this->fpdi = new FPDI();
        $this->fpdi->fontpath = $dir . '/fonts/';
        $this->fpdi->AddFont('verdana', '', 'verdana.php');
        $this->fpdi->SetFont('verdana', '', 10);
    }

    public function generatePaymentProof(User $user) {
        $this->generatePaymentProofs([$user]);
    }

    public function generatePaymentProofs($users) {
        foreach ($users as $user) {
            if ($user->getPaymentMethod() == PaymentType::BANK)
                $this->addAccountProofPage($user);
            else if ($user->getPaymentMethod() == PaymentType::CASH)
                $this->addIncomeProofPage($user);
        }
        $this->fpdi->Output('doklady-o-zaplaceni.pdf', 'D');
        exit;
    }

    private function addIncomeProofPage(User $user)
    {
        $this->configureForIncomeProof();

        $this->fpdi->addPage();

        $this->fpdi->useTemplate($this->template, 0, 0);

        $this->fpdi->SetY(49);
        $this->fpdi->SetX(37);

        $this->fpdi->Line(135, 54, 175, 54);
        $this->fpdi->Line(135, 64, 175, 64);

        $this->fpdi->Text(133, 41, iconv('UTF-8', 'WINDOWS-1250', $user->getPaymentDate()->format("j. n. Y")));

        $this->fpdi->MultiCell(68, 4.5, iconv('UTF-8', 'WINDOWS-1250', $this->settingsRepository->getValue('company')));
        $this->fpdi->Text(35, 71, iconv('UTF-8', 'WINDOWS-1250', $this->settingsRepository->getValue('ico')));
        $this->fpdi->Text(35, 77, iconv('UTF-8', 'WINDOWS-1250', '---------------')); //DIC
        $this->fpdi->Text(140, 76, iconv('UTF-8', 'WINDOWS-1250', '== ' . $user->getFee() . ' =='));
        $this->fpdi->Text(38, 86, iconv('UTF-8', 'WINDOWS-1250', '== ' . $user->getFeeWords() . ' =='));

        $this->fpdi->Text(40, 98, iconv('UTF-8', 'WINDOWS-1250',
            "{$user->getFirstName()} {$user->getLastName()}, {$user->getStreet()}, {$user->getCity()}, {$user->getPostcode()}"));

        $this->fpdi->Text(40, 111, iconv('UTF-8', 'WINDOWS-1250', "účastnický poplatek {$this->settingsRepository->getValue('seminar_name')}"));
    }

    private function addAccountProofPage(User $user)
    {
        $this->configureForAccountProof();

        $this->fpdi->addPage();
        $this->fpdi->useTemplate($this->template, 0, 0);
        $this->fpdi->SetY(30);
        $this->fpdi->SetX(25);
        $this->fpdi->MultiCell(68, 4.5, iconv('UTF-8', 'WINDOWS-1250', $this->settingsRepository->getValue('company')));
        $this->fpdi->Text(26, 52, iconv('UTF-8', 'WINDOWS-1250', 'IČO: ' . $this->settingsRepository->getValue('ico')));

        $this->fpdi->Text(70, 71, iconv('UTF-8', 'WINDOWS-1250', $this->settingsRepository->getValue('account_number')));

        $this->fpdi->Text(70, 78, iconv('UTF-8', 'WINDOWS-1250', $user->getFee() . ' Kč, slovy =' . $user->getFeeWords() . '='));
        $this->fpdi->Text(70, 85, iconv('UTF-8', 'WINDOWS-1250', 'účastnický poplatek ' . $this->settingsRepository->getValue('seminar_name')));
        $this->fpdi->Text(70, 92, iconv('UTF-8', 'WINDOWS-1250', "{$user->getFirstName()} {$user->getLastName()}"));
        $this->fpdi->Text(70, 99, iconv('UTF-8', 'WINDOWS-1250', "{$user->getStreet()}, {$user->getCity()}, {$user->getPostcode()}"));

        $this->fpdi->Text(31, 111, iconv('UTF-8', 'WINDOWS-1250', "{$this->settingsRepository->getValue('print_location')}"));
        $this->fpdi->Text(75, 111, iconv('UTF-8', 'WINDOWS-1250', "{$this->writeToday()}"));

        $this->fpdi->Text(130, 119, iconv('UTF-8', 'WINDOWS-1250', "{$this->settingsRepository->getValue('accountant')}"));
    }

    private function configureForIncomeProof()
    {
        $pagecount = $this->fpdi->setSourceFile($this->dir . '/prijmovy-pokladni-doklad.pdf');
        $template = $this->fpdi->importPage(1, '/MediaBox');
        $this->template = $template;
    }


    private function configureForAccountProof()
    {
        $pagecount = $this->fpdi->setSourceFile($this->dir . '/potvrzeni-o-prijeti-platby.pdf');
        $template = $this->fpdi->importPage(1, '/MediaBox');
        $this->template = $template;
    }

    private function writeToday()
    {
        $today = new \DateTime('now');
        return $today->format("j. n. Y");
    }
}
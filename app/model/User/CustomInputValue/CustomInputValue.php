<?php

namespace App\Model\User\CustomInputValue;

use App\Model\User\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="custom_input_value")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({
 *     "custom_checkbox_value" = "CustomCheckboxValue",
 *     "custom_text_value" = "CustomTextValue",
 * })
 */
abstract class CustomInputValue
{
    use \Kdyby\Doctrine\Entities\Attributes\Identifier;

    /**
     * @ORM\ManyToOne(targetEntity="\App\Model\Settings\CustomInput\CustomInput", cascade={"persist"})
     * @var CustomInput
     */
    protected $input;

    /**
     * @ORM\ManyToOne(targetEntity="\App\Model\User\User", inversedBy="customInputValues", cascade={"persist"})
     * @var User
     */
    protected $user;
}
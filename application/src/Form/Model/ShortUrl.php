<?php

namespace App\Form\Model;

use App\Validator\ShortCode;
use Symfony\Component\Validator\Constraints as Assert;

class ShortUrl
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Url(requireTld: true)]
        public ?string $url = null,

        #[ShortCode()]
        public ?string $shortCode = null,
    ) {
    }
}

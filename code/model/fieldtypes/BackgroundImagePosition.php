<?php



class BackgroundImagePosition extends Enum
{
    public function __construct($name = null)
    {
        return parent::__construct(
            $name,
            $enum =
'center,
top center,
top right,
right center,
bottom right,
bottom center,
bottom left,
left center,
top left',
            $default = 'center'
        );
    }

    public function AsClass()
    {
        return 'background-position-'.str_replace(' ', '-', $this->value);
    }
}

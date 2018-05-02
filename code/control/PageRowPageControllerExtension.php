<?php


class PageRowPageControllerExtension extends Extension
{

    #########################
    # Values for templates
    #########################

    public function PageRowsReadyForPublication()
    {
        return $this->owner->getPageRows()->filter(['ReadyForPublication' => 1]);
    }
}

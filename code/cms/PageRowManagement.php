<?php

class PageRowManagement extends ModelAdmin
{
    private static $managed_models = [
        'PageRow'
    ];

    private static $menu_priority = 1;

    private static $url_segment = "pagerowmanagement";

    private static $menu_title = "Content Blocks";

    private static $menu_icon = 'mysite/images/treeicons/PageRowManagement.png';

    public $showImportForm = false;



}

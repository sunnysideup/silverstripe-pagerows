<?php

class PageRowPageExtension extends SiteTreeExtension
{


    private static $many_many = [
        'PageRows' => 'PageRow'
    ];

    private static $many_many_extraFields = [
        'PageRows' => [
            'SortOrder' => 'Int'
        ]
    ];

    public function PageRows()
    {
        return $this->getPageRows();
    }

    public function getPageRows()
    {
        return $this->owner->getManyManyComponents('PageRows')->sort(['SortOrder' => 'ASC']);
    }

    #######################
    ### Further DB Field Details
    #######################

    #######################
    ### Field Names and Presentation Section
    #######################

    private static $field_labels = [
        'PageRows' => 'Content Blocks'
    ];

    private static $field_labels_right = [
        'PageRows' => 'Please edit with care! You can add content blocks that are not ready for publication, but they will not be visible until they are ticked as available.',
    ];


    #######################
    ### Casting Section
    #######################


    #######################
    ### can Section
    #######################



    #######################
    ### write Section
    #######################



    public function onAfterWrite()
    {
        if (Security::database_is_ready()  && $this->owner->HasPageRows()) {
            // debug::Log('-------------------------');
            $currentPageRows = [];
            if ($this->owner->PageRows()->count() > 0) {
                $sortOrder = 1;
                foreach ($this->owner->PageRows() as $pageRow) {
                    $currentPageRows[$sortOrder] = $pageRow;
                    $sortOrder++;
                }
            }
            $rowClassNames = $this->owner->DefaultPageRows();
            $sortOrder = 1;
            foreach ($rowClassNames as $className) {
                $childClassName = null;
                if (is_array($className)) {
                    $childClassName = $className['Child'];
                    // debug::log($childClassName);
                    $className = $className['Parent'];
                }
                if (isset($currentPageRows[$sortOrder])) {
                    $row = $currentPageRows[$sortOrder];
                    if ($row->ClassName === $className) {
                        //all OK!
                    } else {
                        // $this->owner->removePageRowFromThisPage($row);
                        //we do not delete the row as it may be used somewhere else ...
                        // $this->owner->deletePageRowFromMe($row);
                    }
                } else {
                    $row = $className::create();
                    $row->Title = 'Title '.$row->singular_name().' #'.$className::get()->count().' for '.$this->owner->MenuTitle;
                    $row->write();
                    $this->owner->PageRows()->add($row, ['SortOrder' => $sortOrder]);
                    $currentPageRows[$sortOrder] = $row;
                }
                DB::query('
                    UPDATE "Page_PageRows"
                    SET "SortOrder" = '.$sortOrder.'
                    WHERE
                        "PageID" ='.$this->owner->ID.' AND
                        "PageRowID" = '.$row->ID.'
                    LIMIT 1;
                ');
                if ($childClassName) {
                    $childClassMethod = $row->ChildClassMethodName();
                    if ($childClassMethod) {
                        $child = $row->$childClassMethod();
                        if ($child && $child->exists()) {
                        } else {
                            $childClassMethodFieldName = $childClassMethod.'ID';
                            $child = $childClassName::create();
                            $child->Title = 'New '.$child->singular_name().' for '.$row->getTitle();
                            $child->write();
                            // debug::log($childClassMethodFieldName);
                            // debug::log($child->ID);
                            // debug::log($child->Title);
                            $row->$childClassMethodFieldName = $child->ID;
                            $row->write();
                        }
                    } else {
                        user_error('no childclass method set in '.$row->ClassName);
                    }
                }
                $sortOrder++;
            }
            $sortOrder = 1;
            foreach ($this->owner->PageRows() as $pageRow) {
                $sortOrder++;
                $delete = false;
                if (!isset($rowClassNames[$sortOrder])) {
                    $delete = true;
                } elseif ($rowClassNames[$sortOrder] !== $pageRow->ClassName) {
                    $delete = true;
                }
                if ($delete) {
                    // $this->owner->removePageRowFromThisPage($pageRow);
                }
            }
        }
    }

    protected function removePageRowFromThisPage($rowOrRowID)
    {
        if ($rowOrRowID instanceof PageRow) {
            $rowOrRowID = $rowOrRowID->ID;
        }
        DB::query('
            DELETE
            FROM "Page_PageRows"
            WHERE
                "PageID" ='.$this->owner->ID.' AND
                "PageRowID" = '.$rowOrRowID.'
            LIMIT 1;
        ');
    }


    public function DefaultPageRows()
    {
        if($this->owner->hasMethod('MyDefaultPageRows')) {
            return $this->owner->MyDefaultPageRows();
        }
        return [];
    }

    #######################
    ### Import / Export Section
    #######################



    #######################
    ### CMS Edit Section
    #######################

    /**
     * Update Fields
     * @return FieldList
     */
    public function updateCMSFields(FieldList $fields)
    {
        $list = $this->owner->PageRows();
        if($this->owner->canEdit() && $this->owner->exists() && $this->HasPageRows()) {
            $fields->addFieldsToTab(
                'Root.ContentBlocks',
                $this->owner->ContentBlocksFields()
            );
        }
    }

    public function ContentBlocksFields()
    {
        $conf = GridFieldConfig_RelationEditor::create(100);
        $conf->addComponent(new GridFieldSortableRows('SortOrder'));
        // switch ($this->owner->ClassName) {
        //     case 'HomePage':
        //         // leave as is ...
        //         $conf->removeComponentsByType('GridFieldAddExistingAutocompleter');
        //         $conf->removeComponentsByType('GridFieldDeleteAction');
        //         $conf->removeComponentsByType('GridFieldAddNewButton');
        //         break;
        //     default:
        // }
        //
        $conf->getComponentByType('GridFieldAddExistingAutocompleter')->setSearchFields(['Code', 'Title']);

        $pageRowField = GridField::create(
            'PageRows',
            'Content Blocks',
            $this->owner->PageRows(),
            $conf
        );
        $array = [$pageRowField];
        $arrayRowList = [];
        foreach ($this->owner->DefaultPageRows() as $count => $className) {
            if (is_array($className)) {
                $className = $className['Parent'];
            }
            $humanCount = $count + 1;
            $arrayRowList[$className.'_'.$count] = $humanCount . ' - '.Injector::inst()->get($className)->singular_name().' ('.$className.')';
        }
        if(count($arrayRowList)) {
            $array[] = LiteralField::create(
                'ListOfContentBlocks',
                '<h2>By default, this page type ('.$this->owner->singular_name().') has the following content blocks:</h2><p>'.implode('</p><p>', $arrayRowList).'</p>'
            );
        }

        return $array;
    }

    public function HasPageRows()
    {
        switch($this->owner->ClassName) {
            case 'ErrorPage':
            case 'RedirectorPage':
            case 'VirtualPage':
                return false;
        }
        if($this->owner->hasMethod('MyHasPageRows')) {
            return $this->owner->MyHasPageRows();
        }
        return true;
    }

}

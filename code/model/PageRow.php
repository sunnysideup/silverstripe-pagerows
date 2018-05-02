<?php



class PageRow extends DataObject
{

    /**
     *
     * @var string
     */
    private static $bg_image_prefix = '';


    /**
     *
     * @var string
     */
    private static $bg_image_postfix = '';

    /**
     *
     * @var array
     */
    private static $background_image_options = [];

    /**
     *
     * @var array
     */
    private static $background_style_options = [];

    /**
     *
     * @var int
     */
    private static $max_number_of_pages_for_tree_selector = 500;

    #######################
    ### Names Section
    #######################

    private static $singular_name = 'Content Block';

    public function i18n_singular_name()
    {
        return _t('PageRows.SINGULAR_NAME', 'Content Block');
    }

    private static $plural_name = 'PageRows';

    public function i18n_plural_name()
    {
        return _t('PageRows.PLURAL_NAME', 'Content Blocks');
    }


    #######################
    ### Model Section
    #######################

    private static $db = [
        'ReadyForPublication' => 'Boolean',
        'Code' => 'Varchar(7)',
        'Title' => 'Varchar(255)',
        'BackgroundImage' => 'Varchar(255)',
        'BackgroundStyle' => 'Varchar(100)'
    ];

    private static $belongs_many_many = [
        'Pages' => 'Page'
    ];


    #######################
    ### Further DB Field Details
    #######################

    private static $indexes = [
        'Created' => true,
        'Code' => true,
        'ReadyForPublication' => true
    ];

    private static $default_sort = [
        'ReadyForPublication' => 'DESC',
        'Created' => 'DESC'
    ];

    private static $required_fields = [
        'Title'
    ];

    private static $searchable_fields = [
        'ClassName' => 'ExactMatchFilter',
        'ReadyForPublication' => 'ExactMatchFilter',
        'Code' => 'PartialMatchFilter',
        'Title' => 'PartialMatchFilter'
    ];

    public function scaffoldSearchFields($_params = null) {
        $list = ClassInfo::subclassesFor('PageRow');
        $newList = [];
        foreach($list as $key => $entry) {
            $newList[$entry] = Injector::inst()->get($entry)->i18n_singular_name();
        }
        $fields = parent::scaffoldSearchFields($_params);
        $fields->replaceField(
            'ClassName',
            DropdownField::create(
                'ClassName',
                'Type',
                ['' => '--- ANY ---'] + $newList
            )
        );
        return $fields;
    }

    #######################
    ### Field Names and Presentation Section
    #######################

    private static $summary_fields = [
        'CodeNice' => 'Code',
        'Type' => 'Type',
        'TitleStrong' => 'Title',
        'UsedOn' => 'Used On',
        'ReadyForPublicationStrong' => 'Published'
    ];

    private static $field_labels = [
        'ReadyForPublication' => 'Ready'
    ];

    private static $field_labels_right = [
        'Code' => 'This is auto-generated (unique) code to keep track of content blocks',
        'ReadyForPublication' => 'Is this Content Block ready for publication?'
    ];


    #######################
    ### Casting Section
    #######################

    private static $casting = [
        'CodeNice' => 'HTMLText',
        'Type' => 'Varchar',
        'UsedOn' => 'Varchar',
        'ChildPageRowHTML' => 'HTMLText',
        'TitleStrong' => 'HTMLText',
        'ReadyForPublicationStrong' => 'HTMLText'
    ];


    public function getType()
    {
        return $this->singular_name();
    }

    public function getCodeNice()
    {
        $v = '<span style="font-family: monospace;">'.substr($this->Code, 0, 3).'-'.substr($this->Code, -4).'</span>';

        return DBField::create_field('HTMLText', $v);
    }

    public function getUsedOn()
    {
        $v = [];
        $pages = $this->Pages();
        foreach($pages as $page) {
            $v[] = $page->MenuTitle;
        }

        return DBField::create_field('Varchar', implode(', ', $v));
    }

    public function getTitleStrong()
    {
        $v = '<strong style="color: blue">'.$this->Title.'</strong>';

        return DBField::create_field('HTMLText', $v);
    }

    public function getReadyForPublicationStrong()
    {
        if($this->ReadyForPublication) {
            $v = '<span style="color: green">Published</span>';
        } else {
            $v = '<span style="color: red">NOT published</span>';
        }

        return DBField::create_field('HTMLText', $v);
    }



    #######################
    ### can Section
    #######################

    /**
     * only children can be created
     */


    public function canDelete($member = null)
    {
        return $this->Pages()->count() ? false : true;
    }



    #######################
    ### write Section
    #######################

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();

        //make template
        $theme = SSViewer::current_theme();
        if($theme) {
            if ($this->ClassName !== 'PageRow') {
                $folder = Director::baseFolder().
                '/themes/'.
                $theme.'_mysite/templates/Includes/page-row/types/';
                if(! file_exists($folder)) {
                    DB::alteration_message('✓ Creating '.$folder, 'created');
                    mkdir($folder, 0755, true);
                }
                if(file_exists($folder)){
                    $fileName =
                        $folder . $this->templateForHTMLOutput($this->ClassName).'.ss';
                    if (! file_exists($fileName)) {
                        DB::alteration_message('✓ Creating ...' . $fileName, 'created');
                        file_put_contents(
                            $fileName,
        '<% include PageRowHeader %>

        <% include PageRowFooter %>'
                        );
                    } else {
                        DB::alteration_message('✓ Checked ...' . $fileName);
                    }
                }
            }

            //scss files ...
            $folder =
                Director::baseFolder().'/themes/'.
                $theme.'_mysite/src/sass/page-rows/';
            $fileName = $folder . $this->ClassName.'.scss';
            if(! file_exists($folder)) {
                mkdir($folder, 0755, true);
            }
            if (! file_exists($fileName)) {
                DB::alteration_message('✓ Creating ...' . $fileName, 'created');
                file_put_contents(
                    $fileName,
    '.'.strtolower($this->ClassName).'.row {

    }'
                );
            } else {
                DB::alteration_message('✓ Checked ...' . $fileName);
            }
        }
    }


    public function validate()
    {
        $result = parent::validate();
        if ($this->ReadyForPublication) {
            $fieldLabels = $this->FieldLabels();
            $indexes = $this->Config()->get('indexes');
            foreach ($this->Config()->get('required_fields') as $field) {
                $value = $this->$field;
                if (! $value) {
                    $fieldWithoutID = $field;
                    if (substr($fieldWithoutID, -2) === 'ID') {
                        $fieldWithoutID = substr($fieldWithoutID, 0, -2);
                    }
                    $myName = isset($fieldLabels[$fieldWithoutID]) ? $fieldLabels[$fieldWithoutID] : $fieldWithoutID;
                    $result->error(
                        _t(
                            'PageRows.'.$field.'_REQUIRED',
                            $myName.' is required'
                        ),
                        'REQUIRED_PageRows_'.$field
                    );
                }
                if (isset($indexes[$field]) && isset($indexes[$field]['type']) && $indexes[$field]['type'] === 'unique') {
                    $id = (empty($this->ID) ? 0 : $this->ID);
                    $count = PageRows::get()
                        ->filter(array($field => $value))
                        ->exclude(array('ID' => $id))
                        ->count();
                    if ($count > 0) {
                        $myName = $fieldLabels['$field'];
                        $result->error(
                            _t(
                                'PageRows.'.$field.'_UNIQUE',
                                $myName.' needs to be unique'
                            ),
                            'UNIQUE_PageRows_'.$field
                        );
                    }
                }
            }
        }
        return $result;
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if (!$this->Code) {
            $this->Code = hash('md5', rand());
        }
        //...
    }

    public function onAfterWrite()
    {
        parent::onAfterWrite();
        if (!$this->Code) {
            $this->Code = hash('md5', rand());
        }
        if(class_exists('DynamicCache')) {
            DynamicCache::inst()->clear();
        }
    }


    #######################
    ### Import / Export Section
    #######################


    #######################
    ### CMS Edit Section
    #######################


    protected $runCMSFieldFixups = true;

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName('BackgroundImage');
        $fields->removeByName('BackgroundStyle');
        $fields->removeByName('Pages');
        if ($this->exists() && $this->Code) {
            //too late now!
        } else {
            $list = ClassInfo::subclassesFor('PageRow');
            $arrayOfTypes = [];
            foreach ($list as $className) {
                if ($className === 'PageRow') {
                    continue;
                }
                $arrayOfTypes[$className] = Injector::inst()->get($className)->singular_name();
            }
            $fields->addFieldToTab(
                'Root.Main',
                DropdownField::create(
                    'ClassName',
                    'Type',
                    $arrayOfTypes
                ),
                'Title'
            );
        }
        $fieldLabels = $this->FieldLabels();
        $fields->insertAfter(
            'ReadyForPublication',
            ReadonlyField::create(
                'ClassNameNice',
                'Type',
                $this->singular_name()
            )
        );
        $fields->replaceField(
            'Code',
            ReadonlyField::create(
                'Code',
                'Code'
            )
        );



        $fieldGroups = $this->myCMSFieldGroups();
        foreach ($fieldGroups as $tabTitle => $fieldGroup) {
            if(is_array($fieldGroup) && count($fieldGroup)) {
                foreach ($fieldGroup as $fieldNameOrFormField) {
                    if($fieldNameOrFormField instanceof FormField) {
                        $fields->addFieldToTab(
                            'Root.'.$tabTitle,
                            $fieldNameOrFormField
                        );
                    } else {
                        $fieldObject = $fields->dataFieldByName($fieldNameOrFormField);
                        if ($fieldObject) {
                            $fields->addFieldToTab(
                                'Root.'.$tabTitle,
                                $fieldObject
                            );
                        }
                    }
                }
            }
        }

        //...



        if ($this->exists()) {
            $fields->addFieldsToTab(
                'Root.Preview',
                [
                    LiteralField::create(
                        'PreviewClassName',
                        '<h2><a href="'.$this->MyPreviewLink(strtolower($this->ClassName)).'">View All '.$this->singular_name().' Page Blocks</a></h2>'
                    ),
                    LiteralField::create(
                        'PreviewIframe',
                        '<iframe src="'.$this->MyPreviewLink($this->ID).'" style="width: calc(100% - 2px); height: 600px; border: 1px solid #000;"></iframe>'
                    ),
                    LiteralField::create(
                        'PreviewAll',
                        '<h2><a href="'.$this->MyPreviewLink('all').'">View All Page Blocks</a></h2>'
                    ),
                    LiteralField::create(
                        'PreviewOneOfEach',
                        '<h2><a href="'.$this->MyPreviewLink('oneofeach').'">View One Of Each Page Block Type</a></h2>'
                    )
                ]
            );
            if(Page::get()->count() > $this->Config()->get('max_number_of_pages_for_tree_selector')) {
                $fields->addFieldToTab(
                    'Root.Pages',
                    GridField::create(
                        'Pages',
                        'Shown On ...',
                        $this->Pages(),
                        GridFieldConfig_RecordViewer::create()
                    )
                );
            } else {
                $fields->addFieldToTab(
                    'Root.Pages',
                    TreeMultiselectField::create(
                        'Pages',
                        'Shown On ...',
                        'SiteTree'
                        )
                    );
            }


            $usedInArray = [];
            $pageClasses = ClassInfo::subclassesFor('SiteTree');
            foreach ($pageClasses as $pageClass) {
                $pageClassObject = Injector::inst()->get($pageClass);
                if ($pageClassObject instanceof Page) {
                    $classesUsed = $pageClassObject->DefaultPageRows();
                    foreach ($classesUsed as $individualItems) {
                        if (
                            (
                                is_array($individualItems) &&
                                (
                                    $individualItems['Parent'] === $this->ClassName ||
                                    $individualItems['Child']=== $this->ClassName
                                )
                            )
                            ||
                            (is_string($individualItems) && $individualItems=== $this->ClassName)
                        ) {
                            $usedInArray[$pageClassObject->ClassName] = $pageClassObject->singular_name();
                        }
                    }
                }
            }
            if (count($usedInArray)) {
                $fields->addFieldToTab(
                    'Root.Pages',
                    LiteralField::create(
                        'UsedInInfo',
                        '<h2>'.$this->plural_name().' ('.$this->ClassName.') are used in the following pages ...</h2><p>
                        - '.implode('<br />- ', $usedInArray).'
                        </p>'
                    )
                );
            }
        }

        if ($this->runCMSFieldFixups) {
            $this->decorateCMSFields($fields);
        }

        return $fields;
    }

    protected function myCMSFieldGroups()
    {
        return [
            'Image' => $this->getImageCMSFields(),
            'Layout' => $this->getLayoutCMSFields(),
            'Background' => $this->getBackgroundFields(),
            'Publish' => $this->getPublishCMSFields()
        ];
    }

    #############################################
    ### TEMPLATE STUFF
    #############################################


    public function HTMLClassNamesAsString()
    {
        $str = $this->makeIntoHTMLClasses($this->baseClassesForHTMLAsArray());
        $str .= ' '.$this->makeIntoHTMLClasses($this->AdditionalHTMLClassNamesAsArray());

        return $str;
    }

    public function ChildInheritedClassNamesAsString()
    {
        return "";
    }

    protected function baseClassesForHTMLAsArray()
    {
        /**
         * we avoid the use of Advertisement as this will be blocked by adblockers ...
         */
        $array = [
            ($this->HasChildPageRowHTML() ? 'has-child-row' : 'no-child-row'),
            $this->ClassName
        ];
        foreach($this->hasOne() as $method => $item) {
            $field = $method.'ID';
            if($this->$field) {
                $array [] = 'has-'.strtolower($method);
            }
        }
        return $array;
    }

    protected function makeIntoHTMLClasses($array)
    {
        $str = implode(' ', $array);
        $str = str_replace('  ', ' ', $str);
        $str = str_replace('  ', ' ', $str);
        $str = strtolower($str);

        return $str;
    }

    public function UserCanEditMe()
    {
        return $this->canEdit();
    }


    private static $_current_owner_page_object_overridden = null;

    public static function set_current_page_object($page)
    {
        self::$_current_owner_page_object_overridden = $page;
    }


    public function CurrentOwnerPageObject()
    {
        if (self::$_current_owner_page_object_overridden) {
            return self::$_current_owner_page_object_overridden;
        }
        $controller = $this->CurrentOwnerController();
        if ($controller) {
            return $controller->data();
        }
    }

    public function CurrentOwnerController()
    {
        $controller = Controller::curr();
        if ($controller instanceof Page_Controller) {
            return $controller;
        }
    }

    public function AdditionalHTMLClassNamesAsArray()
    {
        return [];
    }

    public function HTMLOutputAlwaysOutput($includeJS = true)
    {
        return $this->HTMLOutput($includeJS, true);
    }


    public function HTMLOutput($includeJS = true, $alwaysShow = false)
    {
        if ($this->ReadyForPublication || $alwaysShow) {
            $scripts = $this->customScripts();
            if ($includeJS) {
                foreach ($scripts as $key => $script) {
                    Requirements::customScript(
                        $script,
                        $key
                    );
                }
            }

            return $this->renderWith($this->templateForHTMLOutput());
        }
    }

    /**
     * custom scripts required to run this show
     * @return array
     */
    protected function customScripts()
    {
        return [];
    }

    protected function templateForHTMLOutput($className = null)
    {
        if (! $className) {
            $className = $this->ClassName;
        }
        return 'PageRow-'.$className;
    }

    public function MyPreviewLink($id = 0)
    {
        if (! $id) {
            $id = $this->ID;
        }
        $controller = Injector::inst()->get('PreviewPageRow');

        return $controller->Link('preview/'.$id);
    }

    public function MyPublicViewLink()
    {
        if (! $id) {
            $id = $this->ID;
        }
        $controller = Injector::inst()->get('PreviewPageRow');
        return $controller->Link('preview/'.$id);
    }


    public function MoreDetailsRowChildLinkingID()
    {
        return 'PageRow-'.$this->ID;
    }



    public function UserCanEditBlock($member = null)
    {
        return $this->canEdit($member);
    }

    public function ContextRelevantCMSEditLink()
    {
        $ownerPage = $this->CurrentOwnerPageObject();
        if ($ownerPage && $ownerPage instanceof SiteTree) {
            return '/admin/pages/edit/EditForm/'.$ownerPage->ID.'/field/PageRows/item/'.$this->ID.'/edit/';
        } else {
            return $this->CMSEditLink();
        }
    }


    public function ChildClassMethodName()
    {
        return '';
    }

    private $_childPageRowHTML = null;

    /**
     * @alias for HasChildPageRowHTML
     * @return bool
     */
    public function HasChild()
    {
        return $this->CalculatedHasMoreDetailsRow();
    }

    public function AsChildOpenByDefault()
    {
        return false;
    }


    public function MoreDetailsRowParentLinkingID()
    {
        if($this->CalculatedHasMoreDetailsRow()) {
            return 'PageRow'.$this->MoreDetailsRowID;
        }

        return null;
    }


    public function CalculatedHasMoreDetailsRow()
    {
        if(!empty($this->HasMoreDetailsSection)) {
            if(!empty($this->MoreDetailsRowID)) {
                if($object = $this->MoreDetailsRow()){
                    if($object->exists() && $object->ReadyForPublication) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     *
     * @return bool
     */
    public function HasChildPageRowHTML()
    {
        return $this->CalculatedHasMoreDetailsRow();
    }

    /**
     * @return null / string
     */
    public function ChildPageRowHTML()
    {
        return $this->getChildPageRowHTML();
    }

    /**
     * @return null / string
     */
    public function getChildPageRowHTML()
    {
        if ($this->_childPageRowHTML === null) {
            $this->_childPageRowHTML = false;
            $method = $this->ChildClassMethodName();
            if ($method) {
                $object = $this->$method();
                if ($object && $object->exists()) {
                    $this->_childPageRowHTML = $object->HTMLOutput();
                }
            }
        }
        return $this->_childPageRowHTML;
    }

    ###############################
    # COPIED AND PATCHED FROM:
    # https://github.com/jonom/silverstripe-version-history/blob/master/code/VersionHistoryExtension.php
    ###############################

    protected function getImageCMSFields()
    {
        return [];
    }

    protected function getLayoutCMSFields()
    {
        return [];
    }

    protected function getBackgroundFields()
    {
        $a = [];
        if($this->IsPageRowWithBackgroundStyle()) {
            $a[] = DropdownField::create(
                'BackgroundStyle',
                'Background Style',
                $this->BackgroundStyleOptionsWithBasics()
            );
        }
        if($this->IsPageRowWithBackgroundImage()) {
            $a[] = DropdownField::create(
                'BackgroundImage',
                'Background Image',
                $this->BackgroundImageOptionsWithBasics()
            );
        }
        return $a;
    }


    protected function getPublishCMSFields()
    {
        return [
            'ReadyForPublication',
            'Code'
        ];
    }

    private $_bgImage = null;

    protected $_theSameBackgroundImageForAllOfThisTypeToDo = true;

    public function IsPageRowWithBackgroundImage()
    {
        $prefix = $this->Config()->get('bg_image_prefix');
        $postfix = $this->Config()->get('bg_image_postfix');
        if($prefix || $postfix) {
            $options = $this->BackgroundImageOptions();
            if(count($options)) {
                return true;
            }
        }
    }

    public function HasBackgroundImage()
    {
        return $this->BackgroundImage ? true : false;
    }

    public function CalculatedBackgroundImage()
    {
        if($this->_bgImage === null) {
            $this->_bgImage = false;
            if($this->BackgroundImage) {
                $prefix = $this->Config()->get('bg_image_prefix');
                $postfix = $this->Config()->get('bg_image_postfix');
                if($this->BackgroundImage == 'random') {
                    $a = $this->BackgroundImageOptions();
                    if(count($a)) {
                        $this->_bgImage = $prefix.$a[array_rand($a)].$postfix;
                    }
                } else {
                    $this->_bgImage = $prefix.$this->BackgroundImage.$postfix;
                }
                if($this->HasSameBackgroundImageForAllOfThisType() && $this->_theSameBackgroundImageForAllOfThisTypeToDo) {
                    $this->_theSameBackgroundImageForAllOfThisTypeToDo = false;
                    Requirements::customCSS('
                        section.row.'.strtolower($this->ClassName).' {
                            background-image: url('.$this->_bgImage.');
                        }
                    ');
                }
            }
        }
        return $this->_bgImage;
    }

    protected function HasSameBackgroundImageForAllOfThisType()
    {
        return false;
    }

    protected function BackgroundImageOptions()
    {
        return $this->Config()->get('background_image_options');
    }

    protected function BackgroundImageOptionsWithBasics()
    {
        $a = ['' => '--- no background image selected ---']
            + $this->BackgroundImageOptions();
        if(count($a) > 2) {
            $a['random'] = 'Random Background Image';
        }
        return $a;
    }


    protected $_bgStyle = null;

    public function IsPageRowWithBackgroundStyle()
    {
        $options = $this->BackgroundStyleOptions();
        if(count($options)) {
            return true;
        }
    }

    public function CalculatedBackgroundStyle()
    {
        if($this->_bgStyle === null) {
            $this->_bgStyle = false;
            if($this->BackgroundStyle) {
                if($this->BackgroundStyle == 'random') {
                    $a = $this->BackgroundStyleOptions();
                    if(count($a)) {
                        $this->_bgStyle = $a[array_rand($a)];
                    }
                } else {
                    $this->_bgStyle = $this->BackgroundStyle;
                }
            }
        }
        return $this->_bgStyle;
    }


    protected function BackgroundStyleOptionsWithBasics()
    {
        $a = ['' => '--- default style ---']
            + $this->BackgroundstyleOptions();
        if(count($a) > 2) {
            $a['random'] = 'Random Background Style';
        }
        return $a;
    }


    protected function BackgroundStyleOptions()
    {
        return $this->Config()->get('background_style_options');
    }
}

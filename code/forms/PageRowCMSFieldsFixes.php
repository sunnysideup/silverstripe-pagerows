<?php

class PageRowCMSFieldsFixes extends DataExtension
{

    /**
     * Update Fields
     * @return FieldList
     */
    public function decorateCMSFields(FieldList $fields)
    {
        //right titles ...
        $list = $fields->dataFields();

        $rightFieldDescriptions = Config::inst()->get($this->owner->ClassName, 'field_labels_right');
        if (is_array($rightFieldDescriptions) && count($rightFieldDescriptions)) {
            foreach ($list as $formField) {

                // right titles ...
                $desc = '';
                $fieldName = $formField->getName();
                if (isset($rightFieldDescriptions[$fieldName])) {
                    $desc = $rightFieldDescriptions[$fieldName];
                }
                if (!$desc) {
                    if (isset($rightFieldDescriptions[$fieldName.'ID'])) {
                        $desc = $rightFieldDescriptions[$fieldName];
                    }
                }
                if (!$desc) {
                    if (substr($fieldName, -2) === 'ID') {
                        $fieldName = substr($fieldName, -2);
                        if (isset($rightFieldDescriptions[$fieldName])) {
                            $desc = $rightFieldDescriptions[$fieldName];
                        }
                    }
                }
                if ($desc) {
                    if ($formField->hasMethod('setDescription')) {
                        $formField->setDescription($desc);
                    } else {
                        $formField->setRightTitle($desc);
                    }
                }


                //HTML Editor fields
                //HTML
                if ($formField instanceof HTMLEditorField) {
                    $formField->setRows(12)
                        ->setRightTitle('Need more room? Use the FULL SCREEN button (last button above).');
                }
                if ($formField instanceof DateField) {
                    $formField->setConfig('showcalendar', true);
                }
            }
        }
        return $fields;
    }


}

<?php



class LeftOrRight extends Boolean
{
    public function Nice()
    {
        return ($this->value) ? _t('FalseIsLeftTrueIsRightDBField.YESANSWER', 'Right') : _t('FalseIsLeftTrueIsRightDBField.NOANSWER', 'Left');
    }
    //
    // public function NiceAsBoolean() {
    //     return ($this->value) ? 'true' : 'false';
    // }

    /**
     * Saves this field to the given data object.
     */
    public function saveInto($dataObject)
    {
        $fieldName = $this->name;
        if ($fieldName) {
            $dataObject->$fieldName = ($this->value) ? 1 : 0;
        } else {
            user_error("DBField::saveInto() Called on a nameless '$this->class' object", E_USER_ERROR);
        }
    }

    public function scaffoldFormField($title = null, $params = null)
    {
        return new OptionSetField(
            $this->name,
            $title,
            [
                0 => 'Left',
                1 => 'Right'
            ]
        );
    }

    public function scaffoldSearchField($title = null)
    {
        $anyText = _t('FalseIsLeftTrueIsRightDBField.ANY', 'Any');
        $source = array(
            0 => _t('FalseIsLeftTrueIsRightDBField.NOANSWER', 'Left'),
            1 => _t('FalseIsLeftTrueIsRightDBField.YESANSWER', 'Right')
        );

        $field = new DropdownField($this->name, $title, $source);
        $field->setEmptyString("($anyText)");
        
        return $field;
    }
}

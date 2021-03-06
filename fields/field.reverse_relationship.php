<?php
/**
 * Copyright: Deux Huit Huit 2017
 * LICENCE: MIT https://deuxhuithuit.mit-license.org
 */

if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

require_once(EXTENSIONS . '/entry_relationship_field/lib/class.field.relationship.php');
require_once(EXTENSIONS . '/entry_relationship_field/lib/class.cacheablefetch.php');
require_once(EXTENSIONS . '/entry_relationship_field/lib/class.erfxsltutilities.php');

/**
 *
 * Field class that will represent a reverse relationship between entries
 * @author Deux Huit Huit
 *
 */
class FieldReverse_Relationship extends FieldRelationship
{
    /**
     *
     * Name of the field table
     *  @var string
     */
    const FIELD_TBL_NAME = 'tbl_fields_reverse_relationship';

    /**
     *
     * Constructor for the Reverse_Relationship Field object
     */
    public function __construct()
    {
        // call the parent constructor
        parent::__construct();
        // set the name of the field
        $this->_name = __('Reverse Relationship');
        // allowed to make it required
        $this->_required = true;
        // allowed to show in the table columns
        $this->_showcolumn = true;
        // forbid association
        $this->_showassociation = false;
        // set as not required by default
        $this->set('required', 'no');
        // show header by default
        $this->set('show_header', 'yes');
        // allow link by default
        $this->set('allow_unlink', 'yes');
        // allow go to by default
        $this->set('allow_goto', 'yes');
        // no modes
        $this->set('mode', null);
        $this->set('mode_table', null);
        $this->set('mode_header', null);
        $this->set('mode_footer', null);
        // no links
        $this->set('linked_section_id', null);
        $this->set('linked_field_id', null);
    }

    public function isSortable()
    {
        return true;
    }

    public function canFilter()
    {
        return false;
    }
    
    public function canPublishFilter()
    {
        return false;
    }

    public function canImport()
    {
        return false;
    }

    public function canPrePopulate()
    {
        return false;
    }
    
    public function mustBeUnique()
    {
        return false;
    }

    public function allowDatasourceOutputGrouping()
    {
        return false;
    }

    public function requiresSQLGrouping()
    {
        return false;
    }

    public function allowDatasourceParamOutput()
    {
        return false;
    }

    public function requiresTable()
    {
        return false;
    }

    public function createTable()
    {
        return false;
    }

    public function fetchIncludableElements()
    {
    }

    /* ********** INPUT AND FIELD *********** */

    /**
     * 
     * Validates input
     * Called before <code>processRawFieldData</code>
     * @param $data
     * @param $message
     * @param $entry_id
     */
    public function checkPostFieldData($data, &$message, $entry_id=null)
    {
        return self::__OK__;
    }


    /**
     *
     * Process data before saving into database.
     *
     * @param array $data
     * @param int $status
     * @param boolean $simulate
     * @param int $entry_id
     *
     * @return Array - data to be inserted into DB
     */
    public function processRawFieldData($data, &$status, &$message = null, $simulate = false, $entry_id = null)
    {
        $status = self::__OK__;
        return null;
    }


    /**
     *
     * Validates the field settings before saving it into the field's table
     */
    public function checkFields(Array &$errors, $checkForDuplicates = true)
    {
        $parent = parent::checkFields($errors, $checkForDuplicates);
        if ($parent != self::__OK__) {
            return $parent;
        }
        
        $sections = $this->get('linked_section_id');
        if (empty($sections)) {
            $errors['sections'] = __('A section must be chosen');
        }
        $field = $this->get('linked_field_id');
        if (empty($field)) {
            $errors['field'] = __('A field must be chosen');
        }

        return (!empty($errors) ? self::__ERROR__ : self::__OK__);
    }

    /**
     *
     * Save field settings into the field's table
     */
    public function commit()
    {
        // if the default implementation works...
        if(!parent::commit()) return false;
        
        $id = $this->get('id');
        
        // exit if there is no id
        if ($id == false) return false;
        
        // declare an array contains the field's settings
        $settings = array(
            'linked_section_id' => $this->get('linked_section_id'),
            'linked_field_id' => $this->get('linked_field_id'),
            'mode' => $this->get('mode'),
            'mode_table' => $this->get('mode_table'),
            'mode_header' => $this->get('mode_header'),
            'mode_footer' => $this->get('mode_footer'),
        );
        
        return FieldManager::saveSettings($id, $settings);
    }

    /**
     * Appends data into the XML tree of a Data Source
     * @param $wrapper
     * @param $data
     */
    public function appendFormattedElement(XMLElement &$wrapper, $data, $encode = false, $mode = null, $entry_id = null)
    {
        // nothing to do
    }

    /* ********* UI *********** */
    
    /**
     *
     * Builds the UI for the field's settings when creating/editing a section
     * @param XMLElement $wrapper
     * @param array $errors
     */
    public function displaySettingsPanel(XMLElement &$wrapper, $errors = null)
    {
        /* first line, label and such */
        parent::displaySettingsPanel($wrapper, $errors);

        // fieldset
        $fieldset = new XMLElement('fieldset', null);

        // group
        $group = new XMLElement('div', null, array('class' => 'two columns'));
        $fieldset->appendChild($group);

        // sections
        $sections = new XMLElement('div', null, array('class' => 'column'));
        $this->appendSelectionSelect($sections);
        if (is_array($errors) && isset($errors['sections'])) {
            $sections = Widget::Error($sections, $errors['sections']);
        }
        $group->appendChild($sections);

        // field
        $field = new XMLElement('div', null, array('class' => 'column'));
        $this->appendFieldSelect($field);
        if (is_array($errors) && isset($errors['field'])) {
            $field = Widget::Error($field, $errors['field']);
        }
        $group->appendChild($field);

        $wrapper->appendChild($fieldset);

        // xsl
        $xsl = new XMLElement('fieldset');
        $xsl->appendChild(new XMLElement('legend', __('Backend XSL templates options')));
        $xsl_cols = new XMLElement('div');
        $xsl_cols->setAttribute('class', 'four columns');

        // xsl mode
        $xslmode = Widget::Label();
        $xslmode->setValue(__('XSL mode for entries content template'));
        $xslmode->setAttribute('class', 'column');
        $xslmode->appendChild(Widget::Input($this->createSettingsFieldName('mode'), $this->get('mode'), 'text'));
        $xsl_cols->appendChild($xslmode);

        // xsl header mode
        $xslmodetable = Widget::Label();
        $xslmodetable->setValue(__('XSL mode for entries header template'));
        $xslmodetable->setAttribute('class', 'column');
        $xslmodetable->appendChild(Widget::Input($this->createSettingsFieldName('mode_header'), $this->get('mode_header'), 'text'));
        $xsl_cols->appendChild($xslmodetable);

        // xsl table mode
        $xslmodetable = Widget::Label();
        $xslmodetable->setValue(__('XSL mode for publish table value'));
        $xslmodetable->setAttribute('class', 'column');
        $xslmodetable->appendChild(Widget::Input($this->createSettingsFieldName('mode_table'), $this->get('mode_table'), 'text'));
        $xsl_cols->appendChild($xslmodetable);

        // xsl action bar mode
        $xslmodetable = Widget::Label();
        $xslmodetable->setValue(__('XSL mode for publish action bar'));
        $xslmodetable->setAttribute('class', 'column');
        $xslmodetable->appendChild(Widget::Input($this->createSettingsFieldName('mode_footer'), $this->get('mode_footer'), 'text'));
        $xsl_cols->appendChild($xslmodetable);

        $xsl->appendChild($xsl_cols);
        $wrapper->appendChild($xsl);

        // Footer
        $this->appendStatusFooter($wrapper);
    }

    /**
     *
     * Builds the UI for the publish page
     * @param XMLElement $wrapper
     * @param mixed $data
     * @param mixed $flagWithError
     * @param string $fieldnamePrefix
     * @param string $fieldnamePostfix
     */
    public function displayPublishPanel(XMLElement &$wrapper, $data = null, $flagWithError = null, $fieldnamePrefix = null, $fieldnamePostfix = null, $entry_id = null)
    {
        if (!$entry_id) {
            return;
        }
        $field = FieldManager::fetch($this->get('linked_field_id'));
        $section = SectionManager::fetch($this->get('linked_section_id'));
        if (!($field instanceof FieldRelationship)) {
            $flagWithError = __('Linked field is not valid. Please edit this field to set it to a valid ER field.');
        }
        
        $label = Widget::Label($this->get('label'));
        // label error management
        if ($flagWithError != null) {
            $wrapper->appendChild(Widget::Error($label, $flagWithError));
        } else {
            $wrapper->appendChild($label);
            $wrapper->appendChild($this->createEntriesList(array()));
            $wrapper->appendChild($this->createActionBarMenu($field));
        }
        
        $wrapper->setAttribute('data-field-id', $this->get('id'));
        $wrapper->setAttribute('data-linked-field-id', $this->get('linked_field_id'));
        $wrapper->setAttribute('data-linked-section-id', $this->get('linked_section_id'));
        $wrapper->setAttribute('data-linked-section', $section->get('handle'));
        $wrapper->setAttribute('data-field-label', $field->get('label'));
        $wrapper->setAttribute(
            'data-entries',
            implode(self::SEPARATOR, $field->findRelatedEntries($entry_id, null))
        );
        if (isset($_REQUEST['debug'])) {
            $wrapper->setAttribute('data-debug', true);
        }
    }

    private function createActionBarMenu($field)
    {
        $section = SectionManager::fetch($this->get('linked_section_id'));
        $wrap = new XMLElement('div');

        $fieldset = new XMLElement('fieldset');
        $fieldset->setAttribute('class', 'single');
        $fieldset->appendChild(new XMLElement(
            'span',
            __('Related section: '),
            array('class' => 'reverse-selection')
        ));
        $fieldset->appendChild(new XMLElement(
            'label',
            General::sanitize($section->get('name') . ': ' . $field->get('label')),
            array('class' => 'reverse-selection')
        ));
        $fieldset->appendChild(new XMLElement('button', __('Add to entry'), array(
            'type' => 'button',
            'class' => 'add',
            'data-add' => $section->get('handle'),
        )));

        $wrap->appendChild($fieldset);

        return $wrap;
    }

    private static $erFields = array();
    private function getERFields()
    {
        if (empty(self::$erFields)) {
            self::$erFields = FieldManager::fetch(null, null, null, 'id', 'entry_relationship');
        }
        return self::$erFields;
    }

    private static $erSections = array();
    private function getERSections()
    {
        if (empty(self::$erSections)) {
            $erFields = self::getERFields();
            $sectionIds = array_map(function ($erField) {
                return $erField->get('parent_section');
            }, $erFields);
            self::$erSections = SectionManager::fetch($sectionIds);
        }
        return self::$erSections;
    }

    private function buildSectionSelect($name)
    {
        $sections = static::getERSections();
        $options = array();
        
        foreach ($sections as $section) {
            $driver = $section->get('id');
            $selected = $driver === $this->get('linked_section_id');
            $options[] = array($driver, $selected, General::sanitize($section->get('name')));
        }
        
        return Widget::Select($name, $options);
    } 

    private function appendSelectionSelect(&$wrapper)
    {
        $name = $this->createSettingsFieldName('linked_section_id', false);

        $input = $this->buildSectionSelect($name);
        $input->setAttribute('class', 'reverse_relationship-sections');

        $label = Widget::Label();

        $label->setValue(__('Available sections %s', array($input->generate())));

        $wrapper->appendChild($label);
    }

    private function buildFieldSelect($name)
    {
        $section = $this->get('linked_section_id') ? SectionManager::fetch($this->get('linked_section_id')) : null;
        $fields = static::getERFields();
        $options = array();
        
        foreach ($fields as $field) {
            if ($section && $section->get('id') !== $field->get('parent_section')) {
                continue;
            }
            $driver = $field->get('id');
            $selected = $driver === $this->get('linked_field_id');
            $options[] = array($driver, $selected, General::sanitize($field->get('label')));
        }
        
        return Widget::Select($name, $options);
    } 

    protected function appendFieldSelect(&$wrapper)
    {
        $name = $this->createSettingsFieldName('linked_field_id', false);

        $input = $this->buildFieldSelect($name);
        $input->setAttribute('class', 'reverse_relationship-field');

        $label = Widget::Label();

        $label->setValue(__('Available Fields %s', array($input->generate())));

        $wrapper->appendChild($label);
    }

    /**
     *
     * Return a plain text representation of the field's data
     * @param array $data
     * @param int $entry_id
     */
    public function prepareTextValue($data, $entry_id = null)
    {
        $field = FieldManager::fetch($this->get('linked_field_id'));
        $section = SectionManager::fetch($this->get('linked_section_id'));
        if ($entry_id == null || !$field || !$section) {
            return null;
        }
        $fieldId = $field->get('id');
        $where = $field->generateWhereFilter($entry_id);
        $data = Symphony::Database()->fetch("SELECT `entries` FROM `tbl_entries_data_$fieldId` AS `d` WHERE 1=1 $where");
        if (!is_array($data) || !($data = current($data))) {
            return null;
        }
        return $data['entries'];
    }

    /**
     * Format this field value for display as readable text value.
     *
     * @param array $data
     *  an associative array of data for this string. At minimum this requires a
     *  key of 'value'.
     * @param integer $entry_id (optional)
     *  An option entry ID for more intelligent processing. Defaults to null.
     * @param string $defaultValue (optional)
     *  The value to use when no plain text representation of the field's data
     *  can be made. Defaults to null.
     * @return string
     *  the readable text summary of the values of this field instance.
     */
    public function prepareReadableValue($data, $entry_id = null, $truncate = false, $defaultValue = 'None')
    {
        $field = FieldManager::fetch($this->get('linked_field_id'));
        $section = SectionManager::fetch($this->get('linked_section_id'));
        if ($entry_id == null || !$field || !$section) {
            return __($defaultValue);
        }

        $fieldId = $field->get('id');
        $where = $field->generateWhereFilter($entry_id);
        $entries = Symphony::Database()->fetch("SELECT DISTINCT * FROM `tbl_entries_data_$fieldId` AS `d` WHERE 1=1 $where");
        $output = array();
        foreach ($entries as $e) {
            $e['entries'] = $entry_id;
            $output[] = $field->prepareReadableValue($e, $e['entry_id'], false, $defaultValue);
        }
        return implode('', $output);
    }

    /**
     * Format this field value for display in the publish index tables.
     *
     * @param array $data
     *  an associative array of data for this string. At minimum this requires a
     *  key of 'value'.
     * @param XMLElement $link (optional)
     *  an XML link structure to append the content of this to provided it is not
     *  null. it defaults to null.
     * @param integer $entry_id (optional)
     *  An option entry ID for more intelligent processing. defaults to null
     * @return string
     *  the formatted string summary of the values of this field instance.
     */
    public function prepareTableValue($data, XMLElement $link = null, $entry_id = null)
    {
        $field = FieldManager::fetch($this->get('linked_field_id'));
        $section = SectionManager::fetch($this->get('linked_section_id'));
        if ($entry_id == null || !$field || !$section) {
            return __($defaultValue);
        }

        $fieldId = $field->get('id');
        $where = $field->generateWhereFilter($entry_id);
        $entries = Symphony::Database()->fetch("SELECT DISTINCT * FROM `tbl_entries_data_$fieldId` AS `d` WHERE 1=1 $where");
        $output = array();
        $this->set('elements', '*');
        $this->set('sections', $this->get('linked_section_id'));
        foreach ($entries as $position => $e) {
            $e['entries'] = $entry_id;
            $value = $field->prepareTableValue($e, $link, $e['entry_id']);

            if ($this->get('mode_table')) {
                $entry = current(EntryManager::fetch($e['entry_id']));
                $cellcontent = ERFXSLTUTilities::processXSLT($this, $entry, $section->get('handle'), $section->fetchFields(), 'mode_table', isset($_REQUEST['debug']), 'entry', $position + 1);

                $cellcontent = trim($cellcontent);
                if (General::strlen($cellcontent)) {
                    if ($link) {
                        $link->setValue($cellcontent);
                        $value = $link->generate();
                    } else {
                        $value = $cellcontent;
                    }
                }
            } else if ($link) {
                $link->setValue($value);
                $value = $link->generate();
            }

            $output[] = $value;
        }
        return implode('', $output);
    }

    /**
     * Build the SQL command to append to the default query to enable
     * sorting of this field. By default this will sort the results by
     * the entry id in ascending order.
     *
     * Extension developers should always implement both `buildSortingSQL()`
     * and `buildSortingSelectSQL()`.
     *
     * @uses Field::isRandomOrder()
     * @see Field::buildSortingSelectSQL()
     * @param string $joins
     *  the join element of the query to append the custom join sql to.
     * @param string $where
     *  the where condition of the query to append to the existing where clause.
     * @param string $sort
     *  the existing sort component of the sql query to append the custom
     *  sort sql code to.
     * @param string $order (optional)
     *  an optional sorting direction. this defaults to ascending. if this
     *  is declared either 'random' or 'rand' then a random sort is applied.
     */
    public function buildSortingSQL(&$joins, &$where, &$sort, $order = 'ASC')
    {
        if ($this->isRandomOrder($order)) {
            $sort = 'ORDER BY RAND()';
        } else {
            $sort = "ORDER BY `order_value` $order";
        }
    }

    /**
     * Build the needed SQL clause command to make `buildSortingSQL()` work on
     * MySQL 5.7 in strict mode, which requires all columns in the ORDER BY
     * clause to be included in the SELECT's projection.
     *
     * If no new projection is needed (like if the order is made via a sub-query),
     * simply return null.
     *
     * For backward compatibility, this method checks if the sort expression
     * contains `ed`.`value`. This check will be removed in Symphony 3.0.0.
     *
     * Extension developers should make their Fields implement
     * `buildSortingSelectSQL()` when overriding `buildSortingSQL()`.
     *
     * @since Symphony 2.7.0
     * @uses Field::isRandomOrder()
     * @see Field::buildSortingSQL()
     * @param string $sort
     *  the existing sort component of the sql query, after it has been passed
     *  to `buildSortingSQL()`
     * @param string $order (optional)
     *  an optional sorting direction. this defaults to ascending. Should be the
     *  same value that was passed to `buildSortingSQL()`
     * @return string
     *  an optional select clause to append to the generated SQL query.
     *  This is needed when sorting on a column that is not part of the projection.
     */
    public function buildSortingSelectSQL($sort, $order = 'ASC')
    {
        if ($this->isRandomOrder($order)) {
            return null;
        }

        $section = SectionManager::fetch($this->get('linked_section_id'));
        $field = FieldManager::fetch($this->get('linked_field_id'));
        $fieldId = $field->get('id');
        $sortableFieldId = 0;
        foreach ($section->fetchFields() as $f) {
            if ($f->isSortable()) {
                $sortableFieldId = $f->get('id');
                break;
            }
        }
        if (!$sortableFieldId) {
            return;
        }

        $sql = "SELECT `s`.`value` FROM `tbl_entries_data_$sortableFieldId` as `s` LEFT JOIN `tbl_entries_data_$fieldId` AS `d` ON `d`.`entry_id` = `s`.`entry_id` WHERE FIND_IN_SET(`e`.`id`, `d`.`entries`) LIMIT 1";
        return "($sql) AS `order_value`";
    }

    /**
     * Creates the table needed for the settings of the field
     */
    public static function createFieldTable()
    {
        $tbl = self::FIELD_TBL_NAME;

        return Symphony::Database()->query("
            CREATE TABLE IF NOT EXISTS `$tbl` (
                `id`                int(11) unsigned NOT NULL AUTO_INCREMENT,
                `field_id`          int(11) unsigned NOT NULL,
                `linked_section_id` int(11) unsigned NOT NULL,
                `linked_field_id`   int(11) unsigned NOT NULL,
                `mode`              varchar(50) NULL COLLATE utf8_unicode_ci DEFAULT NULL,
                `mode_table`        varchar(50) NULL COLLATE utf8_unicode_ci DEFAULT NULL,
                `mode_header`       varchar(50) NULL COLLATE utf8_unicode_ci DEFAULT NULL,
                `mode_footer`       varchar(50) NULL COLLATE utf8_unicode_ci DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `field_id` (`field_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ");
    }

    public static function update_200()
    {
        return static::createFieldTable();
    }

    public static function update_210()
    {
        $tbl = self::FIELD_TBL_NAME;
        $sql = "
            ALTER TABLE `$tbl`
                ADD COLUMN `mode` varchar(50) NULL COLLATE utf8_unicode_ci DEFAULT NULL
                    AFTER `linked_field_id`,
                ADD COLUMN `mode_table` varchar(50) NULL COLLATE utf8_unicode_ci DEFAULT NULL
                    AFTER `mode`,
                ADD COLUMN `mode_header` varchar(50) NULL COLLATE utf8_unicode_ci DEFAULT NULL
                    AFTER `mode_table`,
                ADD COLUMN `mode_footer` varchar(50) NULL COLLATE utf8_unicode_ci DEFAULT NULL
                    AFTER `mode_header`
        ";
        return Symphony::Database()->query($sql);
    }

    /**
     *
     * Drops the table needed for the settings of the field
     */
    public static function deleteFieldTable()
    {
        $tbl = self::FIELD_TBL_NAME;

        return Symphony::Database()->query("
            DROP TABLE IF EXISTS `$tbl`
        ");
    }
}

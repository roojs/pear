<?php

require_once 'File/DXF/Section.php';

class File_DXF_SectionHeader extends File_DXF_Section
{
    public function __construct()
    {
        parent::__construct('header');
    }
    public function parse($handle)
    {
        $variable_pattern = [
            'name' => '',
            'values' => [],
        ];
        $variable = $variable_pattern;
        require_once 'File/DXF/SystemVariable.php';

        while ($pair = $this->readPair($handle)) {
            if ($pair['value'] == 'ENDSEC') {
                break;
            }

            if ($pair['key'] == 9) {
                if (!empty($variable['values'])) {
                    $name = str_replace('$', '', $pair['value']);
                    if (strtoupper($name) == 'ACADVER') {
                        $variable['values'] = [1 => 'AC1012'];
                    }
                    $this->addItem(new File_DXF_SystemVariable($name, $variable['values']));
                }
            }

            $variable['values'][$pair['key']] = $pair['value'];
        }

    }
}

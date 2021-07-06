<?php

require_once 'File/DXF/Section.php';

class File_DXF_SectionHeader extends File_DXF_Section
{
    public function __construct()
    {
        parent::__construct('header');
    }
    public function parse($dxf)
    {
        $variable_pattern = array(
            'name' => '',
            'values' => [],
        );
        $variable = $variable_pattern;
        
        require_once 'File/DXF/SystemVariable.php';

        while ($pair = $dxf->readPair()) {
            
            if ($pair['value'] == 'ENDSEC' || $pair['key'] == 9) {
                if (!empty($variable['values']) {
                    $name = str_replace('$', '', $variable['name']);
                    if (strtoupper($name) == 'ACADVER') {
                        $variable['values'] = [1 => 'AC1012'];
                    }
                    $this->addItem(new File_DXF_SystemVariable($name, $variable['values']));
                }
            }
            
            if ($pair['value'] == 'ENDSEC') {
                // End of a section
                break;
            }
            
            if ($pair['key'] == 9) {
                // Beginning of a new header variable
                $variable = $variable_pattern;
                $variable['name'] = $pair['value'];
                continue;
            }
            
            $variable['values'][$pair['key']] = $pair['value'];
        }

    }
}
